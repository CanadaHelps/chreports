<?php
use CRM_Chreports_ExtensionUtil as E;
use CRM_Canadahelps_ExtensionUtils as EU;
class CRM_Chreports_Reports_SummaryReport extends CRM_Chreports_Reports_BaseReport {


    public function __construct( string $entity, int $id, string $name ) {
        parent::__construct( $entity, $id, $name);
    }

    public function buildSelectQuery(){
      $select = [];
      $this->_columnHeaders = [];

      // Add selected columns to SELECT clause
      foreach($this->_columns as $fieldName => $nodata) {

        if ($fieldName == 'total_amount')
            continue;
         else if($fieldName == 'financial_type')
         {
            $entityName = 'financial_type';
         }else{
            $entityName = $this->getEntity();
         }
        $columnInfo = $this->getFieldMapping( $entityName, $fieldName);
        $select[] = $columnInfo['table_name'] . "." .  $columnInfo['name'] . " AS $fieldName";
        //Adding columns to _columnHeaders for display purpose
        $this->_columnHeaders[$fieldName]['title'] = $columnInfo['title'];
        $this->_columnHeaders[$fieldName]['type'] = $columnInfo['type'];

      }  

      // Add default fields such as total, sum and currency
      $select[] = "COUNT(".$this->getEntityTable().".id) AS count";
      $this->_columnHeaders['count']['title'] = 'Number of Contributions';
      $this->_columnHeaders['count']['type'] = CRM_Utils_Type::T_INT;

      
      $select[] = "SUM(".$this->getEntityTable().".`total_amount`) AS total_amount";
      $this->_columnHeaders['total_amount']['title'] = 'Total Amount';
      $this->_columnHeaders['total_amount']['type'] = CRM_Utils_Type::T_MONEY;

      

      $select[] = "GROUP_CONCAT(DISTINCT ".$this->getEntityTable().".currency) AS currency";
      $this->_columnHeaders['currency']['title'] = 'Currency';
      $this->_columnHeaders['currency']['type'] = CRM_Utils_Type::T_STRING;

      // Combine everything
      $this->_selectClauses = $select;
      $selectVal = "SELECT " . implode(', ', $select) . " ";
      if ($this->_isPagination) {
        $selectVal = preg_replace('/SELECT(\s+SQL_CALC_FOUND_ROWS)?\s+/i', 'SELECT SQL_CALC_FOUND_ROWS ', $selectVal);
      }
      $this->_select = $selectVal;

    }


    public function buildGroupByQuery(){


    $groupBy = [];
      //columns and group by selection are always same that's why using columns here
    foreach($this->_columns as $fieldName => $nodata) {
      $fieldName = ($fieldName == 'financial_type') ? $fieldName . '_id' : $fieldName;

      if ($fieldName == 'total_amount')
        continue;
      
      $columnInfo = $this->getFieldMapping($this->getEntity(), $fieldName);
      $groupBy[] = $columnInfo['table_name'] . "." .  $columnInfo['name'];
      
    } 

    if (!empty($groupBy)) {
      $this->_groupBy = ' GROUP BY ' . implode(', ', $groupBy);
    }else{
      $this->_groupBy = "GROUP BY ".$this->getEntityTable('contact').".id";
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

      // Add columns joins (if needed)
      foreach($this->_columns as $fieldName => $nodata) {
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
          case 'financial_type':
            $from[] = " LEFT JOIN ".$this->getEntityTable('financial_type')."
            ON ".$this->getEntityTable().".financial_type_id = ".$this->getEntityTable('financial_type').".id";
            break;
          case 'ch_fund': // fund_13
            if(!array_key_exists('ch_fund',$this->_filters)){
              $entityTable = EU::getTableNameByName('Additional_info');
              $from[] = " LEFT JOIN ".$entityTable." 
              ON ".$entityTable.".entity_id = ".$this->getEntityTable().".id";
            }
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
        case 'campaign_type': 
          $from[] = " LEFT JOIN ".$fieldInfo['table_name']."
          ON ".$fieldInfo['table_name'].".entity_id = ".$this->getEntityTable().".contribution_page_id";
          break;
        }
      }
      
      $this->_from = "FROM " . implode(' ', $from) . " ";
    
    } 
 
}

?>