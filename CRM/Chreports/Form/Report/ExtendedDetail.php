<?php

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

  public function sectionTotals() {
    //get select clause statements for section headers
    list($select, $columnHeader) = $this->_reportInstance->getSortBySectionDetails();
    if (empty($select)) {
      return;
    } else {
      // pull section aliases out of $this->_sections
      $sectionAliases = array_keys($this->_sections);
      $addtotals = '';
      if (
        array_search("civicrm_contribution_total_amount", $this->_selectAliases) !==
        FALSE
      ) {
        $addtotals = ", sum(civicrm_contribution.total_amount) as sumcontribs";
        $showsumcontribs = TRUE;
      }
      //to get correct section header for calculated fields
      if (!empty($this->_reportInstance->getCalculatedFieldsList())) {
        $deletedSectionClause = [];
        foreach (array_keys($this->_reportInstance->getCalculatedFieldsList()) as $sectionkey => $sectionValue) {
          $columnInfo = $this->_reportInstance->getFieldMapping($this->_reportInstance->getEntityTableFromField($sectionValue), $sectionValue);
          $sortByAlias = ($columnInfo['custom_alias']) ? $columnInfo['custom_alias'] : $columnInfo['table_name'] . '_' . $sectionValue;
          if (($key = array_search($sortByAlias, $sectionAliases)) !== false) {
            //if calculated field is there in section header unset that clause first because it might have aggregated function in group by clause
            unset($sectionAliases[$key]);
            if (!in_array($columnInfo['table_name'] . '.id', $sectionAliases))
              $sectionAliases[$key] = $columnInfo['table_name'] . '.id';
            $deletedSectionClause[$sortByAlias] = $columnInfo['table_name'] . '.id';
          }
        }
        ksort($sectionAliases);
      }

      $selectStatement = "SELECT " . implode(', ', $select) . " ";
      $query = $selectStatement .
        "$addtotals, count(*) as ct {$this->_from} {$this->_where} group by " .
        implode(", ", $sectionAliases);
      //after query formation reinstate section columns for calculated fields display
      foreach ($deletedSectionClause as $k => $v) {
        if (($key = array_search($v, $sectionAliases)) !== false) {
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
          } else {
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
      } else {
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
      
    if($this->_reportInstance->isRecurringContributionReport()) {
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
    if(!empty($this->_reportInstance->getFilters())){
      if (array_key_exists('repeat_contri_initial_date_range',$this->_reportInstance->getFilters()) && array_key_exists('repeat_contri_second_date_range',$this->_reportInstance->getFilters())){
        $clauses[] = "((".$this->generateFilterClause($this->_reportInstance->getFilters()['repeat_contri_initial_date_range'], 'repeat_contri_initial_date_range').") 
        OR (".$this->generateFilterClause($this->_reportInstance->getFilters()['repeat_contri_second_date_range'], 'repeat_contri_second_date_range')."))";
      }
      foreach ($this->_reportInstance->getFilters() as $fieldName => $fieldInfo) {
        switch ($fieldName) {
          case 'total_range':
          case 'yid':
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
    return array_filter($clauses);
  }

  public function from() {
    return;
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
    foreach ($rows as $rowNum => $row) {
      $rows[$rowNum]['class'] = '';
      if (CRM_Utils_Array::value('total_amount', $row)) {
        $rows[$rowNum]['civicrm_contribution_currency'] = '';
      }
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
