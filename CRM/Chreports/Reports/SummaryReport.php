<?php
use CRM_Chreports_ExtensionUtil as E;
use CRM_Canadahelps_ExtensionUtils as EU;
class CRM_Chreports_Reports_SummaryReport extends CRM_Chreports_Reports_BaseReport {


    public function __construct( string $entity, $id = NULL, string $name ) {
        parent::__construct( $entity, $id, $name);
    }

    public function buildSelectQuery(){
      $select = [];
      $this->_columnHeaders = [];

      // Add selected columns to SELECT clause
      foreach($this->_columns as $fieldName => $nodata) {

        if ($fieldName) {
          // skip total amount as part of calculated fields
          if ($fieldName == 'total_contribution_sum')
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

      //Contribution Rentention Report
      //CRM-2157  
      if($this->isContribRetentionReport()){
        $retentionYearSatement = "YEAR(".$this->getEntityTable().".`receive_date`) AS year";
        $select[] = $retentionYearSatement;
        $this->_columnHeaders['year']['title'] = 'Year Name';
     //   $this->_statisticsCalculatedFields['year'] = ['title' =>$this->_columnHeaders['year']['title'],'select'=>['year'=> strstr($retentionYearSatement, 'AS year',true)]];
        
        $select[] = "COUNT(DISTINCT ".$this->getEntityTable().".`contact_id`) as all_donors";
        $this->_columnHeaders['all_donors']['title'] = 'All Donors';
        //Calculation for new donor
        $select[] = "COUNT(DISTINCT ".$this->getEntityTable().".`contact_id`) as new_donor";
        $this->_columnHeaders['new_donor']['title'] = 'New Donor';
        // //calculation for retained donors
        $select[] = "COUNT(DISTINCT future_contrib.`contact_id`) as retained_donors";
        $this->_columnHeaders['retained_donors']['title'] = 'Retained Donors';
        //calculation for retension rate

        $select[] = "ROUND(COUNT(DISTINCT future_contrib.`contact_id`) / COUNT(DISTINCT ".$this->getEntityTable().".`contact_id`) * 100, 2) as retention";
        $this->_columnHeaders['retention']['title'] = 'Retention Rate';
      }

      //Monthly / Yerly report select clause
      if($this->isPeriodicSummary()){
        if($this->hasMonthlyBreakdown()) {
          $select[] = "MONTH(".$this->getEntityTable().".`receive_date`) AS month";
          $this->_columnHeaders['month']['title'] = 'Month Name';
        }
        $select[] = "YEAR(".$this->getEntityTable().".`receive_date`) AS year";
        $this->_columnHeaders['year']['title'] = 'Year Name';
      }

      //fiscle year report
      if($this->isPeriodicDetailed()) {
        if($this->hasQuarterlyBreakdown()) {
          $select[] = "QUARTER(".$this->getEntityTable().".`receive_date`) AS quartername";
          $this->_columnHeaders['quartername']['title'] = 'Fiscal no.';
        }
      }
      
      // Add default fields such as total, sum and currency
      $contribCountStatement = "COUNT(".$this->getEntityTable().".id) AS total_count";
      $select[] = $contribCountStatement;
      $this->_columnHeaders['total_count']['title'] = 'Number of Contributions';
      $this->_columnHeaders['total_count']['type'] = CRM_Utils_Type::T_INT;
      $this->_calculatedFields['total_count']=[ 'total_count' => $contribCountStatement];
      if( preg_match('/(MIN|SUM|AVG|COUNT|MAX|MIN)/', $contribCountStatement )) {
        $this->_having['total_count'] =  strstr($contribCountStatement, 'AS total_count',true);
      }

      // Total Amount
      $totalAmountStatement = "SUM(".$this->getEntityTable('contribution').".`total_amount`) AS total_contribution_sum";
      $select[] = $totalAmountStatement;
      $this->_columnHeaders['total_contribution_sum']['title'] = 'Total Amount';
      $this->_columnHeaders['total_contribution_sum']['type'] = CRM_Utils_Type::T_MONEY;
      $this->_calculatedFields['total_contribution_sum']=[ 'total_contribution_sum' => $totalAmountStatement];
      if( preg_match('/(MIN|SUM|AVG|COUNT|MAX|MIN)/', $totalAmountStatement )) {
        $this->_having['total_contribution_sum'] = strstr($totalAmountStatement, 'AS total_contribution_sum',true);
      }
      
      $select[] = "GROUP_CONCAT(DISTINCT ".$this->getEntityTable().".currency) AS currency";
      $this->_columnHeaders['currency']['title'] = 'Currency';
      $this->_columnHeaders['currency']['type'] = CRM_Utils_Type::T_STRING;
      //function to rearrange columnheader for display
      $this->rearrangeColumnHeaders($this->_columnHeaders);
      // Combine everything
      $this->_selectClauses = $select;
      $this->_select = $select;

    }

    public function addCalculatedFieldstoSelect(&$select) {
    
    }


    public function buildGroupByQuery(){
      $groupBy = [];
      //CRM-2157
      if($this->isContribRetentionReport()) {
        $groupBy[] = "year";
      }
      //Monthly / Yerly report group by clause
      if($this->isPeriodicSummary()){
        if($this->hasMonthlyBreakdown()) {
          $groupBy[] = "MONTH(".$this->getEntityTable().".`receive_date`)";
        }
        $groupBy[] = "YEAR(".$this->getEntityTable().".`receive_date`)";
      }
        //columns and group by selection are always same that's why using columns here
      foreach($this->_columns as $fieldName => $nodata) {
        if($fieldName) {
          $fieldName = ($fieldName == 'financial_type') ? $fieldName . '_id' : $fieldName;
          if ($fieldName == 'total_contribution_sum')
          continue;
          $groupBy[] =  $this->getEntityClauseFromField($fieldName);
        }
      } 

      if($this->isPeriodicDetailed()) {
        unset($groupBy);
        if($this->hasMonthlyBreakdown()) {
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

    public function buildOrderByQuery() {

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
      if($this->isPeriodicSummary()) {
        $orderBys[] = "YEAR(".$this->getEntityTable().".`receive_date`)";
        if($this->hasMonthlyBreakdown()) {
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