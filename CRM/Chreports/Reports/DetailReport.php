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
      if($this->getReportName() == 'sybunt' || $this->getReportName() == 'lybunt')
      {
        $select[] = "MAX(".$this->getEntityTable('contribution').".receive_date) as lastContributionTime";
      }
      //echo '<pre>';print_r($this->_columns);echo '</pre>';
      // Add selected columns to SELECT clause
      foreach($this->_columns as $fieldName => $nodata) {
        if ($fieldName == 'total_amount')
            continue;

        if((parent::isGLAccountandPaymentMethodReconciliationReport()))
           {
            if($fieldName == 'title' || $fieldName == 'name' )
            {
               $entityName = 'batch';
            }

            if($fieldName == 'debit_name'  )
            {
               $entityName = 'financial_account';
            }

            if($fieldName == 'trxn_date' || $fieldName == 'card_type_id'  )
            {
               $entityName = 'financial_trxn';
            }

           }

      if($fieldName == 'financial_type_id')
      {
        //$entityName = 'financial_type';
        $entityName = $this->getEntity();
      }else if($fieldName == 'sort_name' || $fieldName == 'first_name' || $fieldName == 'last_name' || $fieldName == 'organization_name'|| $fieldName == 'exposed_id' || $fieldName == 'display_name' || $fieldName == 'external_identifier' || $fieldName == 'contact_type')
      {
         $entityName = 'contact';
      }
      else if($fieldName == 'phone' || $fieldName == 'email')
      {
         $entityName = $fieldName;
      }
      else if($fieldName == 'street_address' || $fieldName == 'city' || $fieldName == 'postal_code' || $fieldName == 'state_province_id' || $fieldName == 'country_id')
      {
         $entityName = 'address';
      }
      else if($fieldName == 'source')
      {
         $entityName = 'contribution';
      }
      else{
         $entityName = $this->getEntity();
      }

      
        $columnInfo = $this->getFieldMapping( $entityName, $fieldName);
        if($fieldName == 'financial_type_id')
      {
        $columnInfo['table_name'] = 'civicrm_financial_type';
        $columnInfo['name'] = 'name';
      }
      
     if((parent::isGLAccountandPaymentMethodReconciliationReport()))
     {
        if($fieldName == 'amount' ) {
          $columnInfo['title'] = 'Amount';
          $columnInfo['select_clause_alias'] = $this->getEntityTable('entity_financial_trxn')."_report.amount";
          $columnInfo['type'] = CRM_Utils_Type::T_MONEY;
        }
        if($fieldName == 'financial_type_id') {
          $columnInfo['table_name'] = 'civicrm_financial_type';
          $columnInfo['name'] = 'name';
        }

        if($fieldName == 'title' || $fieldName == 'name') {
        $columnInfo['table_name'] = 'civicrm_batch';
        $columnInfo['select_clause_alias'] = $this->getEntityTable('batch').".".$fieldName;
        $columnInfo['name'] = $fieldName;
        }

        if($fieldName == 'trxn_date' || $fieldName == 'card_type_id')
        {
          $columnInfo['table_name'] = 'civicrm_financial_trxn';
          $columnInfo['select_clause_alias'] = $this->getEntityTable('financial_trxn')."_report.".$fieldName;
          $columnInfo['name'] = $fieldName;
        }
     }
      if(parent::isRecurringContributionReport()){
        if($fieldName == 'total_amount' )
        {
          $columnInfo['title'] = 'This Month Amount';
          $columnInfo['select_clause_alias'] = "IFNULL((CASE WHEN 
          YEAR(".$this->getEntityTable('contribution').".receive_date) = YEAR(NOW()) AND MONTH(".$this->getEntityTable('contribution').".receive_date) = MONTH(NOW()) THEN SUM(".$this->getEntityTable('contribution').".total_amount) 
          END),0)";
          $columnInfo['type'] = CRM_Utils_Type::T_MONEY;
          //[select_clause_alias] => civicrm_phone.phone
        }

        if($fieldName == 'completed_contributions' )
        {
          $columnInfo['title'] = 'Completed Contributions';
          $columnInfo['select_clause_alias'] = "(COUNT(CASE WHEN ".$this->getEntityTable('contribution').".`contribution_status_id` = 1 THEN 1 END))";
          $columnInfo['type'] = CRM_Utils_Type::T_INT;
        }


        if($fieldName == 'last_month_amount' )
        {
          $columnInfo['title'] = 'Last Month Amount';
          $columnInfo['select_clause_alias'] = "IFNULL((CASE WHEN 
          YEAR(".$this->getEntityTable('contribution').".receive_date) = YEAR(MAX(".$this->getEntityTable('contribution').".receive_date)) AND MONTH(".$this->getEntityTable('contribution').".receive_date) = MONTH(MAX(".$this->getEntityTable('contribution').".receive_date)) THEN SUM(".$this->getEntityTable('contribution').".total_amount) 
          END),0)";
          $columnInfo['type'] = CRM_Utils_Type::T_MONEY;
        }

        if($fieldName == 'start_date' )
        {
          $columnInfo['title'] = 'Start Date/First Contribution';
          $columnInfo['select_clause_alias'] = "(MIN(".$this->getEntityTable('contribution').".receive_date))";
          $columnInfo['type'] = CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME;
        }
      }
      if($fieldName == 'organization_name')
      $columnInfo['title'] = 'Organization Name';


        // $selectStatement = ($columnInfo['select_clause_alias']) ? $columnInfo['select_clause_alias'] : $columnInfo['table_name'] . "." .  $columnInfo['name'];
        // $select[] = $selectStatement . " AS $fieldName";
        //for boolean data value display directly 'Yes' or 'No' rather than 1 or 0
        if($fieldName == 'application_submitted')
        {
          $select[] = "case when ".$columnInfo['table_name'] . "." .  $columnInfo['name']." then 'Yes' else 'No' end AS $fieldName";
        }else if($fieldName == 'civicrm_life_time_total')
        {
          $select[] = "SUM(".$this->getEntityTable('contribution').".total_amount) AS $fieldName";
          $this->_columnHeaders[$fieldName]['title'] = $columnInfo['title'];
        }
        else if($fieldName == 'last_four_year_total_amount')
        {
          $select[] = "SUM(IF(" . $this->whereClauseLastNYears('civicrm_contribution.receive_date',4) . ", civicrm_contribution.total_amount, 0)) as $fieldName";
          $this->_columnHeaders[$fieldName]['title'] = $this->getLastNYearColumnTitle(4);;
        }
        else if($fieldName == 'last_three_year_total_amount')
        {
          $select[] = "SUM(IF(" . $this->whereClauseLastNYears('civicrm_contribution.receive_date',3) . ", civicrm_contribution.total_amount, 0)) as $fieldName";
          $this->_columnHeaders[$fieldName]['title'] =$this->getLastNYearColumnTitle(3);;
        }
        else if($fieldName == 'last_two_year_total_amount')
        {
          $select[] = "SUM(IF(" . $this->whereClauseLastNYears('civicrm_contribution.receive_date',2) . ", civicrm_contribution.total_amount, 0)) as $fieldName";
          $this->_columnHeaders[$fieldName]['title'] = $this->getLastNYearColumnTitle(2);;
        }
        else if($fieldName == 'last_year_total_amount')
        {
          $select[] = "SUM(IF(" . $this->whereClauseLastYear('civicrm_contribution.receive_date') . ", civicrm_contribution.total_amount, 0)) as $fieldName";
          $this->_columnHeaders[$fieldName]['title'] = $this->getLastYearColumnTitle();;
        }else{
          $selectStatement = ($columnInfo['select_clause_alias']) ? $columnInfo['select_clause_alias'] : $columnInfo['table_name'] . "." .  $columnInfo['name'];
          $select[] = $selectStatement . " AS $fieldName";
          $this->_columnHeaders[$fieldName]['title'] = $columnInfo['title'];
        }
        //Adding columns to _columnHeaders for display purpose
       
        $this->_columnHeaders[$fieldName]['type'] = $columnInfo['type'];

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

      $customTablename = EU::getTableNameByName('Summary_Fields');
      $columnName = E::getColumnNameByName('Total_Lifetime_Contributions');
      $select[] = $customTablename.".".$columnName." AS total_lifetime_contributions";
      $this->_columnHeaders['total_lifetime_contributions']['title'] = 'Total Lifetime Contributions';
      $this->_columnHeaders['total_lifetime_contributions']['type'] = CRM_Utils_Type::T_MONEY;

      $columnName = E::getColumnNameByName('Amount_of_last_contribution');
      $select[] = $customTablename.".".$columnName." AS Amount_of_last_contribution";
      $this->_columnHeaders['Amount_of_last_contribution']['title'] = 'Amount of last contribution';
      $this->_columnHeaders['Amount_of_last_contribution']['type'] = CRM_Utils_Type::T_MONEY;

      $select[] = "GROUP_CONCAT(DISTINCT ".$this->getEntityTable('contribution').".currency) AS currency";
      $this->_columnHeaders['currency']['title'] = 'Currency';
      $this->_columnHeaders['currency']['type'] = CRM_Utils_Type::T_STRING;

      }

      // Combine everything
      $this->_selectClauses = $select;
      $this->_select = $select;
    }


    public function buildGroupByQuery(){
    $groupBy = [];
    $entityName = $this->getEntity();
    $fieldName = 'id';
    $columnInfo = $this->getFieldMapping($entityName, $fieldName);
    $groupBy[] = $columnInfo['table_name'] . "." .  $columnInfo['name'];
    $having = [];
      foreach($this->_filters as $fieldName => $fieldInfo) {
        switch ($fieldName) {
        case 'yid': // fund_13
         
      if($this->getReportName() == 'sybunt')
      {
          $having[] = $this->whereClauseLast4Year("lastContributionTime");
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
           if((parent::isGLAccountandPaymentMethodReconciliationReport()))
           {
            if($fieldName == 'title' || $fieldName == 'name' )
            {
               $entityName = 'batch';
            }

            if($fieldName == 'debit_name'  )
            {
               $entityName = 'financial_account';
            }

            if($fieldName == 'trxn_date' || $fieldName == 'card_type_id'  )
            {
               $entityName = 'financial_trxn';
            }

           }
            if($fieldName == 'sort_name' || $fieldName == 'first_name' || $fieldName == 'last_name' || $fieldName == 'organization_name'|| $fieldName == 'exposed_id' || $fieldName == 'external_identifier' || $fieldName == 'contact_type')
            {
               $entityName = 'contact';
            }
            else if($fieldName == 'phone' || $fieldName == 'email')
            {
               $entityName = $fieldName;
            }
            else if($fieldName == 'street_address' || $fieldName == 'city' || $fieldName == 'postal_code' || $fieldName == 'state_province_id' || $fieldName == 'country_id')
            {
               $entityName = 'address';
            }
            else if($fieldName == 'source')
            {
               $entityName = 'contribution';
            }
            else if($fieldName == 'trxn_date' || $fieldName == 'trxn_id' || $fieldName == 'card_type_id')
            {
              $entityName = 'financial_trxn';
            }
            else{
               $entityName = $this->getEntity();
            }

            $columnInfo = $this->getFieldMapping($entityName, $fieldName);
           if((parent::isRecurringContributionReport()) && ($fieldName == 'total_amount' || $fieldName == 'last_month_amount' || $fieldName == 'completed_contributions' || $fieldName == 'start_date'))
           {
            $orderBys[] = $columnInfo['name']." ".$orderBy['order'];
           }else if((parent::isGLAccountandPaymentMethodReconciliationReport()) && ($fieldName == 'debit_accounting_code' || $fieldName == 'debit_contact_id' || $fieldName == 'credit_accounting_code' || $fieldName == 'credit_contact_id'
           || $fieldName == 'debit_name'|| $fieldName == 'credit_name'))
           {
            $orderBys[] = $columnInfo['dbAlias']." ".$orderBy['order'];
           }else if($fieldName == 'civicrm_life_time_total' || $fieldName == 'last_year_total_amount')
           {
             $orderBys[] = $fieldName." ".$orderBy['order'];
           }else{
            $orderBys[] = $columnInfo['table_name'] . "." .  $columnInfo['name']." ".$orderBy['order'];
           }
            // assign order by fields which has section display checked
            if($orderBy['section'])
            $this->_orderByFields[$orderBy['column']] = $columnInfo['table_name'] . "." .  $columnInfo['name'];
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
      $from[] = $this->getEntityTable();
      if ($this->getEntity() != 'contact') {
        $from[] = $this->getSQLJoinForField('contact_id', $this->getEntityTable('contact'), $this->getEntityTable(), 'id', "INNER");
      }else{
        $from[] = $this->getSQLJoinForField('id', $this->getEntityTable('contribution'), $this->getEntityTable(), 'contact_id', "INNER");
      }
      //$from[] = "INNER JOIN " . $this->getEntityTable('contact') . " ON " . $this->getEntityTable('contact') . ".id = " . $this->getEntityTable() . ".contact_id";
      
      if(parent::isRecurringContributionReport()){
        $from[] = "INNER JOIN " . $this->getEntityTable('contribution') . " ON " . $this->getEntityTable() . ".id = " . $this->getEntityTable('contribution') . ".contact_id";
      }
      $fieldsForFromClauses = array_merge($this->_columns,$this->_orderByFields);
      if($this->getReportName() == 'top_donors')
        {
          $customTablename = EU::getTableNameByName('Summary_Fields');
          $from[] = $this->getSQLJoinForField('id', $customTablename, $this->getEntityTable('contact'),'entity_id');
        }

        if($this->isOpportunityReport())
        {
          $customTablename = EU::getTableNameByName('Grant');
            $from[] = " LEFT JOIN ".$customTablename."
            ON ".$this->getEntityTable().".id = ".$customTablename.".entity_id";
        }

      if($this->isGLAccountandPaymentMethodReconciliationReport())
        {
          //join condition for credit_name field
          $from[] = "LEFT JOIN ".$this->getEntityTable('entity_financial_trxn')." as ".$this->getEntityTable('entity_financial_trxn')."_report
          ON (".$this->getEntityTable().".id = ".$this->getEntityTable('entity_financial_trxn')."_report.entity_id AND
          ".$this->getEntityTable('entity_financial_trxn')."_report.entity_table = 'civicrm_contribution')";
          $from[] = "LEFT JOIN ".$this->getEntityTable('financial_trxn')." as ".$this->getEntityTable('financial_trxn')."_report
          ON ".$this->getEntityTable('financial_trxn')."_report.id = ".$this->getEntityTable('entity_financial_trxn')."_report.financial_trxn_id";
          $from[] = "LEFT JOIN ".$this->getEntityTable('financial_account')." as ".$this->getEntityTable('financial_account')."_credit ON ".$this->getEntityTable('financial_account')."_credit.id = ".$this->getEntityTable('financial_trxn')."_report.from_financial_account_id";
          //join condition for debit_name field
          $from[] = "LEFT JOIN ".$this->getEntityTable('financial_account')." as ".$this->getEntityTable('financial_account')."_debit ON ".$this->getEntityTable('financial_account')."_debit.id = ".$this->getEntityTable('financial_trxn')."_report.to_financial_account_id";
        }

      // Add columns joins (if needed)
      foreach($fieldsForFromClauses as $fieldName => $nodata) {
        switch ($fieldName) {
         
          case 'contribution_page_id': //campaign
          case 'campaign_id': //campaign group
          case 'financial_type_id': //Fund
            $fieldEntity = str_replace("_id", "", $fieldName);
            $from[] = $this->getSQLJoinForField($fieldName, $this->getEntityTable($fieldEntity), $this->getEntityTable('contribution'));
            break;
          case 'gl_account': // fund_13
            $from[] = " LEFT JOIN ".$this->getEntityTable('line_item')." 
            ON ".$this->getEntityTable('line_item').".contribution_id = ".$this->getEntityTable().".id";

            $from[] = " LEFT JOIN (
              SELECT financial_account_id,entity_id,entity_table 
              FROM ".$this->getEntityTable('financial_item')."  
              GROUP BY entity_id,financial_account_id HAVING SUM(amount)>0
            ) ".$this->getEntityTable('financial_item')." 
            ON ( ".$this->getEntityTable('financial_item').".entity_table = 'civicrm_line_item' 
            AND ".$this->getEntityTable('financial_item').".entity_id = ".$this->getEntityTable('line_item').".id) ";

            $from[] = " INNER JOIN ".$this->getEntityTable('financial_account')." 
            ON ".$this->getEntityTable('financial_item').".financial_account_id = ".$this->getEntityTable('financial_account').".id";
            break;
          case 'account_type':          // Account Type
            $from[] = $this->getSQLJoinForOptionValue("financial_account_type","financial_account_type_id",$this->getEntityTable('financial_account'),$fieldName);
            break;
          case 'payment_instrument_id':  // Payment Method
          case 'grant_type_id': //opportunity type
          case 'status_id': //opportunity status
            if ($fieldName == "payment_instrument_id")     $groupName = 'payment_instrument';                // financial_type_id
            else if ($fieldName == "grant_type_id")  $groupName = 'grant_type'; 
            else if ($fieldName == "status_id")  $groupName = 'grant_status'; 
            $from[] = $this->getSQLJoinForOptionValue($groupName,$fieldName,$this->getEntityTable(),$fieldName);
            break;
          case 'probability':
            $columnName =  E::getColumnNameByName('probability');
            $customTablename = EU::getTableNameByName('Grant');
            $optionGroupName = E::getOptionGroupNameByColumnName($columnName);
            $from[] = $this->getSQLJoinForOptionValue($optionGroupName,$columnName,$customTablename,$fieldName);
            break;
          case 'phone':
          case 'email':
            $from[] = $this->getSQLJoinForField('id', $this->getEntityTable($fieldName), $this->getEntityTable('contact'),'contact_id');
            break;
          case 'credit_contact_id':
            $from[] = "LEFT JOIN ".$this->getEntityTable('contact')." as civicrm_contact_credit ON civicrm_contact_credit.id = ".$this->getEntityTable('financial_account')."_credit.contact_id";
            break;
          case 'debit_contact_id':
            $from[] = "LEFT JOIN ".$this->getEntityTable('contact')." as civicrm_contact_debit ON civicrm_contact_debit.id = ".$this->getEntityTable('financial_account')."_debit.contact_id";
            break;
        }
      }
      
      if(parent::isRecurringContributionReport()){
        $tablename = E::getTableNameByName('Contribution_Details');
        $from[] = " LEFT JOIN {$tablename} ON {$tablename}.entity_id =  ".$this->getEntityTable('contribution').".id 
        AND sg_flag_38 = 1";
      }
       $mappingFields = parent::getAllFieldsMapping();
       if((count(array_intersect(array_keys($this->_params['fields']), array_keys($this->_mapping['civicrm_address']['fields'])))) ? true : false)
       {
        $from[] = " LEFT JOIN ".$this->getEntityTable('address')."
        ON (".$this->getEntityTable().".id =
        ".$this->getEntityTable('address').".contact_id)";
       }

       if((count(array_intersect(array_keys($this->_params['fields']), array_keys($this->_mapping['civicrm_batch']['fields'])))) ? true : false)
       {
        $from[] = "LEFT JOIN ".$this->getEntityTable('entity_batch')." AS ".$this->getEntityTable('entity_batch')."_report
        ON  ".$this->getEntityTable('financial_trxn')."_report.id = ".$this->getEntityTable('entity_batch')."_report.entity_id AND ".$this->getEntityTable('entity_batch')."_report.entity_table = 'civicrm_financial_trxn'";

        $from[] = "LEFT JOIN ".$this->getEntityTable('batch')." 
        ON  ".$this->getEntityTable('entity_batch')."_report.batch_id = ".$this->getEntityTable('batch')." .id";
       }
      

      // Add filter joins (if needed)
      foreach($this->_filters as $fieldName => $fieldInfo) {
        switch ($fieldName) {
        case 'ch_fund': // fund_13
          $from[] = " LEFT JOIN ".$fieldInfo['table_name']."
          ON ".$fieldInfo['table_name'].".entity_id = ".$this->getEntityTable().".id";
          break;
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