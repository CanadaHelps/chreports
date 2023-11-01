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
      


        //  $selectStatement = ($columnInfo['select_clause_alias']) ? $columnInfo['select_clause_alias'] : $columnInfo['table_name'] . "." .  $columnInfo['name'];
        //  $select[] = $selectStatement . " AS $fieldName";
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
        //get sg_flag_38 custom field table and value;
         $customTablename = EU::getTableNameByName('Contribution_Details');
         $columnName = E::getColumnNameByName('SG_Flag');

       // die('test');
        $select[] = "IF(".$this->getEntityTable('contribution').".contribution_recur_id IS NOT NULL, 1, IF(".$customTablename.".".$columnName." IS NOT NULL, 1, 0)) as is_recurring";
        $this->_columnHeaders['is_recurring']['title'] = 'is_recurring';
        $this->_columnHeaders['is_recurring']['type'] = CRM_Utils_Type::T_INT;


      }
      //Repeat contribution report 
      if((parent::isRepeatContributionReport()))
      {
        $select[] = "CASE 
        WHEN (COUNT(civicrm_contribution_secondset.id) = 0) THEN 'Skipped Donation'
        WHEN (COUNT(civicrm_contribution_primaryset.id) = 0) THEN 'New Donor'
        ELSE 
        CONCAT(ROUND(((SUM(civicrm_contribution_secondset.total_amount) -SUM(civicrm_contribution_primaryset.total_amount))/ SUM(civicrm_contribution_primaryset.total_amount))*100, 2),'%')
    END AS per_change";
        $this->_columnHeaders['per_change']['title'] = 'Per Change';
        $this->_columnHeaders['per_change']['type'] = CRM_Utils_Type::T_STRING;
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

      $statements = [];

      switch($fieldName){
        case 'recurring_contribution_total_amount':
          $statements = [ 
            $fieldName => "IFNULL((CASE WHEN 
              YEAR(".$this->getEntityTable('contribution').".receive_date) = YEAR(NOW()) AND MONTH(".$this->getEntityTable('contribution').".receive_date) = MONTH(NOW()) 
              THEN SUM(".$this->getEntityTable('contribution').".total_amount) 
              END),0)"
          ];
          break;
        case 'completed_contributions':
          $statements = [ 
            $fieldName => "(COUNT(CASE WHEN ".$this->getEntityTable('contribution').".`contribution_status_id` = 1 THEN 1 END))"
          ];
          break;
        case 'last_month_amount':
          $statements = [ 
            $fieldName => "IFNULL((CASE WHEN 
          YEAR(".$this->getEntityTable('contribution').".receive_date) = YEAR(MAX(".$this->getEntityTable('contribution').".receive_date)) AND MONTH(".$this->getEntityTable('contribution').".receive_date) = MONTH(MAX(".$this->getEntityTable('contribution').".receive_date)) THEN SUM(".$this->getEntityTable('contribution').".total_amount) 
          END),0)"];
          break;
        case 'recurring_contribution_start_date':
          $statements = [ 
            $fieldName => "(MIN(".$this->getEntityTable('contribution').".receive_date))"
          ];
          break;
        case 'application_submitted':
          $statements = [ 
            $fieldName => "case when ".$this->getEntityClauseFromField($fieldName)." then 'Yes' else 'No' end "
          ];
          break;
        case 'life_time_total':
          $statements = [ 
            $fieldName => "SUM(".$this->getEntityTable('contribution').".total_amount)"
          ];
          break;
        case 'last_four_year_total_amount':
          $statements = [ 
            $fieldName => "SUM(IF(" . $this->whereClauseLastNYears('civicrm_contribution.receive_date',4) . ", civicrm_contribution.total_amount, 0))"
          ];
          $_columnHeader[$fieldName]['title'] = $this->getLastNYearColumnTitle(4);
          break;
        case 'last_three_year_total_amount':
          $statements = [ 
            $fieldName => "SUM(IF(" . $this->whereClauseLastNYears('civicrm_contribution.receive_date',3) . ", civicrm_contribution.total_amount, 0))"
          ];
          $_columnHeader[$fieldName]['title'] = $this->getLastNYearColumnTitle(3);
          break;
        case 'last_two_year_total_amount':
          $statements = [ 
            $fieldName => "SUM(IF(" . $this->whereClauseLastNYears('civicrm_contribution.receive_date',2) . ", civicrm_contribution.total_amount, 0))"
          ];
          $_columnHeader[$fieldName]['title'] = $this->getLastNYearColumnTitle(2);
          break;
        case 'last_year_total_amount':
          $statements = [ 
            $fieldName => "SUM(IF(" . $this->whereClauseLastYear('civicrm_contribution.receive_date') . ", civicrm_contribution.total_amount, 0))"
          ];
          $_columnHeader[$fieldName]['title'] = $this->getLastYearColumnTitle();
          break;
        case 'range_one_stat':
          $statements = [ 
            $fieldName => "SUM(civicrm_contribution_primaryset.total_amount)",
            "primary_total_contribution_count" => "COUNT(civicrm_contribution_primaryset.id)"
          ];
          break;
        case 'range_two_stat':
          $statements = [ 
            "second_total_contribution_count" => "COUNT(civicrm_contribution_secondset.id)",
            $fieldName => "SUM(civicrm_contribution_secondset.total_amount)"
          ];
        //  $selectedCalculatedStatement = "COUNT(civicrm_contribution_secondset.id) AS  ,SUM(civicrm_contribution_secondset.total_amount)";
          break;
      }
      if (count($statements) > 0) {
        $this->_calculatedFields[$fieldName] = $statements;
        // for staistics calculated fields
        $this->_statisticsCalculatedFields[$fieldName] = ['title' =>$_columnHeader[$fieldName]['title'],'select'=>$statements];
        foreach($statements as $fieldName => $statement) {
          $select[] = $statement.' AS '.$fieldName;
          if ( preg_match('/(MIN|SUM|AVG|COUNT|MAX|MIN)/', $statement )) {
            $this->_having[] = $statement;
          }
        }
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
            $isCalculatedField = isset($fieldInfo['calculated_field']) && $fieldInfo['calculated_field'] === true;
            // assign order by fields which has section display checked
            if($orderBy['section']){}
            $this->_orderByFields[$orderBy['column']] = ($isCalculatedField) ? $this->getCalculatedFieldStatement($orderBy['column']) : $this->getCommonSelectClause($fieldName);
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


      // if($this->isGLAccountandPaymentMethodReconciliationReport())
      // {
      //   //join condition for credit_name field
      //   $from[] = "LEFT JOIN ".$this->getEntityTable('entity_financial_trxn')." as ".$this->getEntityTable('entity_financial_trxn')."_report
      //   ON (".$this->getEntityTable().".id = ".$this->getEntityTable('entity_financial_trxn')."_report.entity_id AND
      //   ".$this->getEntityTable('entity_financial_trxn')."_report.entity_table = 'civicrm_contribution')";
      //   $from[] = "LEFT JOIN ".$this->getEntityTable('financial_trxn')." as ".$this->getEntityTable('financial_trxn')."_report
      //   ON ".$this->getEntityTable('financial_trxn')."_report.id = ".$this->getEntityTable('entity_financial_trxn')."_report.financial_trxn_id";
      //   $from[] = "LEFT JOIN ".$this->getEntityTable('financial_account')." as ".$this->getEntityTable('financial_account')."_credit ON ".$this->getEntityTable('financial_account')."_credit.id = ".$this->getEntityTable('financial_trxn')."_report.from_financial_account_id";
      //   //join condition for debit_name field
      //   $from[] = "LEFT JOIN ".$this->getEntityTable('financial_account')." as ".$this->getEntityTable('financial_account')."_debit ON ".$this->getEntityTable('financial_account')."_debit.id = ".$this->getEntityTable('financial_trxn')."_report.to_financial_account_id";
      // }
//temporary commented code start
      // Add columns joins (if needed)
      // foreach($fieldsForFromClauses as $fieldName => $nodata) {
      //   switch ($fieldName) {
         
      //     case 'contribution_page_id': //campaign
      //     case 'campaign_id': //campaign group
      //     case 'financial_type_id': //Fund
      //       $fieldEntity = str_replace("_id", "", $fieldName);
      //       $from[] = $this->getSQLJoinForField($fieldName, $this->getEntityTable($fieldEntity), $this->getEntityTable('contribution'));
      //       break;
      //     case 'gl_account': // fund_13
      //       $from[] = " LEFT JOIN ".$this->getEntityTable('line_item')." 
      //       ON ".$this->getEntityTable('line_item').".contribution_id = ".$this->getEntityTable().".id";

      //       $from[] = " LEFT JOIN (
      //         SELECT financial_account_id,entity_id,entity_table 
      //         FROM ".$this->getEntityTable('financial_item')."  
      //         GROUP BY entity_id,financial_account_id HAVING SUM(amount)>0
      //       ) ".$this->getEntityTable('financial_item')." 
      //       ON ( ".$this->getEntityTable('financial_item').".entity_table = 'civicrm_line_item' 
      //       AND ".$this->getEntityTable('financial_item').".entity_id = ".$this->getEntityTable('line_item').".id) ";

      //       $from[] = " INNER JOIN ".$this->getEntityTable('financial_account')." 
      //       ON ".$this->getEntityTable('financial_item').".financial_account_id = ".$this->getEntityTable('financial_account').".id";
      //       break;
      //     case 'account_type':          // Account Type
      //       $from[] = $this->getSQLJoinForOptionValue("financial_account_type","financial_account_type_id",$this->getEntityTable('financial_account'),$fieldName);
      //       break;
      //     case 'payment_instrument_id':  // Payment Method
      //     case 'grant_type_id': //opportunity type
      //     case 'status_id': //opportunity status
      //     case 'contribution_status_id': //opportunity status
          
      //       if ($fieldName == "payment_instrument_id")     $groupName = 'payment_instrument';                // financial_type_id
      //       else if ($fieldName == "grant_type_id")  $groupName = 'grant_type'; 
      //       else if ($fieldName == "status_id")  $groupName = 'grant_status'; 
      //       else if ($fieldName == "contribution_status_id")  $groupName = 'contribution_status'; 
      //       $from[] = $this->getSQLJoinForOptionValue($groupName,$fieldName,$this->getEntityTable(),$fieldName);
      //       break;
      //     case 'probability':
      //       $columnName =  E::getColumnNameByName('probability');
      //       $customTablename = EU::getTableNameByName('Grant');
      //       $optionGroupName = E::getOptionGroupNameByColumnName($columnName);
      //       $from[] = $this->getSQLJoinForOptionValue($optionGroupName,$columnName,$customTablename,$fieldName);
      //       break;
      //     case 'phone':
      //     case 'email':
      //       $from[] = $this->getSQLJoinForField('id', $this->getEntityTable($fieldName), $this->getEntityTable('contact'),'contact_id');
      //       break;
      //     case 'credit_contact_id':
      //       $from[] = "LEFT JOIN ".$this->getEntityTable('contact')." as civicrm_contact_credit ON civicrm_contact_credit.id = ".$this->getEntityTable('financial_account')."_credit.contact_id";
      //       break;
      //     case 'debit_contact_id':
      //       $from[] = "LEFT JOIN ".$this->getEntityTable('contact')." as civicrm_contact_debit ON civicrm_contact_debit.id = ".$this->getEntityTable('financial_account')."_debit.contact_id";
      //       break;
      //     case 'card_type_id': //credit card type
      //       $from[] = $this->getSQLJoinForOptionValue("accept_creditcard",$fieldName,$this->getEntityTable('financial_trxn_report'),$fieldName);
      //       break;
      //     case 'range_one_stat':
      //       $from[] = "LEFT JOIN ".$this->getEntityTable('contribution')." as civicrm_contribution_primaryset ON ".$this->getEntityTable('contribution').".id = civicrm_contribution_primaryset.id";
      //       $filterFieldName = 'repeat_contri_initial_date_range';
      //       $from[] = " AND ( civicrm_contribution_primaryset.receive_date >= 20220101000000) 
      //       AND ( civicrm_contribution_primaryset.receive_date <= 20221231235959)";
      //       break;
      //     case 'range_two_stat':
      //       $from[] = "LEFT JOIN ".$this->getEntityTable('contribution')." as civicrm_contribution_secondset ON ".$this->getEntityTable('contribution').".id = civicrm_contribution_secondset.id";
      //       $from[] = " AND ( civicrm_contribution_primaryset.receive_date >= 20230101000000) 
      //       AND ( civicrm_contribution_primaryset.receive_date <= 20231231235959)";
      //       break;
      //   }
      // }
      //temporary commented code ends
      if(parent::isRecurringContributionReport()){
        $tablename = E::getTableNameByName('Contribution_Details');
        $columnName = E::getColumnNameByName('SG_Flag');
        $from[] = " LEFT JOIN {$tablename} ON {$tablename}.entity_id =  ".$this->getEntityTable('contribution').".id 
        AND ".$tablename.".".$columnName." = 1";
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