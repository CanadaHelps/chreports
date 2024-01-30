<?php
use CRM_Chreports_ExtensionUtil as E;
use CRM_Canadahelps_ExtensionUtils as EU;
class CRM_Chreports_Reports_DetailReport extends CRM_Chreports_Reports_BaseReport {


    public function __construct( string $entity, $id = NULL, string $name ) {
        parent::__construct( $entity, $id, $name);
    }

    public function buildSelectQuery(){

      $select = [];
      $this->_columnHeaders = [];
      
      // Add selected columns to SELECT clause
      foreach($this->_columns as $fieldName => $nodata) {
       
        $fieldInfo = $this->getFieldInfo($fieldName);
        $columnInfo = $this->getFieldMapping($this->getEntityTableFromField($fieldName), $fieldName);

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

      }
      
      //Contribution Table ID details
      $select[] = "(".$this->getEntityTable().".id) as civicrm_contribution_contribution_id";
      $this->_columnHeaders['civicrm_contribution_contribution_id']['title'] = 'contribution_id';
      $this->_columnHeaders['civicrm_contribution_contribution_id']['type'] = CRM_Utils_Type::T_INT;

      //Repeat contribution report 
      if((parent::isComparisonReport()))
      {
        $repeatContribPercentstatement = "CASE 
        WHEN (COUNT(civicrm_contribution_secondset.id) = 0) THEN 'Skipped Donation'
        WHEN (COUNT(civicrm_contribution_primaryset.id) = 0) THEN 'New Donor'
        ELSE 
        CONCAT(ROUND(((SUM(civicrm_contribution_secondset.total_amount) -SUM(civicrm_contribution_primaryset.total_amount))/ SUM(civicrm_contribution_primaryset.total_amount))*100, 2),'%')
    END AS per_change";
        $select[] = $repeatContribPercentstatement;
        $this->_columnHeaders['per_change']['title'] = '% Change';
        $this->_columnHeaders['per_change']['type'] = CRM_Utils_Type::T_STRING;
        $this->_calculatedFields['per_change']= [ 'per_change' => $repeatContribPercentstatement];
        $this->_statisticsCalculatedFields['per_change'] = ['title' =>$this->_columnHeaders['per_change']['title'],'select'=>['per_change'=> strstr($repeatContribPercentstatement, 'AS per_change',true)]];
        //For adding default 'percentage change' calculated field in filters, so need to add under having clause
        if( preg_match('/(MIN|SUM|AVG|COUNT|MAX|MIN)/', $repeatContribPercentstatement )) {
          $this->_having['per_change'] = strstr($repeatContribPercentstatement, 'AS per_change',true);
        }
      }
      
      //contact Table ID details
      $select[] = "(".$this->getEntityTable('contact').".id) as civicrm_contact_id";
      $this->_columnHeaders['civicrm_contact_id']['title'] = 'contact_id';
      $this->_columnHeaders['civicrm_contact_id']['type'] = CRM_Utils_Type::T_INT;

      if($this->isTopDonorReport())
      {
      $topDonorContribCountStatement = "COUNT(".$this->getEntityTable('contribution').".id) AS count";
      $select[] = $topDonorContribCountStatement;
      $this->_columnHeaders['count']['title'] = 'Donations';
      $this->_columnHeaders['count']['type'] = CRM_Utils_Type::T_INT;
      $this->_calculatedFields['count'] = [ 'count' => $topDonorContribCountStatement];

      $topDonorTotalAmountStatement = "SUM(".$this->getEntityTable('contribution').".`total_amount`) AS total_amount";
      $select[] = $topDonorTotalAmountStatement;
      $this->_columnHeaders['total_amount']['title'] = 'Amount';
      $this->_columnHeaders['total_amount']['type'] = CRM_Utils_Type::T_MONEY;
      $this->_calculatedFields['total_amount']= [ 'total_amount' => $topDonorTotalAmountStatement];

      $topDonorAvgAmountStatement = "ROUND(AVG(".$this->getEntityTable('contribution').".`total_amount`),2) AS avg_amount";
      $select[] = $topDonorAvgAmountStatement;
      $this->_columnHeaders['avg_amount']['title'] = 'Average';
      $this->_columnHeaders['avg_amount']['type'] = CRM_Utils_Type::T_MONEY;
      $this->_calculatedFields['avg_amount']= [ 'avg_amount' => $topDonorAvgAmountStatement];

      $select[] = "GROUP_CONCAT(DISTINCT ".$this->getEntityTable('contribution').".currency) AS currency";
      $this->_columnHeaders['currency']['title'] = 'Currency';
      $this->_columnHeaders['currency']['type'] = CRM_Utils_Type::T_STRING;

      }

      if($this->isLYBNTSYBNTReport())
      {
        $select[] = "MAX(".$this->getEntityTable('contribution').".receive_date) as lastContributionTime";
      }
      //function to rearrange columnheader for display
      $this->rearrangeColumnHeaders($this->_columnHeaders);
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
            $fieldName => "SUM((CASE WHEN 
              YEAR(".$this->getEntityTable('contribution').".receive_date) = YEAR(NOW()) AND MONTH(".$this->getEntityTable('contribution').".receive_date) = MONTH(NOW()) 
              THEN ".$this->getEntityTable('contribution').".total_amount ELSE 0
              END))"
          ];
          break;
        case 'completed_contributions':
          $statements = [ 
            $fieldName => "(COUNT(CASE WHEN ".$this->getEntityTable('contribution').".`contribution_status_id` = 1 THEN 1 END))"
          ];
          break;
        case 'last_month_amount':
          $statements = [ 
            $fieldName => "SUM((CASE WHEN 
          YEAR(".$this->getEntityTable('contribution').".receive_date) = YEAR((SELECT MAX(".$this->getEntityTable('contribution').".receive_date) FROM ".$this->getEntityTable('contribution').")) AND MONTH(".$this->getEntityTable('contribution').".receive_date) = MONTH((SELECT MAX(".$this->getEntityTable('contribution').".receive_date) FROM ".$this->getEntityTable('contribution').")) THEN ".$this->getEntityTable('contribution').".total_amount ELSE 0
          END))"];
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
          break;
        case 'age':
          $statements = [ 
            $fieldName => "TIMESTAMPDIFF(YEAR, civicrm_contact.birth_date, CURDATE())"
          ];
          break;
      }
      if (count($statements) > 0) {
        $this->_calculatedFields[$fieldName] = $statements;
        // for staistics calculated fields
        $this->_statisticsCalculatedFields[$fieldName] = ['title' =>$_columnHeader[$fieldName]['title'],'select'=>$statements];
        foreach($statements as $fieldName => $statement) {
          $select[] = $statement.' AS '.$fieldName;
          if ( preg_match('/(MIN|SUM|AVG|COUNT|MAX|MIN)/', $statement )) {
            $this->_having[$fieldName] = $statement;
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
      foreach($this->_filters as $fieldName => $fieldInfo) {
        switch ($fieldName) {
        case 'yid': // fund_13
          break;
        }
      }
    if (!empty($groupBy)) {
      $this->_groupBy = ' GROUP BY ' . implode(', ', $groupBy);
    }


    } 

    public function buildOrderByQuery(){
      $orderBys = [];
      if (!empty($this->_params['order_bys']) && is_array($this->_params['order_bys'])) 
      {
      //Conditional check to apply following order by clause only if exposed_id column field is checked for lybnt , sybunt report
      if(($this->getReportName() == 'contrib_sybunt' || $this->getReportName() == 'contrib_lybunt') && in_array('exposed_id',array_keys($this->_columns)))
      {
        $orderBys[] = "ISNULL(exposed_id)";
      }
        foreach ($this->_params['order_bys'] as $orderBy) {
          //if order by option is selected on the report
          if($orderBy['column'] != '-')
          {
            $fieldName =  $orderBy['column'];
            $fieldInfo = $this->getFieldInfo($orderBy['column']);
            $isCalculatedField = isset($fieldInfo['calculated_field']) && $fieldInfo['calculated_field'] === true;

            $orderBys[] = ($isCalculatedField) ? $fieldName." ".$orderBy['order'] : $this->getEntityClauseFromField($fieldName)." ".$orderBy['order'];
            $this->_orderByFieldsFrom[$orderBy['column']] = true;
  
            //for order by calculated fields, alias calculated field we need to include original select calculated statement 
            if($isCalculatedField && !in_array($fieldName,array_keys($this->_calculatedFields)) ) {
              $this->addCalculatedFieldstoSelect($this->_select,$fieldName,$this->_columnHeaders);
            }

            // assign order by fields which has section display checked
            if($orderBy['section']){
            $this->_orderByFields[$orderBy['column']] = ($isCalculatedField) ? $this->getCalculatedFieldStatement($orderBy['column']) : $this->getCommonSelectClause($fieldName);
            }
          }
        }

        if($this->isTopDonorReport())
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
    
      if(parent::isRecurringContributionReport()){
        list($tablename,$columnName) = $this->getCustomTableNameColumnName('SG_Flag');
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

}

?>