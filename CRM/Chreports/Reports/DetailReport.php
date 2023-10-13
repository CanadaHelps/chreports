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


        $selectStatement = ($columnInfo['select_clause_alias']) ? $columnInfo['select_clause_alias'] : $columnInfo['table_name'] . "." .  $columnInfo['name'];
        $select[] = $selectStatement . " AS $fieldName";
        //for boolean data value display directly 'Yes' or 'No' rather than 1 or 0
        if($fieldName == 'application_submitted')
        {
          $select[] = "case when ".$columnInfo['table_name'] . "." .  $columnInfo['name']." then 'Yes' else 'No' end AS $fieldName";
        }
        //Adding columns to _columnHeaders for display purpose
        $this->_columnHeaders[$fieldName]['title'] = $columnInfo['title'];
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
   
    if (!empty($groupBy)) {
      $this->_groupBy = ' GROUP BY ' . implode(', ', $groupBy);
    }

    } 

    public function buildOrderByQuery(){

      $orderBys = [];
      if (!empty($this->_params['order_bys']) && is_array($this->_params['order_bys'])) 
      {
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
           }else{
            $orderBys[] = $columnInfo['table_name'] . "." .  $columnInfo['name']." ".$orderBy['order'];
           }
            // assign order by fields which has section display checked
            if($orderBy['section'])
            $this->_orderByFields[$orderBy['column']] = $columnInfo['table_name'] . "." .  $columnInfo['name'];
          }
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
      if($this->getEntityTable() !== 'civicrm_contact')
      {
        $from[] = "INNER JOIN " . $this->getEntityTable('contact') . " ON " . $this->getEntityTable('contact') . ".id = " . $this->getEntityTable() . ".contact_id";
      }
      if(parent::isRecurringContributionReport()){
        $from[] = "INNER JOIN " . $this->getEntityTable('contribution') . " ON " . $this->getEntityTable() . ".id = " . $this->getEntityTable('contribution') . ".contact_id";
      }
      $fieldsForFromClauses = array_merge($this->_columns,$this->_orderByFields);

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
         
          //campaign
          case 'contribution_page_id':
            $from[] = " LEFT JOIN ".$this->getEntityTable('contribution_page')."
            ON ".$this->getEntityTable().".contribution_page_id = ".$this->getEntityTable('contribution_page').".id";
            break;
            //campaign group
          case 'campaign_id':
            $from[] = " LEFT JOIN ".$this->getEntityTable('campaign')."
            ON ".$this->getEntityTable().".campaign_id = ".$this->getEntityTable('campaign').".id";
            break;
            //Fund
          case 'financial_type_id':
            $from[] = " LEFT JOIN ".$this->getEntityTable('financial_type')."
            ON ".$this->getEntityTable().".financial_type_id = ".$this->getEntityTable('financial_type').".id";
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