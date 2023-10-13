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
      if($fieldName == 'financial_type_id')
      {
         $entityName = 'financial_type';
      }else if($fieldName == 'sort_name' || $fieldName == 'first_name' || $fieldName == 'last_name' || $fieldName == 'organization_name'|| $fieldName == 'exposed_id' || $fieldName == 'display_name' || $fieldName == 'contact_sub_type' || $fieldName == 'contact_type' || $fieldName == 'contact_type' )
      {
         $entityName = 'contact';
      }else if($fieldName == 'phone' || $fieldName == 'email')
      {
         $entityName = $fieldName; //civicrm_phone or civicrm_email
      }else{
         $entityName = $this->getEntity();
      }
        $columnInfo = $this->getFieldMapping( $entityName, $fieldName);
        if($fieldName == 'financial_type_id')
      {
        $columnInfo['table_name'] = 'civicrm_financial_type';
        $columnInfo['name'] = 'name';
      }

      if($fieldName == 'organization_name')
      $columnInfo['title'] = 'Organization Name';
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
      //Contribution Table ID details
      $select[] = "(".$this->getEntityTable().".id) as civicrm_contribution_contribution_id";
      $this->_columnHeaders['civicrm_contribution_contribution_id']['title'] = 'contribution_id';
      $this->_columnHeaders['civicrm_contribution_contribution_id']['type'] = CRM_Utils_Type::T_INT;
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
            if($fieldName == 'civicrm_life_time_total' || $fieldName == 'last_year_total_amount')
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
      // Add columns joins (if needed)
      foreach($fieldsForFromClauses as $fieldName => $nodata) {
        switch ($fieldName) {
         
          case 'contribution_page_id': //campaign
          case 'campaign_id': //campaign group
          case 'financial_type_id': //Fund
            $fieldEntity = str_replace("_id", "", $fieldName);
            $from[] = $this->getSQLJoinForField($fieldName, $this->getEntityTable($fieldEntity), $this->getEntityTable('contribution'));
            break;
          case 'payment_instrument_id': // Account Type
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
        }
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