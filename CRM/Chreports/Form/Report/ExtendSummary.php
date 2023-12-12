<?php
use CRM_Chreports_ExtensionUtil as E;

class CRM_Chreports_Form_Report_ExtendSummary extends CRM_Report_Form_Contribute_Summary {

  private $_reportInstance;

  public function __construct() {
    parent::__construct();
  }

  public function getReportInstance(): CRM_Chreports_Reports_SummaryReport {
    // Instantiate Report Instance if doesn't exists yet
    if ($this->_reportInstance == NULL) {
      $reportPath = $this->_attributes['action'];
      list($reportId, $reportName) = CRM_Chreports_Reports_BaseReport::getReportDetail($reportPath);
      $this->_reportInstance = new CRM_Chreports_Reports_SummaryReport('contribution', $reportId, $reportName);
    }
    
    return $this->_reportInstance;
  }

  public static function formRule($fields, $files, $self) {
    //To disable default validation for filters Contribution Aggregate,Contribution Count fields
    return [];
  }

  public function buildSQLQuery(&$var) {
    $params = $var->getVar('_params');
    //CRM-2144 for precise "view results", filtering out preSelected fields 
    if($var->getVar('_force') == 1){
      //set column fields to params
      $trueKeys =  array_keys($params['fields'],true);
      $params['fields'] = array_fill_keys($trueKeys, true);
      //set sort by fields to params
      $params =  $this->_reportInstance->setDefaultOptionSortBy($params);
    }
    // setting out columns, filters, params,mapping from report object
    $this->_reportInstance->setFieldsMapping($var->getVar('_columns'));
    $this->_reportInstance->setFormParams($params);
    //TO DO need to make changes
    $settings = $this->_reportInstance->getDefaultColumns();
    if(empty($params['fields']))
    {
      $params['fields'] = [
        $settings[0] => 1
      ];
    }
    $this->_reportInstance->setColumns($params['fields']);
    $this->_reportInstance->setFilters();
    //Remove limit, pagination parameter from query for monthly/yearly reports
    if($this->_reportInstance->isPeriodicSummary() || ($this->_reportInstance->isTopDonorReport())){
      //$var->setVar('_limit','');
      $this->_reportInstance->setPagination(FALSE);
    }else{
      $this->_reportInstance->setPagination($this->addPaging);
    }
    //manage limit of query params
    $this->_reportInstance->setLimit($var->getVar('_limit'));

    // forcefully apply default filter values to params only for 'View Results' action
    if($var->getVar('_force') == 1){
        // Create params from the default JSON config file
        $this->_reportInstance->setDefaultFilterValues();

        // Set the new filters (if applicable)
        $this->_reportInstance->setFilters();

        // This is done for the generateFilterClause() method to work
        $this->_params = $this->_reportInstance->_params;
    }
    //attache entity name to fields for mapping purpose
    //$this->_reportInstance->setEntityTableForFields();

    // Report Instance
    // _entity => Contribution, Contact, etc
    // _columns => array of columns with info for each field
    // _sorting_fields => array of sort by with info for each field
    // _filters => array of filters with info for each field, and operator/filter info  

    // SELECT
    $this->_reportInstance->buildSelectQuery();
    $var->setVar('_select', $this->_reportInstance->getSelect());
    $var->setVar('_selectClauses', $this->_reportInstance->getSelectClauses());
    $var->setVar('_columnHeaders', $this->_reportInstance->getColumnHeaders());
    
    // ORDER BY
    $this->_reportInstance->buildOrderByQuery();
    $var->setVar('_orderBy', $this->_reportInstance->getOrderBy());

    // FROM
    $this->_reportInstance->buildFromQuery();
    $var->setVar('_from', $this->_reportInstance->getFrom());

    // GROUP BY
    $this->_reportInstance->buildGroupByQuery();
    $var->setVar('_groupBy', $this->_reportInstance->getGroupBy());

     // SORT BY SECTIONS
    $this->_reportInstance->updateSelectWithSortBySections();
     $var->setVar('_select', $this->_reportInstance->getSelect());
     $var->setVar('_selectClauses', $this->_reportInstance->getSelectClauses());
     $var->setVar('_columnHeaders', $this->_reportInstance->getColumnHeaders());
    // WHERE
    // requires access to form
    $clauses = $this->buildWhereClause();
    if (empty($clauses)) {
      $var->setVar('_where', "WHERE ( 1 ) ");
      $var->setVar('_having', "");
    } else {
      $var->setVar('_where', "WHERE " . implode(' AND ', $clauses));
    }

    $this->_reportInstance->setWhere($var->getVar('_where'));
    //HAVING
    $havingClause = $this->buildHavingClause();
    if (!empty($havingClause)) {
      $var->setVar('_having', "HAVING " . implode(' AND ', $havingClause));
      $this->_reportInstance->setHaving($var->getVar('_having'));
    }
    $var->setVar('_limit', $this->_reportInstance->getLimit());
  }

  private function buildHavingClause(): array {
    $havingclauses = [];
    //define Having clause array key values
    $havingClauseKeyVal = array_keys($this->_reportInstance->getHavingStatements());
    foreach ($this->_reportInstance->getFilters() as $fieldName => $fieldInfo) {
      if ( in_array($fieldInfo['dbAlias'], $havingClauseKeyVal) ) {
         // Calculated Fields included in having
        $havingclauses[] = $this->generateFilterClause($fieldInfo, $fieldName);
      }
    }
    return $havingclauses;
  }
  private function buildWhereClause(): array {
    $clauses = [];
      
    //-- DEFAULT: NOT a test contribution
    $clauses[] = $this->_reportInstance->getEntityTable().'.is_test = 0';
    
    //-- DEFAULT: Contact is not deleted (trash)
    $clauses[] = $this->_reportInstance->getEntityTable('contact').'.is_deleted = 0';
    
    // Filters
    if(!empty($this->_reportInstance->getFilters())) {
      //define Having clause array key values
      $havingClauseKeyVal = array_keys($this->_reportInstance->getHavingStatements());
      foreach ($this->_reportInstance->getFilters() as $fieldName => $fieldInfo) {
        switch ($fieldName) {
          case 'campaign_type':
          case 'ch_fund': // fund_13
            $clauses[] = $this->generateFilterClause($fieldInfo, $fieldInfo['name']);
            break;
          case 'base_year': // BaseYear
            $fieldInfo['dbAlias'] = "YEAR(".$fieldInfo['dbAlias'].")";
            $defaultCompareOperator =(strpos( $this->generateFilterClause($fieldInfo, $fieldName), 'LIKE' ) !== false) ? 'LIKE' : '=';
            $clauses[] = str_replace($defaultCompareOperator, ">=", $this->generateFilterClause($fieldInfo, $fieldName)) ;
            break;
          default:
          if ( !in_array($fieldInfo['dbAlias'], $havingClauseKeyVal) )
            $clauses[] = $this->generateFilterClause($fieldInfo, $fieldName);
            break;
        }
      }
    }
    return $clauses;
  }

  public function groupBy() {
    parent::groupBy();
    $this->_groupBy = str_replace($this->_rollup, '', $this->_groupBy);
    $this->_rollup = '';
  }

  public function from($entity = NULL) {
    $this->setFromBase('civicrm_contact');

    $params = $this->_params;
    $table = 'civicrm_contribution';
    foreach (['contribution_page_id', 'campaign_id', 'financial_type'] as $key) {
      $contactID = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_contact LIMIT 1');
      if (!empty($params['fields'][$key]) && !empty($params['group_bys'][$key])) {
        $key = ($key == 'financial_type') ? $key . '_id' : $key;
        $this->createTemporaryTable($key . '_temp_table' , "SELECT * FROM civicrm_contribution WHERE $key IS NOT NULL");
        if ($key == 'contribution_page_id') {
          CRM_Core_DAO::executeQuery("INSERT INTO {$this->temporaryTables[$key . '_temp_table']['name']} (contribution_page_id, total_amount, id, contact_id)
            SELECT id as contribution_page_id, 0 as total_amount, 1 as id, $contactID as contact_id
            FROM civicrm_contribution_page
            WHERE id NOT IN (SELECT contribution_page_id FROM civicrm_contribution WHERE contribution_page_id IS NOT NULL)
          ");
          $table = $this->temporaryTables[$key . '_temp_table']['name'];
        }
        elseif ('financial_type_id' == $key) {
          CRM_Core_DAO::executeQuery("INSERT INTO {$this->temporaryTables[$key . '_temp_table']['name']} (financial_type_id, total_amount, id, contact_id)
            SELECT id as financial_type_id, 0 as total_amount, 1 as id, $contactID as contact_id
            FROM civicrm_financial_type
            WHERE id NOT IN (SELECT financial_type_id FROM civicrm_contribution WHERE financial_type_id IS NOT NULL)
          ");
          $table = $this->temporaryTables[$key . '_temp_table']['name'];
        }
        else {
          CRM_Core_DAO::executeQuery("INSERT INTO {$this->temporaryTables[$key . '_temp_table']['name']} (campaign_id, total_amount, id, contact_id)
            SELECT id as campaign_id, 0 as total_amount, 1 as id, $contactID as contact_id
            FROM civicrm_campaign
            WHERE id NOT IN (SELECT campaign_id FROM civicrm_contribution WHERE campaign_id IS NOT NULL)
          ");
          $table = $this->temporaryTables[$key . '_temp_table']['name'];
        }
      }
    }

    $this->_from .= "

             INNER JOIN $table   {$this->_aliases['civicrm_contribution']}
                     ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND
                        {$this->_aliases['civicrm_contribution']}.is_test = 0
             LEFT  JOIN civicrm_financial_type  {$this->_aliases['civicrm_financial_type']}
                     ON {$this->_aliases['civicrm_contribution']}.financial_type_id ={$this->_aliases['civicrm_financial_type']}.id
             LEFT JOIN civicrm_campaign campaign
                    ON {$this->_aliases['civicrm_contribution']}.campaign_id = campaign.id
             ";

    $this->joinAddressFromContact();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();
    $this->addFinancialTrxnFromClause();
    //CRM-1887 Removed reliability of report generation from extra transaction and line item tables
    //Now detailed and summary report primarily using same tables for report generation
    // replace the core select columns to use respective columns
    $this->_select = str_replace("COUNT({$this->_aliases['civicrm_contribution']}.total_amount)", "COUNT(DISTINCT {$this->_aliases['civicrm_contribution']}.id)", $this->_select);
    //CRM-819 To resolve data discrepancy where discrepancy contributions not having a GL account (financial account) defined
    //changing condition from "INNER" join to "LEFT" join
    $this->_from .= "
    LEFT JOIN civicrm_financial_item fi ON {$this->_aliases['civicrm_contribution']}.id = fi.id AND fi.entity_table = 'civicrm_line_item'
    LEFT JOIN civicrm_financial_account fa ON fi.financial_account_id = fa.id
    ";
    $tableName = E::getTableNameByName('Campaign_Information');
    if (!empty($tableName)) {
      $this->_from .= "
      LEFT JOIN $tableName ct ON ct.entity_id = contribution_civireport.contribution_page_id
      ";
    }
  }

  public function statistics(&$rows) {

    $statistics = $this->_reportInstance->alterStatistics($rows);
    if($statistics || $this->_reportInstance->isContribRetentionReport()){
      $count = count($rows);
      // requires access to form
      //set Row(s) Listed and Total rows statistics
      $this->countStat($statistics, $count);
      //set Group by criteria statistics
      $this->groupByStat($statistics);
      //set filter criteria statistics
      $this->filterStat($statistics);
      //CRM-1257
      if($this->_reportInstance->isContribRetentionReport() && isset($statistics['counts']['rowsFound'])) {
        unset($statistics['counts']['rowsFound']);
      }
      return $statistics;
    }

    $statistics = parent::statistics($rows);
    unset($statistics['counts']['mode'], $statistics['counts']['median'], $statistics['counts']['avg']);

    if (!isset($this->_groupByArray['civicrm_contribution_currency'])) {
      $this->_groupByArray['civicrm_contribution_currency'] = 'currency';
    }
    $group = ' GROUP BY ' . implode(', ', $this->_groupByArray);

    $this->from('contribution');
    $this->customDataFrom();

    // Ensure that Extensions that modify the from statement in the sql also modify it in the statistics.
    CRM_Utils_Hook::alterReportVar('sql', $this, $this);

    $contriQuery = "
COUNT(DISTINCT {$this->_aliases['civicrm_contribution']}.id )         as civicrm_contribution_total_amount_count,
SUM({$this->_aliases['civicrm_contribution']}.total_amount)  as civicrm_contribution_total_amount_sum,
ROUND(AVG({$this->_aliases['civicrm_contribution']}.total_amount), 2) as civicrm_contribution_total_amount_avg,
{$this->_aliases['civicrm_contribution']}.currency                    as currency
{$this->_from} {$this->_where}";

if (!strstr($this->_from, 'civicrm_line_item li') && array_key_exists('financial_account', $this->_params['group_bys'])) {
  $contriQuery = "
COUNT(DISTINCT {$this->_aliases['civicrm_contribution']}.id )        as civicrm_contribution_total_amount_count,
SUM({$this->_aliases['civicrm_contribution']}.total_amount)          as civicrm_contribution_total_amount_sum,
ROUND(AVG({$this->_aliases['civicrm_contribution']}.total_amount), 2) as civicrm_contribution_total_amount_avg,
{$this->_aliases['civicrm_contribution']}.currency                    as currency
{$this->_from} {$this->_where}";
}

    $contriSQL = "SELECT {$contriQuery} {$group} {$this->_having}";
    $contriDAO = CRM_Core_DAO::executeQuery($contriSQL);
    $this->addToDeveloperTab($contriSQL);
    $currencies = $currAmount = $currAverage = $currCount = [];
    $totalAmount = $average = $mode = $median = [];
    $averageCount = [];
    $count = 0;
    while ($contriDAO->fetch()) {
      if (empty($contriDAO->currency)) {
        continue;
      }
      if (!isset($currAmount[$contriDAO->currency])) {
        $currAmount[$contriDAO->currency] = 0;
      }
      if (!isset($currCount[$contriDAO->currency])) {
        $currCount[$contriDAO->currency] = 0;
      }
      if (!isset($currAverage[$contriDAO->currency])) {
        $currAverage[$contriDAO->currency] = 0;
      }
      if (!isset($averageCount[$contriDAO->currency])) {
        $averageCount[$contriDAO->currency] = 0;
      }
      $currAmount[$contriDAO->currency] += $contriDAO->civicrm_contribution_total_amount_sum;
      $currCount[$contriDAO->currency] += $contriDAO->civicrm_contribution_total_amount_count;
      $currAverage[$contriDAO->currency] += ($contriDAO->civicrm_contribution_total_amount_sum/$contriDAO->civicrm_contribution_total_amount_count);
      $averageCount[$contriDAO->currency]++;
      $count += $contriDAO->civicrm_contribution_total_amount_count;

      if (!in_array($contriDAO->currency, $currencies)) {
        $currencies[] = $contriDAO->currency;
      }
    }

    foreach ($currencies as $currency) {
      if (empty($currency)) {
        continue;
      }
      $totalAmount[] = CRM_Utils_Money::format($currAmount[$currency], $currency) .
        " (" . $currCount[$currency] . ")";
      $average[] = CRM_Utils_Money::format(($currAverage[$currency] / $averageCount[$currency]), $currency);
    }

    $groupBy = "\n{$group}, {$this->_aliases['civicrm_contribution']}.total_amount";
    $orderBy = "\nORDER BY civicrm_contribution_total_amount_count DESC";
    $modeSQL = "SELECT MAX(civicrm_contribution_total_amount_count) as civicrm_contribution_total_amount_count,
      SUBSTRING_INDEX(GROUP_CONCAT(amount ORDER BY mode.civicrm_contribution_total_amount_count DESC SEPARATOR ';'), ';', 1) as amount,
      currency
      FROM (SELECT {$this->_aliases['civicrm_contribution']}.total_amount as amount,
    {$contriQuery} {$groupBy} {$orderBy}) as mode GROUP BY currency";

      $statistics['counts']['amount'] = [
        'title' => ts('Total Amount'),
        'value' => implode(',  ', $totalAmount),
        'type' => CRM_Utils_Type::T_STRING,
      ];
      $statistics['counts']['count'] = [
        'title' => ts('Total Contributions'),
        'value' => $count,
      ];

    return $statistics;
  }

  function _getTableNameByName($name) {
     $values = civicrm_api3('CustomGroup', 'get', [
       'name' => $name,
       'sequential' => 1,
       'return' => ['table_name'],
     ])['values'];
     if (!empty($values[0])) {
       return CRM_Utils_Array::value('table_name', $values[0]);
     }

     return NULL;
  }

  /**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {
    $entryFound = FALSE;
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'label');
    $contributionPages = CRM_Contribute_PseudoConstant::contributionPage(NULL, TRUE);
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();

    //CRM-16338 if both soft-credit and contribution are enabled then process the contribution's
    //total amount's average, count and sum separately and add it to the respective result list
    $softCredit = (!empty($this->_params['fields']['soft_amount']) && !empty($this->_params['fields']['total_amount']));
    if ($softCredit) {
      $this->from('contribution');
      $this->customDataFrom();
      $contriSQL = "{$this->_select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy} {$this->_limit}";
      CRM_Core_DAO::disableFullGroupByMode();
      $contriDAO = CRM_Core_DAO::executeQuery($contriSQL);
      CRM_Core_DAO::reenableFullGroupByMode();
      $this->addToDeveloperTab($contriSQL);
      $contriFields = [
        'civicrm_contribution_total_amount_sum',
        'civicrm_contribution_total_amount_avg',
        'civicrm_contribution_total_amount_count',
      ];
      $count = 0;
      while ($contriDAO->fetch()) {
        foreach ($contriFields as $column) {
          $rows[$count][$column] = $contriDAO->$column;
        }
        $count++;
      }
    }
    foreach ($rows as $rowNum => $row) {
      // make count columns point to detail report
      if (!empty($this->_params['group_bys']['receive_date']) &&
        !empty($row['civicrm_contribution_receive_date_start']) &&
        CRM_Utils_Array::value('civicrm_contribution_receive_date_start', $row) &&
        !empty($row['civicrm_contribution_receive_date_subtotal'])
      ) {

        $dateStart = CRM_Utils_Date::customFormat($row['civicrm_contribution_receive_date_start'], '%Y%m%d');
        $endDate = new DateTime($dateStart);
        $dateEnd = [];

        list($dateEnd['Y'], $dateEnd['M'], $dateEnd['d']) = explode(':', $endDate->format('Y:m:d'));

        switch (strtolower($this->_params['group_bys_freq']['receive_date'])) {
          case 'month':
            $dateEnd = date("Ymd", mktime(0, 0, 0, $dateEnd['M'] + 1,
              $dateEnd['d'] - 1, $dateEnd['Y']
            ));
            break;

          case 'year':
            $dateEnd = date("Ymd", mktime(0, 0, 0, $dateEnd['M'],
              $dateEnd['d'] - 1, $dateEnd['Y'] + 1
            ));
            break;

          case 'fiscalyear':
            $dateEnd = date("Ymd", mktime(0, 0, 0, $dateEnd['M'],
              $dateEnd['d'] - 1, $dateEnd['Y'] + 1
            ));
            break;

          case 'yearweek':
            $dateEnd = date("Ymd", mktime(0, 0, 0, $dateEnd['M'],
              $dateEnd['d'] + 6, $dateEnd['Y']
            ));
            break;

          case 'quarter':
            $dateEnd = date("Ymd", mktime(0, 0, 0, $dateEnd['M'] + 3,
              $dateEnd['d'] - 1, $dateEnd['Y']
            ));
            break;
        }
        $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
          "reset=1&force=1&receive_date_from={$dateStart}&receive_date_to={$dateEnd}",
          $this->_absoluteUrl,
          $this->_id,
          $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contribution_receive_date_start_link'] = $url;
        $rows[$rowNum]['civicrm_contribution_receive_date_start_hover'] = ts('List all contribution(s) for this date unit.');
        $entryFound = TRUE;
      }

      // make subtotals look nicer
      if (array_key_exists('civicrm_contribution_receive_date_subtotal', $row) &&
        !$row['civicrm_contribution_receive_date_subtotal']
      ) {
        $this->fixSubTotalDisplay($rows[$rowNum], $this->_statFields);
        $entryFound = TRUE;
      }

      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("Lists detailed contribution(s) for this record.");
        $entryFound = TRUE;
      }

      // convert contribution status id to status name
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = $contributionStatus[$value];
        $entryFound = TRUE;
      }

      // convert contribution status id to status name
      if ($value = CRM_Utils_Array::value('civicrm_contribution_payment_instrument_id', $row)) {
        $var[$rowNum]['civicrm_contribution_payment_instrument_id'] = CRM_Utils_Array::value($row['civicrm_contribution_payment_instrument_id'], $paymentInstruments);
        $entryFound = TRUE;
      }

      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = $this->getLabels($row['civicrm_financial_trxn_card_type_id'], 'CRM_Financial_DAO_FinancialTrxn', 'card_type_id');
        $entryFound = TRUE;
      }

      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_page_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_page_id'] = $contributionPages[$value];
        $entryFound = TRUE;
      }

      // If using campaigns, convert campaign_id to campaign title
      if (array_key_exists('civicrm_contribution_campaign_id', $row)) {
        if ($value = $row['civicrm_contribution_campaign_id']) {
          $rows[$rowNum]['civicrm_contribution_campaign_id'] = $this->campaigns[$value];
        }
        $entryFound = TRUE;
      }

      // convert batch id to batch title
      if (!empty($row['civicrm_batch_batch_id']) && !in_array('Subtotal', $rows[$rowNum])) {
        $rows[$rowNum]['civicrm_batch_batch_id'] = $this->getLabels($row['civicrm_batch_batch_id'], 'CRM_Batch_BAO_EntityBatch', 'batch_id');
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'contribute/detail', 'List all contribution(s) for this ') ? TRUE : $entryFound;
      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, 'contribute/detail', 'List all contribution(s) for this ') ? TRUE : $entryFound;
    }
  }

}
