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

        if ($fieldName) {
          // skip total amount as part of calculated fields
          if ($fieldName == 'total_amount')
            continue;

          $columnInfo = $this->getFieldMapping($this->getEntityTableFromField($fieldName), $fieldName);
          //common select clause
          $selectStatement = $this->getCommonSelectClause($fieldName);
          $select[] = $selectStatement . " AS $fieldName";
          //Adding columns to _columnHeaders for display purpose
          $this->_columnHeaders[$fieldName]['title'] = $columnInfo['title'];
          $this->_columnHeaders[$fieldName]['type'] = $columnInfo['type'];
        }
      }  

      // Calculated fields
      // @todo move code below here
      $this->addCalculatedFieldstoSelect($select);

      //Monthly / Yerly report select clause
      if($this->isPeriodicSummary()){
        if($this->hasMonthlyBreakdown())
        {
          $select[] = "MONTH(".$this->getEntityTable().".`receive_date`) AS month";
          $this->_columnHeaders['month']['title'] = 'Month Name';
        }
        $select[] = "YEAR(".$this->getEntityTable().".`receive_date`) AS year";
        $this->_columnHeaders['year']['title'] = 'Year Name';
      }

      //fiscle year report
      if($this->isPeriodicDetailed()){
        if($this->hasMonthlyBreakdown())
        {
          $select[] = "MONTH(".$this->getEntityTable().".`receive_date`) AS monthIndex";
          $this->_columnHeaders['monthIndex']['title'] = '';

          $select[] = "MONTHNAME(".$this->getEntityTable().".`receive_date`) AS monthname";
          $this->_columnHeaders['monthname']['title'] = '';
        }else{
          $select[] = "QUARTER(".$this->getEntityTable().".`receive_date`) AS quartername";
          $this->_columnHeaders['quartername']['title'] = '';
        }
      }
      
      // Add default fields such as total, sum and currency
      $contribCountStatement = "COUNT(".$this->getEntityTable().".id) AS count";
      $select[] = $contribCountStatement;
      $this->_columnHeaders['count']['title'] = 'Number of Contributions';
      $this->_columnHeaders['count']['type'] = CRM_Utils_Type::T_INT;
      $this->_calculatedFields['count']=[ 'count' => $contribCountStatement];

      // Total Amount
      $totalAmountStatement = "SUM(".$this->getEntityTable('contribution').".`total_amount`) AS total_amount";
      $select[] = $totalAmountStatement;
      $this->_columnHeaders['total_amount']['title'] = 'Total Amount';
      $this->_columnHeaders['total_amount']['type'] = CRM_Utils_Type::T_MONEY;
      $this->_calculatedFields['total_amount']=[ 'total_amount' => $totalAmountStatement];
      
      $select[] = "GROUP_CONCAT(DISTINCT ".$this->getEntityTable().".currency) AS currency";
      $this->_columnHeaders['currency']['title'] = 'Currency';
      $this->_columnHeaders['currency']['type'] = CRM_Utils_Type::T_STRING;

      // Combine everything
      $this->_selectClauses = $select;
      $this->_select = $select;

    }

    public function addCalculatedFieldstoSelect(&$select) {
    
    }


    public function buildGroupByQuery(){


    $groupBy = [];
     //Monthly / Yerly report group by clause
    if($this->isPeriodicSummary()){
      if($this->hasMonthlyBreakdown())
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
     
      $groupBy[] =  $this->getEntityClauseFromField($fieldName);
      
      }
    } 

    if($this->isPeriodicDetailed()){
      unset($groupBy);
      if($this->hasMonthlyBreakdown())
      {
      $groupBy[] = "EXTRACT(YEAR_MONTH FROM ".$this->getEntityTable().".`receive_date`)";
      }else{
        $groupBy[] = "QUARTER(".$this->getEntityTable().".`receive_date`)";
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
            $orderBys[] = $this->getEntityClauseFromField($fieldName)." ".$orderBy['order'];
            $this->_orderByFieldsFrom[$orderBy['column']] = true;
            // assign order by fields which has section display checked
            if($orderBy['section']){
            $this->_orderByFields[$orderBy['column']] = $this->getCommonSelectClause($fieldName);
            }
          }
        }
      }
      //Monthly / Yerly report order by clause
      if($this->isPeriodicSummary()){
        $orderBys[] = "YEAR(".$this->getEntityTable().".`receive_date`)";
        if($this->hasMonthlyBreakdown())
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
      $this->getDefaultFromClause($from);

      //common from clause for summary and detailed reports
      $this->getCommonFromClause($from);      
      $this->_from = "FROM " . implode(' ', $from) . " ";
    
    } 
}

?>