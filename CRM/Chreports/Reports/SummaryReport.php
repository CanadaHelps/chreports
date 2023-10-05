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

        if($fieldName){
        if ($fieldName == 'total_amount')
            continue;
         else if($fieldName == 'financial_type')
         {
            $entityName = 'financial_type';
         }else{
            $entityName = $this->getEntity();
         }
        $columnInfo = $this->getFieldMapping( $entityName, $fieldName);
        $selectStatement = ($columnInfo['select_clause_alias']) ? $columnInfo['select_clause_alias'] : $columnInfo['table_name']. "." . $columnInfo['name'];
        //$columnTableInfo = ($columnInfo['op_group_alias']) ? $columnInfo['table_name'].'_value' : $columnInfo['table_name'];
        $select[] = $selectStatement . " AS $fieldName";
        //Adding columns to _columnHeaders for display purpose
        $this->_columnHeaders[$fieldName]['title'] = $columnInfo['title'];
        $this->_columnHeaders[$fieldName]['type'] = $columnInfo['type'];
        }
      }  

      // Add default fields such as total, sum and currency
      $select[] = "COUNT(".$this->getEntityTable().".id) AS count";
      $this->_columnHeaders['count']['title'] = 'Number of Contributions';
      $this->_columnHeaders['count']['type'] = CRM_Utils_Type::T_INT;

      
      $select[] = "SUM(".$this->getEntityTable().".`total_amount`) AS total_amount";
      $this->_columnHeaders['total_amount']['title'] = 'Total Amount';
      $this->_columnHeaders['total_amount']['type'] = CRM_Utils_Type::T_MONEY;
      //don't include currenct column for Monthly / Yerly report
      if(!$this->isMonthlyYearlyReport()){
      $select[] = "GROUP_CONCAT(DISTINCT ".$this->getEntityTable().".currency) AS currency";
      $this->_columnHeaders['currency']['title'] = 'Currency';
      $this->_columnHeaders['currency']['type'] = CRM_Utils_Type::T_STRING;
      }
      //Monthly / Yerly report select clause
      if($this->isMonthlyYearlyReport()){
        if($this->getReportType() == 'monthly')
        {
          $select[] = "MONTH(".$this->getEntityTable().".`receive_date`) AS month";
          $this->_columnHeaders['month']['title'] = 'Month Name';
        }
        $select[] = "YEAR(".$this->getEntityTable().".`receive_date`) AS year";
        $this->_columnHeaders['year']['title'] = 'Year Name';
      }

      // Combine everything
      $this->_selectClauses = $select;
      $this->_select = $select;

    }


    public function buildGroupByQuery(){


    $groupBy = [];
     //Monthly / Yerly report group by clause
    if($this->isMonthlyYearlyReport()){
      if($this->getReportType() == 'monthly')
      {
        $groupBy[] = "MONTH(".$this->getEntityTable().".`receive_date`)";
      }
      $groupBy[] = "YEAR(".$this->getEntityTable().".`receive_date`)";
    }
      //columns and group by selection are always same that's why using columns here
    foreach($this->_columns as $fieldName => $nodata) {
      if($fieldName){
      $fieldName = ($fieldName == 'financial_type') ? $fieldName . '_id' : $fieldName;

      if ($fieldName == 'total_amount')
        continue;
      
      $columnInfo = $this->getFieldMapping($this->getEntity(), $fieldName);
      $groupBy[] = $columnInfo['table_name'] . "." .  $columnInfo['name'];
      }
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
            // assign order by fields which has section display checked
            if($orderBy['section'])
            $this->_orderByFields[$orderBy['column']] = $columnInfo['table_name'] . "." .  $columnInfo['name'];
          }
        }
      }
      //Monthly / Yerly report order by clause
      if($this->isMonthlyYearlyReport()){
        $orderBys[] = "YEAR(".$this->getEntityTable().".`receive_date`)";
        if($this->getReportType() == 'monthly')
        {
          $orderBys[] = "MONTH(".$this->getEntityTable().".`receive_date`)";
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
          case 'account_type': // Account Type
           $from[] = $this->fetchOptionLabel("financial_account_type","financial_account_type_id",$this->getEntityTable('financial_account'),$fieldName);
            break;
          case 'payment_instrument_id': // Account Type
            $from[] = $this->fetchOptionLabel("payment_instrument","payment_instrument_id",$this->getEntityTable(),$fieldName);
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
 
    //Added new parameter at the last to distinguish group and value alias in case of same tablename
    public function fetchOptionLabel($groupName,$fieldName,$tableName,$columnName){
      $tableName_group = $tableName.'_'.$columnName.'_group';
      $tableName_value = $tableName.'_'.$columnName.'_value';
      $optionValueLabel = " LEFT JOIN civicrm_option_group as ".$tableName_group." ON ".$tableName_group.".name = '".$groupName."' 
      LEFT JOIN civicrm_option_value as $tableName_value ON $tableName_value.option_group_id = $tableName_group.id 
      AND $tableName_value.value = ".$tableName.".".$fieldName;
      return $optionValueLabel;
    }
}

?>