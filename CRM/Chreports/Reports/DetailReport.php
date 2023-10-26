<?php
use CRM_Chreports_ExtensionUtil as E;
use CRM_Canadahelps_ExtensionUtils as EU;
class CRM_Chreports_Reports_DetailReport extends CRM_Chreports_Reports_BaseReport {


    public function __construct( string $entity, int $id, string $name ) {
        parent::__construct( $entity, $id, $name);
    }

    public function buildSelectQuery(){

      $select = [];
      $this->_columnHeaders = [];
      
      // Add selected columns to SELECT clause
      foreach($this->_columns as $fieldName => $nodata) {
       
        $fieldInfo = $this->getFieldInfo($fieldName);
        $columnInfo = $this->getFieldMapping($this->getEntityTableFromField($fieldName), $fieldName);
      
        if((parent::isGLAccountandPaymentMethodReconciliationReport()))
        {
          //need to confirm with her
            // if($fieldName == 'amount' ) {
            //   $columnInfo['title'] = 'Amount';
            //   $columnInfo['select_clause_alias'] = $this->getEntityTable('entity_financial_trxn')."_report.amount";
            //   $columnInfo['type'] = CRM_Utils_Type::T_MONEY;
            // }
            
            // if($fieldName == 'title' || $fieldName == 'name') {
            //   $columnInfo['table_name'] = 'civicrm_batch';
            //   $columnInfo['select_clause_alias'] = $this->getEntityTable('batch').".".$fieldName;
            //   $columnInfo['name'] = $fieldName;
            // }

            // if($fieldName == 'trxn_date' || $fieldName == 'card_type_id')
            // {
            //   $columnInfo['table_name'] = 'civicrm_financial_trxn';
            //   $columnInfo['select_clause_alias'] = $this->getEntityTable('financial_trxn')."_report.".$fieldName;
            //   $columnInfo['name'] = $fieldName;
            // }
        }

        if($fieldName == 'application_submitted'){
          $select[] = "case when ".$this->getEntityClauseFromField($fieldName)." then 'Yes' else 'No' end AS $fieldName";
        }else if(isset($fieldInfo['calculated_field']) && $fieldInfo['calculated_field'] === true){
          // Calculated fields
          // @todo move code below this to the function
          $this->addCalculatedFieldstoSelect($select,$fieldName,$this->_columnHeaders);
        }else{
          $selectStatement = $this->getCommonSelectClause($fieldName);
          $select[] = $selectStatement . " AS $fieldName";
        }
        $this->_columnHeaders[$fieldName]['title'] = $this->_columnHeaders[$fieldName]['title'] ?? $columnInfo['title'];
        $this->_columnHeaders[$fieldName]['type'] = $columnInfo['type'];

        
        


          // if(parent::isRecurringContributionReport()){
          //   if($fieldName == 'total_amount' )
          //   {
          //     $columnInfo['title'] = 'This Month Amount';
          //     $columnInfo['select_clause_alias'] = "IFNULL((CASE WHEN 
          //     YEAR(".$this->getEntityTable('contribution').".receive_date) = YEAR(NOW()) AND MONTH(".$this->getEntityTable('contribution').".receive_date) = MONTH(NOW()) THEN SUM(".$this->getEntityTable('contribution').".total_amount) 
          //     END),0)";
          //     $columnInfo['type'] = CRM_Utils_Type::T_MONEY;
          //     //[select_clause_alias] => civicrm_phone.phone
          //   }

          //   if($fieldName == 'completed_contributions' )
          //   {
          //     $columnInfo['title'] = 'Completed Contributions';
          //     $columnInfo['select_clause_alias'] = "(COUNT(CASE WHEN ".$this->getEntityTable('contribution').".`contribution_status_id` = 1 THEN 1 END))";
          //     $columnInfo['type'] = CRM_Utils_Type::T_INT;
          //   }


          //   if($fieldName == 'last_month_amount' )
          //   {
          //     $columnInfo['title'] = 'Last Month Amount';
          //     $columnInfo['select_clause_alias'] = "IFNULL((CASE WHEN 
          //     YEAR(".$this->getEntityTable('contribution').".receive_date) = YEAR(MAX(".$this->getEntityTable('contribution').".receive_date)) AND MONTH(".$this->getEntityTable('contribution').".receive_date) = MONTH(MAX(".$this->getEntityTable('contribution').".receive_date)) THEN SUM(".$this->getEntityTable('contribution').".total_amount) 
          //     END),0)";
          //     $columnInfo['type'] = CRM_Utils_Type::T_MONEY;
          //   }

          //   if($fieldName == 'start_date' )
          //   {
          //     $columnInfo['title'] = 'Start Date/First Contribution';
          //     $columnInfo['select_clause_alias'] = "(MIN(".$this->getEntityTable('contribution').".receive_date))";
          //     $columnInfo['type'] = CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME;
          //   }
          // }
      


        // $selectStatement = ($columnInfo['select_clause_alias']) ? $columnInfo['select_clause_alias'] : $columnInfo['table_name'] . "." .  $columnInfo['name'];
        // $select[] = $selectStatement . " AS $fieldName";
        //for boolean data value display directly 'Yes' or 'No' rather than 1 or 0
        // if($fieldName == 'application_submitted')
        // {
        //   $select[] = "case when ".$this->getEntityClauseFromField($fieldName)." then 'Yes' else 'No' end AS $fieldName";
        //   $this->_columnHeaders[$fieldName]['title'] = $columnInfo['title'];
        // }else if($fieldName == 'life_time_total')
        // {
        //   $select[] = "SUM(".$this->getEntityTable('contribution').".total_amount) AS $fieldName";
        //   $this->_columnHeaders[$fieldName]['title'] = $columnInfo['title'];
        // }
        // else if($fieldName == 'last_four_year_total_amount')
        // {
        //   $select[] = "SUM(IF(" . $this->whereClauseLastNYears('civicrm_contribution.receive_date',4) . ", civicrm_contribution.total_amount, 0)) as $fieldName";
        //   $this->_columnHeaders[$fieldName]['title'] = $this->getLastNYearColumnTitle(4);;
        // }
        // else if($fieldName == 'last_three_year_total_amount')
        // {
        //   $select[] = "SUM(IF(" . $this->whereClauseLastNYears('civicrm_contribution.receive_date',3) . ", civicrm_contribution.total_amount, 0)) as $fieldName";
        //   $this->_columnHeaders[$fieldName]['title'] =$this->getLastNYearColumnTitle(3);;
        // }
        // else if($fieldName == 'last_two_year_total_amount')
        // {
        //   $select[] = "SUM(IF(" . $this->whereClauseLastNYears('civicrm_contribution.receive_date',2) . ", civicrm_contribution.total_amount, 0)) as $fieldName";
        //   $this->_columnHeaders[$fieldName]['title'] = $this->getLastNYearColumnTitle(2);;
        // }
        // else if($fieldName == 'last_year_total_amount')
        // {
        //   $select[] = "SUM(IF(" . $this->whereClauseLastYear('civicrm_contribution.receive_date') . ", civicrm_contribution.total_amount, 0)) as $fieldName";
        //   $this->_columnHeaders[$fieldName]['title'] = $this->getLastYearColumnTitle();;
        // }else{
           //common select clause
          
        //}
        //Adding columns to _columnHeaders for display purpose
       
        
        
      }
      if(!parent::isRecurringContributionReport()){
        //Contribution Table ID details
      $select[] = "(".$this->getEntityTable().".id) as civicrm_contribution_contribution_id";
      $this->_columnHeaders['civicrm_contribution_contribution_id']['title'] = 'contribution_id';
      $this->_columnHeaders['civicrm_contribution_contribution_id']['type'] = CRM_Utils_Type::T_INT;

      }else{
        $select[] = "COUNT(".$this->getEntityTable('contribution').".id) as count";
        $this->_columnHeaders['count']['title'] = 'contribution_id';
        $this->_columnHeaders['count']['type'] = CRM_Utils_Type::T_INT;

        $select[] = "IF(".$this->getEntityTable('contribution').".contribution_recur_id IS NOT NULL, 1, IF(sg_flag_38 IS NOT NULL, 1, 0)) as is_recurring";
        $this->_columnHeaders['is_recurring']['title'] = 'is_recurring';
        $this->_columnHeaders['is_recurring']['type'] = CRM_Utils_Type::T_INT;


      }
      
      //contact Table ID details
      $select[] = "(".$this->getEntityTable('contact').".id) as civicrm_contact_id";
      $this->_columnHeaders['civicrm_contact_id']['title'] = 'contact_id';
      $this->_columnHeaders['civicrm_contact_id']['type'] = CRM_Utils_Type::T_INT;

      if($this->getReportName() == 'top_donors')
      {
      $select[] = "COUNT(".$this->getEntityTable('contribution').".id) AS count";
      $this->_columnHeaders['count']['title'] = 'Donations';
      $this->_columnHeaders['count']['type'] = CRM_Utils_Type::T_INT;

      $select[] = "SUM(".$this->getEntityTable('contribution').".`total_amount`) AS total_amount";
      $this->_columnHeaders['total_amount']['title'] = 'Aggregate Amount';
      $this->_columnHeaders['total_amount']['type'] = CRM_Utils_Type::T_MONEY;

      $select[] = "ROUND(AVG(".$this->getEntityTable('contribution').".`total_amount`),2) AS avg_amount";
      $this->_columnHeaders['avg_amount']['title'] = 'Average';
      $this->_columnHeaders['avg_amount']['type'] = CRM_Utils_Type::T_MONEY;

      $fieldInfo = $this->getFieldInfo('total_lifetime_contributions');
      $select[] = $this->getCommonSelectClause('total_lifetime_contributions') ." AS total_lifetime_contributions";
      $this->_columnHeaders['total_lifetime_contributions']['title'] = $fieldInfo['title'];
      $this->_columnHeaders['total_lifetime_contributions']['type'] = $this->getFilterType('total_lifetime_contributions')['type'];

      $fieldInfo = $this->getFieldInfo('Amount_of_last_contribution');
      $select[] = $this->getCommonSelectClause('Amount_of_last_contribution') ." AS Amount_of_last_contribution";
      $this->_columnHeaders['Amount_of_last_contribution']['title'] = $fieldInfo['title'];
      $this->_columnHeaders['Amount_of_last_contribution']['type'] = $this->getFilterType('Amount_of_last_contribution')['type'];

      // $columnName = E::getColumnNameByName('Amount_of_last_contribution');
      // $select[] = $customTablename.".".$columnName." AS Amount_of_last_contribution";
      // $this->_columnHeaders['Amount_of_last_contribution']['title'] = 'Amount of last contribution';
      // $this->_columnHeaders['Amount_of_last_contribution']['type'] = CRM_Utils_Type::T_MONEY;

      $select[] = "GROUP_CONCAT(DISTINCT ".$this->getEntityTable('contribution').".currency) AS currency";
      $this->_columnHeaders['currency']['title'] = 'Currency';
      $this->_columnHeaders['currency']['type'] = CRM_Utils_Type::T_STRING;

      }

      if($this->getReportName() == 'sybunt' || $this->getReportName() == 'lybunt')
      {
        $select[] = "MAX(".$this->getEntityTable('contribution').".receive_date) as lastContributionTime";
      }
      // Combine everything
      $this->_selectClauses = $select;
      $this->_select = $select;
    }

    public function addCalculatedFieldstoSelect(&$select,$fieldName,&$_columnHeader) {
      $_columnHeader[$fieldName]['title'] = $this->getFieldInfo($fieldName)['title'];
      switch($fieldName){
        case 'recurring_contribution_total_amount':
          $selectedCalculatedStatement = "IFNULL((CASE WHEN 
          YEAR(".$this->getEntityTable('contribution').".receive_date) = YEAR(NOW()) AND MONTH(".$this->getEntityTable('contribution').".receive_date) = MONTH(NOW()) THEN SUM(".$this->getEntityTable('contribution').".total_amount) 
          END),0)";
          break;
        case 'completed_contributions':
          $selectedCalculatedStatement = "(COUNT(CASE WHEN ".$this->getEntityTable('contribution').".`contribution_status_id` = 1 THEN 1 END))";
          break;
        case 'last_month_amount':
          $selectedCalculatedStatement = "IFNULL((CASE WHEN 
          YEAR(".$this->getEntityTable('contribution').".receive_date) = YEAR(MAX(".$this->getEntityTable('contribution').".receive_date)) AND MONTH(".$this->getEntityTable('contribution').".receive_date) = MONTH(MAX(".$this->getEntityTable('contribution').".receive_date)) THEN SUM(".$this->getEntityTable('contribution').".total_amount) 
          END),0)";
          break;
        case 'recurring_contribution_start_date':
          $selectedCalculatedStatement = "(MIN(".$this->getEntityTable('contribution').".receive_date))";
          break;
        case 'application_submitted':
          $selectedCalculatedStatement = "case when ".$this->getEntityClauseFromField($fieldName)." then 'Yes' else 'No' end ";
          break;
        case 'life_time_total':
          $selectedCalculatedStatement = "SUM(".$this->getEntityTable('contribution').".total_amount)";
          break;
        case 'last_four_year_total_amount':
          $selectedCalculatedStatement = "SUM(IF(" . $this->whereClauseLastNYears('civicrm_contribution.receive_date',4) . ", civicrm_contribution.total_amount, 0))";
          $_columnHeader[$fieldName]['title'] = $this->getLastNYearColumnTitle(4);
          break;
        case 'last_three_year_total_amount':
          $selectedCalculatedStatement = "SUM(IF(" . $this->whereClauseLastNYears('civicrm_contribution.receive_date',3) . ", civicrm_contribution.total_amount, 0))";
          $_columnHeader[$fieldName]['title'] = $this->getLastNYearColumnTitle(3);
          break;
        case 'last_two_year_total_amount':
          $selectedCalculatedStatement = "SUM(IF(" . $this->whereClauseLastNYears('civicrm_contribution.receive_date',2) . ", civicrm_contribution.total_amount, 0))";
          $_columnHeader[$fieldName]['title'] = $this->getLastNYearColumnTitle(2);
          break;
        case 'last_year_total_amount':
          $selectedCalculatedStatement = "SUM(IF(" . $this->whereClauseLastYear('civicrm_contribution.receive_date') . ", civicrm_contribution.total_amount, 0))";
          $_columnHeader[$fieldName]['title'] = $this->getLastYearColumnTitle();
          break;
      }
      if (!empty($selectedCalculatedStatement)) {
        $select[] = $selectedCalculatedStatement.' AS '.$fieldName;
        $this->_orderByCalculatedSection[$fieldName] = $selectedCalculatedStatement;
      }
    }


    public function buildGroupByQuery(){
    $groupBy = [];
    $entityName = $this->getEntity();
    $fieldName = 'id';
    if($entityName == 'contact') {
      $fieldEntityName = 'exposed_id';
    }else if($entityName == 'contribution') { //sunday refactoring start
      $fieldEntityName = 'contribution_id';
    }else if($entityName == 'grant') { //sunday refactoring start
      $fieldEntityName = 'grant_id';
    }
    
    $groupBy[] =  $this->getEntityClauseFromField($fieldEntityName);
    $having = [];
      foreach($this->_filters as $fieldName => $fieldInfo) {
        switch ($fieldName) {
        case 'yid': // fund_13
         
      if($this->getReportName() == 'sybunt')
      {
          $having[] = $this->whereClauseLast4Year("lastContributionTime");
          $this->_limit = '';
      }else if($this->getReportName() == 'lybunt'){
          $having[] = $this->whereClauseLastYear("lastContributionTime");
      }
          break;
        }
      }
    if (!empty($groupBy)) {
      $this->_groupBy = ' GROUP BY ' . implode(', ', $groupBy);
    }

    if (!empty($having)) {
      $this->_groupBy .= " HAVING " . implode(', ', $having);
    }

    } 

    public function buildOrderByQuery(){

      $orderBys = [];
      if (!empty($this->_params['order_bys']) && is_array($this->_params['order_bys'])) 
      {
      if($this->getReportName() == 'sybunt' || $this->getReportName() == 'lybunt')
      {
        $orderBys[] = "ISNULL(exposed_id)";
      }
        foreach ($this->_params['order_bys'] as $orderBy) {
          //if order by option is selected on the report
          if($orderBy['column'] != '-')
          {
            $fieldName = ($orderBy['column'] == 'financial_type') ? $orderBy['column'] . '_id' : $orderBy['column'];
          //  if((parent::isGLAccountandPaymentMethodReconciliationReport()))
          //  {
          //   if($fieldName == 'title' || $fieldName == 'name' )
          //   {
          //      $entityName = 'batch';
          //   }

          //   if($fieldName == 'debit_name'  )
          //   {
          //      $entityName = 'financial_account';
          //   }

          //   if($fieldName == 'trxn_date' || $fieldName == 'card_type_id'  )
          //   {
          //      $entityName = 'financial_trxn';
          //   }

          //  }
            // if($fieldName == 'sort_name' || $fieldName == 'first_name' || $fieldName == 'last_name' || $fieldName == 'organization_name'|| $fieldName == 'exposed_id' || $fieldName == 'external_identifier' || $fieldName == 'contact_type')
            // {
            //    $entityName = 'contact';
            // }
            // else if($fieldName == 'phone' || $fieldName == 'email')
            // {
            //    $entityName = $fieldName;
            // }
            // else if($fieldName == 'street_address' || $fieldName == 'city' || $fieldName == 'postal_code' || $fieldName == 'state_province_id' || $fieldName == 'country_id')
            // {
            //    $entityName = 'address';
            // }
            // else if($fieldName == 'source')
            // {
            //    $entityName = 'contribution';
            // }
            // else if($fieldName == 'trxn_date' || $fieldName == 'trxn_id' || $fieldName == 'card_type_id')
            // {
            //   $entityName = 'financial_trxn';
            // }
            // else{
            //    $entityName = $this->getEntity();
            // }

            //$columnInfo = $this->getFieldMapping($entityName, $fieldName);
          //  if((parent::isRecurringContributionReport()) && ($fieldName == 'total_amount' || $fieldName == 'last_month_amount' || $fieldName == 'completed_contributions' || $fieldName == 'start_date'))
          //  {
          //   $orderBys[] = $columnInfo['name']." ".$orderBy['order'];
          //  }
          //else if((parent::isGLAccountandPaymentMethodReconciliationReport()) && ($fieldName == 'debit_accounting_code' || $fieldName == 'debit_contact_id' || $fieldName == 'credit_accounting_code' || $fieldName == 'credit_contact_id'
          //  || $fieldName == 'debit_name'|| $fieldName == 'credit_name'))
          //  {
          //   $orderBys[] = $columnInfo['dbAlias']." ".$orderBy['order'];
          //  }
          //   else if($fieldName == 'life_time_total' || $fieldName == 'last_year_total_amount')
          //  {
          //    $orderBys[] = $fieldName." ".$orderBy['order'];
          //  }else{
            $orderBys[] = $this->getEntityClauseFromField($fieldName)." ".$orderBy['order'];
            $this->_orderByFieldsFrom[$orderBy['column']] = true;
            //$orderBys[] = $columnInfo['table_name'] . "." .  $columnInfo['name']." ".$orderBy['order'];
           //}
            $fieldInfo = $this->getFieldInfo($orderBy['column']);
            // assign order by fields which has section display checked
            if($orderBy['section']){}
            $this->_orderByFields[$orderBy['column']] = (isset($fieldInfo['calculated_field']) && $fieldInfo['calculated_field'] === true) ? 
            $this->_orderByCalculatedSection[$orderBy['column']] : $this->getCommonSelectClause($fieldName);
          }
        }
        if($this->getReportName() == 'top_donors')
      {
        unset($orderBys);
        $orderBys[] = "total_amount DESC";
      }
      }

      if (!empty($orderBys)) {
        $this->_orderBy = "ORDER BY " . implode(', ', $orderBys);
      }

    } 

    public function buildFromQuery(){
      $from = [];
      
      // Add defaults for entity
      $this->getDefaultFromClause($from);

      //common from clause for summary and detailed reports
      $this->getCommonFromClause($from);
      
      // move to BaseReport::getCommonFromClause
      if(parent::isRecurringContributionReport()){
        $tablename = E::getTableNameByName('Contribution_Details');
        $from[] = " LEFT JOIN {$tablename} ON {$tablename}.entity_id =  ".$this->getEntityTable('contribution').".id 
        AND sg_flag_38 = 1";
      }
      
      // Add filter joins (if needed)
      foreach($this->_filters as $fieldName => $fieldInfo) {
        switch ($fieldName) {
        case 'total_range': // fund_13
         $limitRange =   $this->_params["total_range_value"];
          $this->_limit = ' LIMIT 0, '.$limitRange;
          $this->setPagination(FALSE);
          break;
        }
      }

      $this->_from = "FROM " . implode(' ', $from) . " ";
    
    } 

    public function getOptionValueFundName($tableName,$fieldName)
    {
      return CRM_Core_DAO::singlevalueQuery("SELECT name FROM $tableName WHERE title = 'Fund'");
    }
}

?>