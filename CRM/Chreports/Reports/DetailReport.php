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
      if($fieldName == 'financial_type_id')
      {
         $entityName = $this->getEntity();
      }else if($fieldName == 'sort_name' || $fieldName == 'first_name' || $fieldName == 'last_name' || $fieldName == 'organization_name'|| $fieldName == 'exposed_id')
      {
         $entityName = 'contact';
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
        $selectStatement = ($columnInfo['select_clause_alias']) ? $columnInfo['select_clause_alias'] : $columnInfo['table_name'] . "." .  $columnInfo['name'];
        $select[] = $selectStatement . " AS $fieldName";
        //Adding columns to _columnHeaders for display purpose
        $this->_columnHeaders[$fieldName]['title'] = $columnInfo['title'];
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
            $columnInfo = $this->getFieldMapping($this->getEntity(), $fieldName);
            $orderBys[] = $columnInfo['table_name'] . "." .  $columnInfo['name']." ".$orderBy['order'];
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
      $from[] = "INNER JOIN " . $this->getEntityTable('contact') . " ON " . $this->getEntityTable('contact') . ".id = " . $this->getEntityTable() . ".contact_id";
      $fieldsForFromClauses = array_merge($this->_columns,$this->_orderByFields);

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
          case 'payment_instrument_id': // Account Type
            $from[] = $this->getSQLJoinForOptionValue("payment_instrument","payment_instrument_id",$this->getEntityTable());
        }
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