<?php
use CRM_Chreports_ExtensionUtil as E;

class CRM_Chreports_Form_Report_ExtendedDetail extends CRM_Report_Form_Contribute_Detail {
  private $_reportInstance;

  public function __construct() {
    parent::__construct();
  }

  public function beginPostProcessCommon() {
    return;
  }

  public function buildQuery($applyLimit = FALSE) {
    $applyLimit = TRUE;
    return parent::buildQuery($applyLimit);
  }

  public function statistics(&$rows) {
   
    $showDetailedStat = ($this->_reportInstance->isOpportunityReport() || $this->_reportInstance->isGLAccountandPaymentMethodReconciliationReport()
    || $this->_reportInstance->isComparisonReport() || $this->_reportInstance->isRecurringContributionReport() || $this->_reportInstance->isLYBNTSYBNTReport()) ? false:true;
    $statistics = $this->_reportInstance->alterStatistics($rows,$showDetailedStat);

  
    if ($statistics || $this->_reportInstance->isTopDonorReport()) {
      $count = count($rows);
      // requires access to form
      //set Row(s) Listed and Total rows statistics
      $this->countStat($statistics, $count);
      //set Group by criteria statistics
      $this->groupByStat($statistics);
      //set filter criteria statistics
      $this->filterStat($statistics);
      return $statistics;
    }
  }
  
  public function sectionTotals()
  { 
    //get select clause statements for section headers
    list($select,$columnHeader) =$this->_reportInstance->getSortBySectionDetails();
    if (empty($select)) {
      return;
    }else{
      // pull section aliases out of $this->_sections
      $sectionAliases = array_keys($this->_sections);
      $addtotals = '';
      if (array_search("civicrm_contribution_total_amount", $this->_selectAliases) !==
        FALSE
      ) {
        $addtotals = ", sum(civicrm_contribution.total_amount) as sumcontribs";
        $showsumcontribs = TRUE;
      }
      //to get correct section header for calculated fields
      if(!empty($this->_reportInstance->getCalculatedFieldsList()))
      {
        $deletedSectionClause = [];
        foreach(array_keys($this->_reportInstance->getCalculatedFieldsList()) as $sectionkey => $sectionValue)
        {
          $columnInfo = $this->_reportInstance->getFieldMapping( $this->_reportInstance->getEntityTableFromField($sectionValue), $sectionValue);
            $sortByAlias = ($columnInfo['custom_alias']) ? $columnInfo['custom_alias'] : $columnInfo['table_name'].'_'.$sectionValue;
              if (($key = array_search($sortByAlias,$sectionAliases)) !== false) {
                //if calculated field is there in section header unset that clause first because it might have aggregated function in group by clause
                unset($sectionAliases[$key]);
                if(!in_array($columnInfo['table_name'].'.id',$sectionAliases))
                  $sectionAliases[$key] = $columnInfo['table_name'].'.id';
                $deletedSectionClause[$sortByAlias] = $columnInfo['table_name'].'.id';
              }
        }
        ksort($sectionAliases);
      }
    
      $selectStatement = "SELECT " . implode(', ', $select) . " ";
      $query = $selectStatement .
        "$addtotals, count(*) as ct {$this->_from} {$this->_where} group by " .
        implode(", ", $sectionAliases);
      //after query formation reinstate section columns for calculated fields display
      foreach($deletedSectionClause as $k => $v)
      {
        if (($key = array_search($v,$sectionAliases)) !== false) {
          unset($sectionAliases[$key]);
          $sectionAliases[$key] = $k;
        }
      }
      // initialize array of total counts
      $sumcontribs = $totals = [];
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $row = $dao->toArray();
        // add totals for all permutations of section values
        $values = [];
        $i = 1;
        ksort($sectionAliases);
        $aliasCount = count($sectionAliases);
        foreach ($sectionAliases as $alias) {
          $values[] = $row[$alias];
          $key = implode(CRM_Core_DAO::VALUE_SEPARATOR, $values);
          if ($i == $aliasCount) {
            // the last alias is the lowest-level section header; use count as-is
            $totals[$key] = $dao->ct;
            if ($showsumcontribs) {
              $sumcontribs[$key] = $dao->sumcontribs;
            }
          }
          else {
            // other aliases are higher level; roll count into their total
            $totals[$key] = (array_key_exists($key, $totals)) ? $totals[$key] + $dao->ct : $dao->ct;
            if ($showsumcontribs) {
              $sumcontribs[$key] = array_key_exists($key, $sumcontribs) ? $sumcontribs[$key] + $dao->sumcontribs : $dao->sumcontribs;
            }
          }
        }
      }
      //display contribution and total amount
      if ($showsumcontribs) {
        $totalandsum = [];
        $title = '%1 contributions : %2';

        foreach ($totals as $key => $total) {
          $totalandsum[$key] = ts($title, [
            1 => $total,
            2 => CRM_Utils_Money::format($sumcontribs[$key]),
          ]);
        }
        $this->assign('sectionTotals', $totalandsum);
      }
      else {
         //display total count
        $this->assign('sectionTotals', $totals);
      }
    }
  }
  public function validate() {
    $grandparent = get_parent_class(get_parent_class($this));
    return $grandparent::validate(); 
  }
  public function getReportInstance(): CRM_Chreports_Reports_DetailReport {
    
    // Instantiate Report Instance if doesn't exists yet
    if ($this->_reportInstance == NULL) {
      $reportPath = $this->_attributes['action'];
      list($reportId, $reportName) = CRM_Chreports_Reports_BaseReport::getReportDetail($reportPath);
      $this->_reportInstance = new CRM_Chreports_Reports_DetailReport('contribution', $reportId, $reportName);
    }
    
    return $this->_reportInstance;
  }
  public function buildSQLQuery(&$var) {
    // setting out columns, filters, params,mapping from report object
    $this->_reportInstance->setFieldsMapping($var->getVar('_columns'));
    $params = $var->getVar('_params');
    //CRM-2144 for precise "view results", filtering out preSelected fields 
    if($var->getVar('_force') == 1){
      //set column fields to params
      $trueKeys =  array_keys($params['fields'],true);
      $params['fields'] = array_fill_keys($trueKeys, true);
    }
    $this->_reportInstance->setFormParams($params);
    if($params['fields'])
    $this->_reportInstance->setColumns($params['fields']);
    $this->_reportInstance->setFilters();
    $this->_reportInstance->setPagination($this->addPaging);

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
         $fieldInfo['dbAlias'] = $this->_reportInstance->getHavingStatements()[$fieldName];
        $havingclauses[] = $this->generateFilterClause($fieldInfo, $fieldName);
      }
    }
   //Add aditional having clause for sybunt, lybunt report
    if($this->_reportInstance->getReportTemplate() == 'chreports/contrib_sybunt') {
      $havingclauses[] = $this->_reportInstance->whereClauseLast4Year("lastContributionTime");
    }else if($this->_reportInstance->getReportTemplate() == 'chreports/contrib_lybunt') {
      $havingclauses[] = $this->_reportInstance->whereClauseLastYear("lastContributionTime");
    }
    return $havingclauses;
  }
  private function buildWhereClause(): array {
    $clauses = [];
    //define Having clause array key values
    $havingClauseKeyVal = array_keys($this->_reportInstance->getHavingStatements());
      
    if($this->_reportInstance->isRecurringContributionReport())
    {
        list($customTablename,$columnName) = $this->_reportInstance->getCustomTableNameColumnName('SG_Flag');
        $clauses[] = 'IF('.$this->_reportInstance->getEntityTable('contribution').'.contribution_recur_id IS NOT NULL, 1, IF('.$customTablename.'.'.$columnName.' IS NOT NULL, 1, 0)) = 1';
      $clauses[] = $this->_reportInstance->getEntityTable('contribution').'.contribution_status_id = 1';
    }


    //-- DEFAULT: NOT a test contribution
    if(!$this->_reportInstance->isOpportunityReport())
    $clauses[] = $this->_reportInstance->getEntityTable('contribution').'.is_test = 0';
    
    //-- DEFAULT: Contact is not deleted (trash)
    $clauses[] = $this->_reportInstance->getEntityTable('contact').'.is_deleted = 0';
    
    // Filters
    $removeIndividualCluase = false;
    if(!empty($this->_reportInstance->getFilters())){
      if (array_key_exists('repeat_contri_initial_date_range',$this->_reportInstance->getFilters()) && array_key_exists('repeat_contri_second_date_range',$this->_reportInstance->getFilters())){
        $removeIndividualCluase = true;
        $clauses[] = "((".$this->generateFilterClause($this->_reportInstance->getFilters()['repeat_contri_initial_date_range'], 'repeat_contri_initial_date_range').") 
        OR (".$this->generateFilterClause($this->_reportInstance->getFilters()['repeat_contri_second_date_range'], 'repeat_contri_second_date_range')."))";
      }
      foreach ($this->_reportInstance->getFilters() as $fieldName => $fieldInfo) {
        switch ($fieldName) {
          case 'total_range':
          case 'yid': // fund_13
            break;
          case 'ch_fund': // fund_13
            $clauses[] = $this->generateFilterClause($fieldInfo, $fieldInfo['name']);
            break;
          case 'repeat_contri_initial_date_range':
          case 'repeat_contri_second_date_range':
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

  public function from() {
    parent::from();
    $cpTableName = E::getTableNameByName('Campaign_Information');
    $this->_from .= "
      LEFT JOIN civicrm_contribution_page cp ON cp.id = contribution_civireport.contribution_page_id
      LEFT JOIN civicrm_campaign campaign ON campaign.id = contribution_civireport.campaign_id
    ";
    if (!empty($cpTableName)) {
      $filter = '';
      $join = 'LEFT';
      if (!empty($this->_params['campaign_type_value']) || in_array($this->_params['campaign_type_op'], ['nll', 'nnll'])) {
        $join = 'INNER';
        $field = [
          'dbAlias' => 'ct.' . E::getColumnNameByName('Campaign_Type'),
          'name' => 'campaign_type',
        ];
        $filter = "AND " . $this->whereClause($field, $this->_params['campaign_type_op'], $this->_params['campaign_type_value'], NULL, NULL);
      }

      $this->_from .= "
      $join JOIN $cpTableName ct ON ct.entity_id = contribution_civireport.contribution_page_id $filter
      ";
    }
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
    $key = $tableName . 'custom_' . CRM_Utils_Array::value('id', civicrm_api3('CustomField', 'get', ['sequential' => 1, 'name' => 'Receipt_Number'])['values'][0], '');
    if (!empty($this->_columnHeaders[$key])) {
      $column = [$key => $this->_columnHeaders[$key]];
      $this->_columnHeaders = $column + $this->_columnHeaders;
    }

    // reorder the columns
    $columnHeaders = [];
    foreach ([
      'civicrm_contribution_campaign_id',
      'civicrm_contact_exposed_id',
      'civicrm_contact_sort_name',
      'civicrm_contribution_receive_date',
      'civicrm_contribution_total_amount',
      'civicrm_contribution_financial_type_id',
      'civicrm_contribution_contribution_page_id',
      'civicrm_contribution_campaign_type',
      'civicrm_contribution_source',
      'civicrm_contribution_payment_instrument_id',
    ] as $name) {
      if (array_key_exists($name, $this->_columnHeaders)) {
        $columnHeaders[$name] = $this->_columnHeaders[$name];
        unset($this->_columnHeaders[$name]);
      }
    }
    $this->_columnHeaders = array_merge($this->_columnHeaders, $columnHeaders);

    if (!empty($this->_columnHeaders['civicrm_contribution_campaign_type'])) {
      $optionValues = CRM_Core_OptionGroup::values(E::getOptionGroupNameByColumnName(E::getColumnNameByName('Campaign_Type')));
      foreach ($rows as $rowNum => $row) {
        $rows[$rowNum]['civicrm_contribution_campaign_type'] = CRM_Utils_Array::value($row['civicrm_contribution_campaign_type'], $optionValues);
      }
    }
    $entryFound = FALSE;
    $display_flag = $prev_cid = $cid = 0;
    $contributionTypes = CRM_Contribute_PseudoConstant::financialType();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'label');
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    // We pass in TRUE as 2nd param so that even disabled contribution page titles are returned and replaced in the report
    $contributionPages = CRM_Contribute_PseudoConstant::contributionPage(NULL, TRUE);
    $batches = CRM_Batch_BAO_Batch::getBatches();
    foreach ($rows as $rowNum => $row) {
      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // don't repeat contact details if its same as the previous row
        if (array_key_exists('civicrm_contact_id', $row)) {
          if ($cid = $row['civicrm_contact_id']) {
            if ($rowNum == 0) {
              $prev_cid = $cid;
            }
            else {
              if ($prev_cid == $cid) {
                $display_flag = 1;
                $prev_cid = $cid;
              }
              else {
                $display_flag = 0;
                $prev_cid = $cid;
              }
            }

            if ($display_flag) {
              foreach ($row as $colName => $colVal) {
                // Hide repeats in no-repeat columns, but not if the field's a section header
                if (in_array($colName, $this->_noRepeats) &&
                  !array_key_exists($colName, $this->_sections)
                ) {
                  unset($rows[$rowNum][$colName]);
                }
              }
            }
            $entryFound = TRUE;
          }
        }
      }

      if (CRM_Utils_Array::value('civicrm_contribution_contribution_or_soft', $rows[$rowNum]) ==
        'Contribution'
      ) {
        unset($rows[$rowNum]['civicrm_contribution_soft_soft_credit_type_id']);
      }

      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, 'contribution/detail', ts('View Contribution Details')) ? TRUE : $entryFound;
      // convert donor sort name to link
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        !empty($rows[$rowNum]['civicrm_contact_sort_name']) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
      }

      if ($value = CRM_Utils_Array::value('civicrm_contribution_financial_type_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_financial_type_id'] = $contributionTypes[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = $contributionStatus[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_page_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_page_id'] = $contributionPages[$value];
        $entryFound = TRUE;
      }
      if (!empty($row['civicrm_batch_batch_id'])) {
        $rows[$rowNum]['civicrm_batch_batch_id'] = $batches[$row['civicrm_batch_batch_id']] ?? NULL;
        $entryFound = TRUE;
      }
      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = $this->getLabels($row['civicrm_financial_trxn_card_type_id'], 'CRM_Financial_DAO_FinancialTrxn', 'card_type_id');
        $entryFound = TRUE;
      }

      // Contribution amount links to viewing contribution
      if ($value = CRM_Utils_Array::value('civicrm_contribution_total_amount', $row)) {
        $rows[$rowNum]['civicrm_contribution_total_amount'] = CRM_Utils_Money::format($value, $row['civicrm_contribution_currency']);
        if (CRM_Core_Permission::check('access CiviContribute')) {
          $url = CRM_Utils_System::url(
            "civicrm/contact/view/contribution",
            [
              'reset' => 1,
              'id' => $row['civicrm_contribution_contribution_id'],
              'cid' => $row['civicrm_contact_id'],
              'action' => 'view',
              'context' => 'contribution',
              'selectedChild' => 'contribute',
            ],
            $this->_absoluteUrl
          );
          $rows[$rowNum]['civicrm_contribution_total_amount_link'] = $url;
          $rows[$rowNum]['civicrm_contribution_total_amount_hover'] = ts("View Details of this Contribution.");
        }
        $entryFound = TRUE;
      }

      // convert campaign_id to campaign title
      if (array_key_exists('civicrm_contribution_campaign_id', $row)) {
        if ($value = $row['civicrm_contribution_campaign_id']) {
          $rows[$rowNum]['civicrm_contribution_campaign_id'] = $this->campaigns[$value];
          $entryFound = TRUE;
        }
      }

      // soft credits
      if (array_key_exists('civicrm_contribution_soft_credits', $row) &&
        'Contribution' ==
        CRM_Utils_Array::value('civicrm_contribution_contribution_or_soft', $rows[$rowNum]) &&
        array_key_exists('civicrm_contribution_contribution_id', $row)
      ) {
        $query = "
SELECT civicrm_contact_id, civicrm_contact_sort_name, civicrm_contribution_total_amount, civicrm_contribution_currency
FROM   {$this->temporaryTables['civireport_contribution_detail_temp2']['name']}
WHERE  civicrm_contribution_contribution_id={$row['civicrm_contribution_contribution_id']}";
        $dao = CRM_Core_DAO::executeQuery($query);
        $string = '';
        $separator = ($this->_outputMode !== 'csv') ? "<br/>" : ' ';
        while ($dao->fetch()) {
          $url = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' .
            $dao->civicrm_contact_id);
          $string = $string . ($string ? $separator : '') .
            "<a href='{$url}'>{$dao->civicrm_contact_sort_name}</a> " .
            CRM_Utils_Money::format($dao->civicrm_contribution_total_amount, $dao->civicrm_contribution_currency);
        }
        $rows[$rowNum]['civicrm_contribution_soft_credits'] = $string;
      }

      if (array_key_exists('civicrm_contribution_soft_credit_for', $row) &&
        'Soft Credit' ==
        CRM_Utils_Array::value('civicrm_contribution_contribution_or_soft', $rows[$rowNum]) &&
        array_key_exists('civicrm_contribution_contribution_id', $row)
      ) {
        $query = "
SELECT civicrm_contact_id, civicrm_contact_sort_name
FROM   {$this->temporaryTables['civireport_contribution_detail_temp1']['name']}
WHERE  civicrm_contribution_contribution_id={$row['civicrm_contribution_contribution_id']}";
        $dao = CRM_Core_DAO::executeQuery($query);
        $string = '';
        while ($dao->fetch()) {
          $url = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' .
            $dao->civicrm_contact_id);
          $string = $string .
            "\n<a href='{$url}'>{$dao->civicrm_contact_sort_name}</a>";
        }
        $rows[$rowNum]['civicrm_contribution_soft_credit_for'] = $string;
      }

      // CRM-18312 - hide 'contribution_or_soft' column if unchecked.
      if (!empty($this->noDisplayContributionOrSoftColumn)) {
        unset($rows[$rowNum]['civicrm_contribution_contribution_or_soft']);
        unset($this->_columnHeaders['civicrm_contribution_contribution_or_soft']);
      }

      //convert soft_credit_type_id into label
      if (array_key_exists('civicrm_contribution_soft_soft_credit_type_id', $rows[$rowNum])) {
        $rows[$rowNum]['civicrm_contribution_soft_soft_credit_type_id'] = CRM_Core_PseudoConstant::getLabel(
          'CRM_Contribute_BAO_ContributionSoft',
          'soft_credit_type_id',
          $row['civicrm_contribution_soft_soft_credit_type_id']
        );
      }

      // Contribution amount links to viewing contribution
      if ($value = CRM_Utils_Array::value('civicrm_pledge_payment_pledge_id', $row)) {
        if (CRM_Core_Permission::check('access CiviContribute')) {
          $url = CRM_Utils_System::url(
            "civicrm/contact/view/pledge",
            [
              'reset' => 1,
              'id' => $row['civicrm_pledge_payment_pledge_id'],
              'cid' => $row['civicrm_contact_id'],
              'action' => 'view',
              'context' => 'pledge',
              'selectedChild' => 'pledge',
            ],
            $this->_absoluteUrl
          );
          $rows[$rowNum]['civicrm_pledge_payment_pledge_id_link'] = $url;
          $rows[$rowNum]['civicrm_pledge_payment_pledge_id_hover'] = ts("View Details of this Pledge.");
        }
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'contribute/detail', 'List all contribution(s) for this ') ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
      $lastKey = $rowNum;
    }
  }

  /**
   * Override "This Year" $op options
   * @param string $type
   * @param null $fieldName
   *
   * @return array
   */
  public function getOperationPair($type = "string", $fieldName = NULL) {
    if ($fieldName == 'yid') {
      return [
        'calendar' => ts('Is Calendar Year'),
        'fiscal' => ts('Fiscal Year Starting'),
      ];
    }
    return parent::getOperationPair($type, $fieldName);
  }

}
