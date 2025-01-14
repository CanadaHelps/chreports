<?php

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
      $trueKeys =  isset($params['fields']) ? array_keys($params['fields'],true) : [];
      $params['fields'] = array_fill_keys($trueKeys, true);
      //set sort by fields to params
      $params =  $this->_reportInstance->setDefaultOptionSortBy($params);
    }
    // setting out columns, filters, params,mapping from report object
    $this->_reportInstance->setFieldsMapping($var->getVar('_columns'));
    $this->_reportInstance->setFormParams($params);
    //TO DO need to make changes
    $settings = $this->_reportInstance->getDefaultColumns();
    if(empty($params['fields']) && count($settings) > 0) {
      $params['fields'] = [
        $settings[0] => 1
      ];
    }
    if($params['fields'])
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
            break;
          default:
          if ( !in_array($fieldInfo['dbAlias'], $havingClauseKeyVal) )
            $clauses[] = $this->generateFilterClause($fieldInfo, $fieldName);
            break;
        }
      }
    }
    return array_filter($clauses);
  }

  public function groupBy() {
    parent::groupBy();
    $this->_groupBy = str_replace($this->_rollup, '', $this->_groupBy);
    $this->_rollup = '';
  }

  public function from($entity = NULL) {
    return;
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

    $statistics['counts']['amount'] = [
      'title' => ts('Total Amount'),
      'value' => implode(',  ', $totalAmount),
      'type' => CRM_Utils_Type::T_STRING,
    ];
    $totalCountLabel = ($this->_reportInstance->getEntity() == 'grant') ? 'Total Opportunities' : 'Total Contributions';
    $statistics['counts']['count'] = [
      'title' => ts($totalCountLabel),
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

}
