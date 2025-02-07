<?php
use CRM_Canadahelps_ExtensionUtils as EU;
use CRM_Chreports_ExtensionUtil as E;
class CRM_Chreports_Reports_BaseReport extends CRM_Chreports_Reports_ReportConfiguration {

    private $_name;
    private $_pagination = FALSE;

    private $_entity  = 'contribution';
    protected $_columns = [];
    protected $_mapping = [];
    public $_params = [];

    
    protected $_select = NULL;
    protected $_selectClauses = [];
    protected $_columnHeaders = [];

    protected $_from;
    protected $_fromEntity = [];

    protected $_groupBy = '';

    protected $_orderBy = NULL;
    protected $_orderByFields = [];
    protected $_orderByFieldsFrom = [];
    protected $_calculatedFields = [];

    protected $_preselected_filter = [];
    protected $_statisticsCalculatedFields = [];

    protected $_where = NULL;
    protected $_having = [];
    protected $_havingClause= NULL;
    protected $_limit = NULL;
   
    public $_filters = NULL;

    
    // Default filters for report
    protected $_defaultFilters = 
    [
        "receive_date",
        "receipt_date",
        "contribution_status_id",
        "contribution_page_id", //campaign
        "financial_type_id", //fund
        "contribution_recur_id",
        "total_amount",
        "non_deductible_amount",
        "campaign_id", //campaign group
        "card_type_id",
        "batch_id",
        "tagid",
        "gid",
        "total_contribution_sum",
        "total_count",
        "total_avg",
        "contribution_source",
        "payment_instrument_id",//payment method
        "campaign_type",
        "ch_fund"//custom
    ];

    public function __construct( string $entity, $id = NULL, string $name ) {
        parent::__construct($id, $name);
        $this->_name = $name;
        $this->_entity = strtolower($entity);
    }

    //set entity value externally
    public function setEntity(string $entity) {
        $this->_entity = strtolower($entity);
    }

    /**
     * 
     * Returns the main entity for the report
     * As per the configuration file
     * 
     * @return string
     */
    public function getEntity(): string {
        return $this->_entity;
    }
    
    
    /**
     * 
     * Returns the database table name
     *
     * @param string $entity Entity name. Default is main entity as defined in configuration file.
     * @return string
     */
    public function getEntityTable(string $entity = null): string {
        $entity = ($entity != NULL) ? $entity : $this->getEntity();
        
        // skip alias tables and other extensions related tables
        if (in_array($entity, ["financial_account_debit", "financial_account_credit","contact_owner"]))
            return $entity;

        return "civicrm_" . $entity;
    }
    
    /**
     * 
     * Returns a list of field names for the columns
     * based on configuration file
     *
     * @return array
     */
    public function getColumnsFromConfig(): array {
        return array_keys($this->_settings['fields']);
    }

    // extract reporting filter fields from JSON file
    // TODO: why do we have both getReportingFilters and getFilters
    public function getReportingFilters(): array {
        $filterValues = [];
        if ($this->_settings['use_default_filters'] == TRUE) {
            $filterValues = array_fill_keys($this->_defaultFilters, []);
        }
        if (count($this->_settings['filters']) > 0) {
            //$filterValues = array_merge($filterValues, array_fill_keys($this->_settings['filters'], []));
            $filterValues = array_merge($filterValues,$this->_settings['filters']);
        }
        return $filterValues;
    }


    /**
     * Get default column(s) from configuration
     * 
     * @return array
     */
    public function getDefaultColumns(): array {
        // TODO: use array_search 
        $defaultFields = [];
        foreach($this->_settings['fields'] as $fieldKey => $value) {
            if ( isset($this->_settings['fields'][$fieldKey]['default']) ){
                $defaultFields[] = $fieldKey;
            }
        }
        return $defaultFields;
    }

    public function getPreSetFilterValues(): array {
        return $this->_preselected_filter;
    }
    // manage mapping fields from extendedSummary
    public function setFieldsMapping(array $mapping) {
        $this->_mapping = $mapping;
    }

    public function getAllFieldsMapping(): array {
        return $this->_mapping;
    }

    // manage columns from extendedSummary
    public function setColumns(array $fields) {
        $this->_columns = $fields;
    }

    public function getColumns(): array {
        return $this->_columns;
    }

    public function getHavingStatements(): array {
        return $this->_having;
    }

    // manage selected params from extendedSummary
    public function getFormParams(): array {
        return $this->_params;
    }
    public function setFormParams(array $params) {
        $this->_params = $params;
    }

    // manage select clause variables
    public function getSelect(): string {
        $selectStatement = "SELECT " . implode(', ', $this->_select) . " ";
        if ($this->hasPagination()) {
          $selectStatement = preg_replace('/SELECT(\s+SQL_CALC_FOUND_ROWS)?\s+/i', 'SELECT SQL_CALC_FOUND_ROWS ', $selectStatement,1);
        }
        return $selectStatement;
    }

    public function getSelectClauses(): array {
        return $this->_selectClauses;
    }

    public function getColumnHeaders(): array {
        return $this->_columnHeaders;
    }
    // manage from clause variable
    public function getFrom(): string {
        return $this->_from;
    }
    // manage group by clause variable
    public function getGroupBy(): string {
        return $this->_groupBy;
    }
    // manage order by clause variable
    public function getOrderBy() {
        return $this->_orderBy;
    }
    // to access where clause variable
    public function setWhere(string $where) {
        $this->_where = $where;
    }
    // to access having clause variable
    public function setHaving(string $having) {
        $this->_havingClause = $having;
    }

    /**
     * Set pagination mode
     * 
     * @param bool $pagination
     * @return void
     */
    public function setPagination(bool $pagination) {
        $this->_pagination = $pagination;
    }

    /**
     * Whether the results should be paginated
     * 
     * @return bool
     */
    public function hasPagination() : bool {
        return $this->_pagination;
    }
    //manage limit from extendedClasses
    public function getLimit() {
        return $this->_limit;
    }
     // manage columns from extendedSummary
     public function setLimit( $limit = NULL) {
        $this->_limit = $limit;
    }


    //get type of the report template from settings
    public function getReportTemplate(): string {
        return $this->_settings['template'];
    }
    //get type of the report from settings
    public function getReportType(): string {
        return $this->_settings['type'];
    }
    //get name of the report
    public function getReportName(): string {
        return $this->_settings['name'];
    }

    public function isPeriodicDetailed(): bool {
        $status = ($this->_settings['template'] == 'chreports/contrib_period_detailed') ? true : false;
        return $status;
    }

    public function isPeriodicSummary (): bool {
        if(in_array($this->_settings['template'], ['chreports/contrib_summary_monthly' , 'chreports/contrib_summary_yearly']))
        {
            $this->_limit = '';
            return true; 
        }
        return false;
    }

    //CRM-2157
    //Retrieve contribution retention Report
    public function isContribRetentionReport(): bool {
        return $this->_settings['template'] == 'chreports/contrib_retention';
    }

    //Retrieve Repeat Contributions Report
    public function isComparisonReport(): bool {
        return $this->_settings['template'] == 'chreports/contrib_period_compare';
    }
    //check if report is opportunity report
    public function isOpportunityReport(): bool {
        return $this->_settings['template'] == 'chreports/opportunity_detailed';
    }
    //check if report is opportunity report
    public function isLYBNTSYBNTReport(): bool {
        return in_array($this->_settings['template'], ['chreports/contrib_lybunt' , 'chreports/contrib_sybunt']);
    }

    //check if report is TopDonor report
    public function isTopDonorReport(): bool {
        return $this->_settings['template'] == 'chreports/contact_top_donors';
    }
 
    //Retrieve GL Accountand Payment Method Reconciliation Report
    public function isGLAccountandPaymentMethodReconciliationReport()
    {
        return $this->_settings['template'] == 'chreports/contrib_glaccount';
    }
   
    //Retrieve Recurring Contribution Report
    public function isRecurringContributionReport()
    {
        return $this->_settings['template'] == 'chreports/contrib_recurring';
    }

    public function hasMonthlyBreakdown (): bool {
        return $this->_settings['period'] == 'monthly';
    }

    public function hasYearlyBreakdown  (): bool {
        return $this->_settings['period'] == 'yearly';
    }

    public function hasQuarterlyBreakdown  (): bool {
        return $this->_settings['period'] == 'quarterly';
    }
    
    //manage filters variable
    public function getFilters(): array {
        return $this->_filters;
    }
    //to access calculated fields list for extended detail report
    public function getCalculatedFieldsList(): array {
        return $this->_calculatedFields;
    }
    public function setFilters() {

        $filters = [];

        // Get actual fields used for filters
        $filterNames = $this->getFieldNamesForFilters();
        // Get fields info
        foreach ($this->getAllFieldsMapping() as $entityTable => $entityData) {
            if (array_key_exists('filters', $entityData)) {
                foreach ($entityData['filters'] as $fieldName => $field) {
                    if ( in_array($fieldName, $filterNames) ) {                                                                                                                                                                                                                                                           
                        //get all fieldinfo
                        //check if field name do not contain 'id' string and operator type is 'OP_MULTISELECT' add following
                        //condition
                        $fieldInfo = $this->getFieldInfo($fieldName);
                        if( !preg_match('/_id$/', $fieldInfo['field_name']) && isset($fieldInfo['table_alias']))
                        {
                            $field['dbAlias'] = $this->getEntityClauseFromField($fieldName, $field['operatorType'] == CRM_Report_Form::OP_MULTISELECT);
                        }else{
                            $field['dbAlias'] = $this->getEntityClauseFromField($fieldName);
                        }
                        $filters[$fieldName] = $field;
                    }
                }
            }
        }
        $this->_filters = $filters;
    }


    //to identify FieldNames from selected filters
    private function getFieldNamesForFilters() {
        $filterNames = [];
        $params = $this->getFormParams();
        foreach ($params as $key => $value) {
            preg_match('/(.+)_(op|from|relative)$/i', $key, $matches);
            if ( count($matches) > 0 ) {
                $fieldName = $matches[1];
                if ($matches[2] == "op") {
                    //to consider fields with filter value 'nll' and 'nnll' option value
                    if (in_array($value, ['nll', 'nnll'])) {
                        $filterNames[] = $fieldName;
                    }
                    // IN (value1, value 2)
                    if ( is_array($params[$fieldName.'_value']) && count($params[$fieldName.'_value']) > 0) {
                        $filterNames[] = $fieldName;
                    // Regular clause
                    } else if (!empty($params[$fieldName.'_value'])) {
                        $filterNames[] = $fieldName;
                    }
                    // Date Range
                } else if ($matches[2] == "from" && !empty($value)) {
                $filterNames[] = $matches[1];

                // Relative Date
                } else if ($matches[2] == "relative" && !empty($value)) {
                $filterNames[] = $matches[1];
                }
            }
        }
        return $filterNames;
    }

    /**
     * Create Filter Params and preset values with JSON config file
     *
     * @return array
     */
    public function createCustomFilterParams(): array {
        $filterParams = [];
        $filterPresets = $this->_preselected_filter;

        foreach ($filterPresets as $fieldName => $data) {
            // Handle the 'null' and 'not null' cases
            if (in_array($data, ['nll', 'nnll'])) {
                $filterParams[$fieldName.'_op'] = $data;
            } elseif (is_array($data)) {
                // Handle the 'bw' and date range cases
                if (isset($data['bw'])) {
                    $filterParams[$fieldName . '_op'] = 'bw';
                    $filterParams[$fieldName . '_min'] = $data['bw']['min'];
                    $filterParams[$fieldName . '_max'] = $data['bw']['max'];
                } elseif (isset($data['from']) && isset($data['to'])) {
                    $filterParams[$fieldName . '_relative'] = 0;
                    $filterParams[$fieldName . '_from'] = $data['from'];
                    $filterParams[$fieldName . '_to'] = $data['to'];
                } elseif (isset($data['relative'])) {
                    $filterParams[$fieldName . '_relative'] = $data['relative'];
                } else {
                    // Handle other cases
                    foreach ($data as $operation => $value) {
                        $filterParams[$fieldName . '_op'] = $operation;
                        $filterParams[$fieldName . '_value'] = $value;
                    }
                }
            }
        }
       
        return $filterParams;
    }


    // Get filter mapping for the custom jSON file
    public function getCustomFilterValues(): array {
        $filterNames = [];
        $params = $this->getFormParams();
        foreach ($params as $key => $value) {
            // Check if the current key ends with "op," "relative," "from," or "to"
            preg_match('/(.+)_(op|from|relative)$/i', $key, $matches);
            if (empty($matches)) {
                continue; // Skip keys that don't match the pattern
            }

            $fieldName = $matches[1];
            $operation = $params[$matches[0]];

            switch ($operation) {
                case 'bw':
                    $filterNames[$fieldName][$operation] = [
                        'min' => $params[$fieldName.'_min'],
                        'max' => $params[$fieldName.'_max']
                    ];
                    break;

                case 'nll':
                    $filterNames[$fieldName] = ['nll' => (int) 1];
                    // In case of relative, change the format that matches the base configuration file
                    if(isset($matches[2])) {
                        if($matches[2] == 'relative') {
                            $filterNames[$fieldName] = ['relative' => 'nll'];
                        }
                    }
                    break;

                case 'nnll':
                    $filterNames[$fieldName] = ['nnll' => (int) 1];
                    if(isset($matches[2])) {
                        if($matches[2] == 'relative') {
                            $filterNames[$fieldName] = ['relative' => 'nnll'];
                        }
                    }
                    break;

                default:
                    if ($matches[2] == 'from' && $params[$fieldName.'_from']) {
                        $filterNames[$fieldName] = [
                            'from' => $params[$fieldName.'_from'],
                            'to' => $params[$fieldName.'_to']
                        ];
                    } elseif ($matches[2] == 'relative' && $params[$matches[0]]) {
                        $filterNames[$fieldName]['relative'] = $params[$matches[0]];
                    } elseif(!empty($params[$fieldName.'_value'])) {
                        $filterNames[$matches[1]][$params[$matches[0]]] = $params[$fieldName.'_value'];
                    }
                    break;
            }
        }
        return $filterNames;
    }

    
    //get field details from array
    public function getFieldMapping(string $fieldEntity, string $fieldName): array {
        $entityTable = ($fieldEntity != NULL) ? $fieldEntity : $this->getEntityTable();
            
        if ( !array_key_exists($entityTable, $this->_mapping) 
            || !array_key_exists($fieldName, $this->_mapping[$entityTable]['fields']) ) {
                return ['name' => $fieldName, 'title' => $fieldName, 'table_name' => $entityTable];
        }
        
        return $this->_mapping[$entityTable]['fields'][$fieldName];
    }

    public function buildSelectQuery() {
    }

    public function buildFromQuery() {
    }

    public function buildGroupByQuery() {
    }

    public function buildOrderByQuery() {
    }


    /**
     * Updates the search form of the report based on configuration
     * 
     * @param array  $var Array used to show columns.
     * @return void
     */
    public function setFormOptions(&$var) {
        // Set main entity
        $this->setEntity($this->_settings['entity']);

        // Columns + Grouping + Sorting
        $this->setFormColumnOptions($var);

        // Filters
        $this->setFormFilterOptions($var);
    }

    private function setFormColumnOptions(&$var) {

        // clear var completely to get blank slate
        $var = [];

        foreach ($this->getAllColumns() as $fieldName => $fieldInfo) {
            $fieldInfo = array_merge( $fieldInfo, $this->getFieldInfo($fieldName) );
            // field not found
            if ( isset($fieldInfo['error']) ) {
                continue;
            }
            if (isset($fieldInfo['custom'])) {
                $entityName = EU::getTableNameByName($fieldInfo['group_name']);
            } else {
                $entityName = isset($fieldInfo['entity'])? $this->getEntityTable($fieldInfo['entity']): $this->getEntityTable();
            }
            
            $actualFieldName = ($fieldInfo['field_name']) ?? $fieldName;
            $filterType = $this->getFilterType($fieldName);
            //manage title
            $this->fixFieldTitle($fieldName,$fieldInfo["title"]);
            $var[$entityName]['fields'][$fieldName] = [
                "title" => $fieldInfo["title"],
                "default" => ( (isset($fieldInfo["default"]) && $fieldInfo["default"] === true) 
                || (isset($fieldInfo["selected"]) && $fieldInfo["selected"] === true) ),
                "type" => $filterType['type'],
                "custom_alias" =>  $entityName.'_'.$fieldName,
            ];
            //set order by fields //sort by
            if(isset($this->_settings['orderByClause']) && $this->_settings['orderByClause'] === false){
                unset($var[$entityName]['order_bys'][$fieldName]);
            }else{
                $var[$entityName]['order_bys'][$fieldName] = [
                    'title' => $fieldInfo["title"]
                ];
            }

            //set group by
            if(isset($this->_settings['groupByClause']) && $this->_settings['groupByClause'] === false){
                unset($var[$entityName]['group_bys'][$fieldName]);
            }else{
                $var[$entityName]['group_bys'][$fieldName] = [
                    'title' => $fieldInfo["title"],
                    "default" => ( (isset($fieldInfo["default"]) && $fieldInfo["default"] === true) 
                        || (isset($fieldInfo["selected"]) && $fieldInfo["selected"] === true) ),
                ];
            }

        }

    }
    // fiscal and quarter report using same field with different title
    private function fixFieldTitle(string $fieldName, &$title) {
        switch ($fieldName) {
            case 'receive_date_start':
                $title = ($this->hasQuarterlyBreakdown())? ts('Quarter') :$title;
                break;
            default:
                $title;
        }
    }
    
    private function setFormFilterOptions(&$var) {

       
        foreach ($this->getReportingFilters() as $fieldName => $fieldInfo) {

            //check if filter value needs to be pre set
            if(count($fieldInfo) > 0) { 
                // For "This year" filter set value to current year as pre set for LYBNT, SYBNT Report 
                if($fieldName === 'yid'){
                    $currentYearIndex = array_search('current_year', $fieldInfo);
                    if($currentYearIndex !== false)
                     $fieldInfo[$currentYearIndex] = date("Y");
                 }
                $this->_preselected_filter[$fieldName] = $fieldInfo;
            }
            $fieldInfo = array_merge( $fieldInfo, $this->getFieldInfo($fieldName) );
            // field not found
            if ( isset($fieldInfo['error']) ) {
                continue;
            }

            // custom fields
            if (isset($fieldInfo['custom'])) {
                $entityName = EU::getTableNameByName($fieldInfo['group_name']);
            } else {
                $entityName = isset($fieldInfo['entity'])? $this->getEntityTable($fieldInfo['entity']): $this->getEntityTable();
            }

            $actualFieldName = ($fieldInfo['field_name']) ?? $fieldName;
            $filterType = $this->getFilterType($fieldName);
            $var[$entityName]['filters'][$fieldName] = [
                "name" => $actualFieldName,
                "title" => $fieldInfo["title"],
                "default" => $fieldInfo["default_value"] ?? '',
                "dataType" => $filterType["dataType"],
                "htmlType" => $filterType["htmlType"],
                "type" => $filterType["type"],
                "column_name" => $this->getEntityField($fieldName)
            ];

            // set operator type
            $var[$entityName]['filters'][$fieldName]["operatorType"] = $this->getOperatorType($fieldName);
            if ( isset($fieldInfo['options']) && $fieldInfo['options'] === true ) {
                $var[$entityName]['filters'][$fieldName]["options"] = $this->getFilterOptions($fieldName);
            }


        }
       
    }


    /**
     * Alter the column headers for display
     * 
     * @param array  $var Array used to show columns.
     * @return void
     */
    public function alterColumnHeadersForDisplay(&$var, &$columnHeaders ) { 
        //define calculatedFields array Key values
        $calculatedFieldsKeyVal = array_keys($this->_calculatedFields);
        //define columns key values
        $columnKeyVal = array_keys($this->getColumns());
        //CRM-1878-Calculated and money type field should be right align , all other fields should be left align
        foreach($this->_calculatedFields as $fieldName => $value) {
            if(!in_array($columnHeaders[$fieldName]['type'],[CRM_Utils_Type::T_MONEY,CRM_Utils_Type::T_INT,CRM_Utils_TYPE::T_DATE + CRM_Utils_Type::T_TIME])) {
                $columnHeaders[$fieldName] = ['title' => $columnHeaders[$fieldName]['title'],'type'=> CRM_Utils_Type::T_INT];
            }
        }
        foreach ($columnHeaders as $fieldName => $value) {
            //for Comparison report update column title based on date range selection
            if($this->isComparisonReport()) {
                switch ($fieldName) {
                    case 'range_one_stat':
                    case 'range_two_stat':
                        $filterFieldName = 'repeat_contri_initial_date_range';
                        if($fieldName === 'range_two_stat') {
                            $filterFieldName = str_replace("initial", "second",  $filterFieldName);
                        }
                        $columnHeaders[$fieldName] = ['title' => '-','type'=> $value['type']];
                        if (in_array($filterFieldName,$this->getFieldNamesForFilters())){
                            list($fromDate, $toDate) = CRM_Utils_Date::getFromTo(CRM_Utils_Array::value($filterFieldName."_relative", $this->_params), 
                            CRM_Utils_Array::value($filterFieldName."_from", $this->_params),
                            CRM_Utils_Array::value($filterFieldName."_to", $this->_params));

                            $fromDate = CRM_Utils_Date::customFormat($fromDate, NULL, array('d'));
                            $toDate = CRM_Utils_Date::customFormat($toDate, NULL, array('d'));
                            $columnHeaders[$fieldName] = ['title' => $fromDate.'-'.$toDate,'type'=> $value['type']];
                        }
                        break;
                }
            }
            $fieldInfo = $this->getFieldInfo($fieldName);
            if(!in_array($fieldName,$calculatedFieldsKeyVal) && !in_array($value['type'],[CRM_Utils_Type::T_MONEY,CRM_Utils_Type::T_INT,CRM_Utils_TYPE::T_DATE + CRM_Utils_Type::T_TIME])) {
                $columnHeaders[$fieldName] = ['title' => $value['title'],'type'=> CRM_Utils_Type::T_STRING];
            }
            elseif(!in_array($fieldName,$calculatedFieldsKeyVal) 
                && in_array($value['type'],[CRM_Utils_Type::T_INT]) 
                && ( isset($fieldInfo['select_name']) && ($fieldInfo['select_name'] === 'option_value' || !preg_match('/id$/', $fieldInfo['select_name']) )
            )) {
                $columnHeaders[$fieldName] = ['title' => $value['title'],'type'=> CRM_Utils_Type::T_STRING];
            }
        }
        //Hide currency column from display result
        unset($columnHeaders['currency']);
        // Hide contact id and contribution id from display result
        unset($columnHeaders['civicrm_contribution_contribution_id']);
        unset($columnHeaders['civicrm_contact_id']);
        //For calculated fields for sorting condition we are adding column header field in select ,so here at the time of display we are unsetting it.
        if ( array_key_exists('order_bys', $this->_params) && is_array($this->_params['order_bys'])) {
            foreach ($this->_params['order_bys'] as $orderBy) {
                //if order by option is selected on the report
                if($orderBy['column'] != '-') {
                    if(in_array($orderBy['column'],$calculatedFieldsKeyVal) && !in_array($orderBy['column'],$columnKeyVal)) {
                        unset($columnHeaders[$orderBy['column']]);
                    }
                }
            }
        }
        // Remove Sort by Sections from column headers
        foreach ($this->_orderByFields as $fieldName => $value) {
            if ($fieldName == 'financial_type') {
                $entityName = 'financial_type';
            } else{
                $entityName = $this->getEntity();
            }
            $columnInfo = $this->getFieldMapping( $this->getEntityTableFromField($fieldName), $fieldName);
            $sortBySectionAlias = ($columnInfo['custom_alias'])? $columnInfo['custom_alias'] : $columnInfo['table_name'].'_'.$fieldName;
            unset($columnHeaders[$sortBySectionAlias]);
        }
        //Modify column headers for monthly/yearly reports
        if($this->isPeriodicSummary()){
            unset($columnHeaders['total_count']);
            $totalAmountArray = $columnHeaders['total_contribution_sum'];
            unset($columnHeaders['total_contribution_sum']);
           
            foreach ($var as $rowId => $row) {
                if($this->hasMonthlyBreakdown())
                {
                    $columnTitle = date("M", mktime(0, 0, 0, (int) $row['month'], 10)) . ' ' . $row['year'];
                    $columnKey = 'total_amount_'.$row['month'].'_'.$row['year'];
                }else{
                    $columnKey = 'total_amount_'.$row['year'];
                    $columnTitle = $row['year'];
                } 
                $columnHeaders[$columnKey] = ['title' => $columnTitle,'type'=> CRM_Utils_Type::T_MONEY];
             }
             
             if($this->hasMonthlyBreakdown())
             {
                unset($columnHeaders['month']);
             }
            unset($columnHeaders['year']);
            $columnHeaders['total_contribution_sum'] = $totalAmountArray;
        }

        //CRM-2157
        if($this->isContribRetentionReport()){
            $filters = $this->getCustomFilterValues();
            $base_year = array_values($filters['base_year'])[0];
            foreach ($var as $rowId => $row) {
                //To prevent previous years before base_year to be added to resulting report
                if($row['year'] < $base_year)
                    continue;
                $columnTitle = $row['year'];
                $columnKey = $row['year'];
                $columnHeaders[$columnKey] = ['title' => $columnTitle,'type'=> CRM_Utils_Type::T_INT];
            }
            unset($columnHeaders['total_contribution_sum']);
            unset($columnHeaders['total_count']);
            unset($columnHeaders['all_donors']);
            unset($columnHeaders['new_donor']);
            unset($columnHeaders['retained_donors']);
            unset($columnHeaders['retention']);
            unset($columnHeaders['contact_ids']);
        }
    }
   /**
     * function to Rearrange column header for display
     * 
     */
    public function rearrangeColumnHeaders(&$columnHeaders) {

        $rearrangedDefaultColumns = [];
        $rearrangedCalculatedColumns = [];

        $calculatedFieldKeys = array_keys($this->_calculatedFields);
        $defaultColumnsValues = array_values($this->getDefaultColumns());
        $rearrangedCalculatedColumnsKeys = array_keys($rearrangedCalculatedColumns);
  
        foreach($columnHeaders as $key=>$value)
        {
          //check columnheader for Money type
          if( isset($value['type']) && in_array($value['type'],[CRM_Utils_Type::T_MONEY]) ) {
            $rearrangedCalculatedColumns[$key] = $columnHeaders[$key];
            $rearrangedCalculatedColumnsKeys = array_keys($rearrangedCalculatedColumns);
            unset($columnHeaders[$key]);
          }
          //check columnheader for calculated fields
          if( in_array($key, $calculatedFieldKeys) && !in_array($key, $rearrangedCalculatedColumnsKeys) ){
            $rearrangedCalculatedColumns[$key] = $columnHeaders[$key];
            $rearrangedCalculatedColumnsKeys = array_keys($rearrangedCalculatedColumns);
            unset($columnHeaders[$key]);
          }
          //check for default fields to add at the beginning of the columns
          if( in_array($key, $defaultColumnsValues) && !in_array($key, $rearrangedCalculatedColumnsKeys) ){
            $rearrangedDefaultColumns[$key] = $columnHeaders[$key];
            $rearrangedCalculatedColumnsKeys = array_keys($rearrangedCalculatedColumns);
            unset($columnHeaders[$key]);
          }
  
        }
  
        if ( count($rearrangedDefaultColumns) > 0 ) {
            $columnHeaders = array_merge($rearrangedDefaultColumns, $columnHeaders); 
        }
            
        if ( count($rearrangedCalculatedColumns) > 0 ) {
            $columnHeaders = array_merge($columnHeaders, $rearrangedCalculatedColumns);
        }
            
    }

    /**
     * Alter the sections based on our configuration
     * 
     * @param array  $var Array used to show columns.
     * @return void
     */
    public function updateSelectWithSortBySections(){
        list($select,$columnHeader) = $this->getSortBySectionDetails();
        $this->_select = array_merge( $this->_select, $select);
        $this->_selectClauses = array_merge( $this->_selectClauses, $select);
        $this->_columnHeaders = array_merge( $this->_columnHeaders, $columnHeader);
    }
    //get section details for sort by fields if section checkbox is checked
    public function getSortBySectionDetails(){
        $select = [];
        $columnHeader = [];
       
        // loop
        foreach ($this->_orderByFields as $fieldName => $value) {
            
            $columnInfo = $this->getFieldMapping( $this->getEntityTableFromField($fieldName), $fieldName);
            $sortByAlias = ($columnInfo['custom_alias']) ? $columnInfo['custom_alias'] : $columnInfo['table_name'].'_'.$fieldName;
            
            // adding sort field to column headers
            $columnHeader[$sortByAlias] = [
                'title' => $columnInfo['title']
            ];

            if($this->isPeriodicDetailed()) {
                $selectStatement = "CONCAT(MONTHNAME($value),' ', YEAR($value))";
            } else {
                $selectStatement = $value;
            }
            
            $select[] = $selectStatement ." as ". $sortByAlias;
           
           
        }
        return array($select,$columnHeader);
    }

    // manage display of resulting rows
    //TODO : try to replace campaign_id with the name it self in the query rather than performing additional alter display
    public function alterDisplayRows(&$rows) {
        //CRM-2157
        if($this->isContribRetentionReport()) {
            $primaryRetentionRateData = [];
            $columnHeaderValue = [];
            //get base_year filter value from filters
            $filters = $this->getCustomFilterValues();
            $base_year = array_values($filters['base_year'])[0];
            $allDonorsData = [];
            $yearData = array_column($rows, 'year');
            //creating allDonors array which contains list of donor contact_ids segregated by years 
            foreach($yearData as $yearIndex => $yearVal) {
                $allDonorsData[$yearVal] = explode(",", $rows[$yearIndex]['contact_ids']);
                //unsetting rows for years prior to base_year
                if($yearVal < $base_year)
                    unset($rows[$yearIndex]);
            }
            $rows = array_values($rows);
            $compareSet = [];
            foreach($rows as $rowNum => $row) {
                $primaryRetentionRateData[$rowNum]['year'] = $row['year'];
                $primaryRetentionRateData[$rowNum]['retained_donors_'.$row['year']] = $row['retained_donors'];
                //newDonors Calculation
                $allUserContactIDS = explode(",", $row['contact_ids']);
                //compare data with previous years contactIds only
                foreach($allDonorsData as $year => $donorIds) {
                    if( $row['year'] > $year)
                        $compareSet[$year] = $allDonorsData[$year];
                }
                $newDonors = 0;
                foreach($allUserContactIDS as $contact_id){
                    $matchFound = 0;
                    foreach($compareSet as $yearVal => $allDonors) {
                        if(in_array($contact_id,$allDonors)){
                            $matchFound = 1;
                            break;  
                        }
                    }
                    if(!$matchFound)
                        $newDonors++;
                }
                $primaryRetentionRateData[$rowNum]['new_donor_'.$row['year']] = $newDonors;
                if($rowNum == 0){
                    $retentionRate = '-'; 
                }else{
                    $retentionRate = round($row['retained_donors'] / $rows[$rowNum-1]['all_donors'] * 100, 2).'%';
                }
                $primaryRetentionRateData[$rowNum]['retention_'.$row['year']] = $retentionRate;
            }
            $retentionRowsDisplay = [
            'retained_donors'=> 'Repeat Donors',
            'new_donor'=>'New Donors',
            'retention'=> 'Retention Rate'];

            foreach($retentionRowsDisplay as $k=>$v) {
                $columnHeaderValue['year'] = $v;
                foreach($primaryRetentionRateData as $kk=>$vv) {
                    if($k == 'new_donor') {
                        $columnHeaderValue[$vv['year']] = $vv['new_donor_'.$vv['year']];
                    }
                    if($k == 'retained_donors') {
                        $columnHeaderValue[$vv['year']] = $vv['retained_donors_'.$vv['year']];
                    }
                    if($k == 'retention') {
                        $columnHeaderValue[$vv['year']] = $vv['retention_'.$vv['year']];
                    }
                }
                $finalDisplay[] = $columnHeaderValue;
            }
            $rows = $finalDisplay;
        }
        
        if($this->isPeriodicDetailed()){
            $rollupTotalRow = [
                'receive_date_start' => 'Grand Total', 
                'total_contribution_sum' => 0, 
                'total_count' => 0
            ];
            $resultingRow = [];
            foreach($rows as $rowNum => $row) {
                if($this->hasQuarterlyBreakdown()) {
                    $year = date('Y', strtotime($row['receive_date_start']));
                    $resultingRow[$year][] = $row;
                }
                $rollupTotalRow['total_contribution_sum'] += $row['total_contribution_sum'];
                $rollupTotalRow['total_count'] += $row['total_count'];
            }
            if($this->hasQuarterlyBreakdown()){
                $finalDisplay = [];
                foreach($resultingRow as $key => $rowValue) {
                    $subTotal = ['receive_date_start' => 'Yearly Subtotal'];
                    foreach($rowValue as $k=>$result) {
                        $subTotal['total_contribution_sum'] += $result['total_contribution_sum'];
                        $subTotal['total_count'] += $result['total_count'];
                        $finalDisplay[] = $result;
                    }
                    $finalDisplay[] = $subTotal;
                }
                $rows = $finalDisplay;
            }
            $rows[] = $rollupTotalRow;
        }
        if($this->_settings['name'] == "SYBNT"){ //to do later
            $rollupTotalRow = ['exposed_id' => 'Grand Total'];
           
            foreach($rows as $rowNum => $row) {
                foreach($row as $key=>$value) {
                    if($key == 'civicrm_life_time_total' || $key == 'last_year_total_amount' || $key == 'last_four_year_total_amount' || $key == 'last_three_year_total_amount' || $key == 'last_two_year_total_amount') {
                        $rollupTotalRow[$key] += $row[$key];
                    }
                }
            }
            $rows[] = $rollupTotalRow;
        }
        $unassignedDataFields = [];
        $reportType = $this->getReportType();
        if ( array_key_exists('order_bys', $this->_params) && is_array($this->_params['order_bys'])) {
            foreach($this->_params['order_bys'] as $orderbyColumnValue) {
                //For detail and summary report if Section Header / Group By is checked, for those fields null data will use "Unassigned" as value
                if ( isset($orderbyColumnValue['section']) )
                    $unassignedDataFields[] = $orderbyColumnValue['column'];
    
                //For default sorting fields
                if(!empty($this->getDefaultColumns()) 
                && in_array($orderbyColumnValue['column'],$this->getDefaultColumns())
                && !in_array($orderbyColumnValue['column'],$unassignedDataFields)) {
                    $unassignedDataFields[] = $orderbyColumnValue['column'];
                }
            }
        }
        
        if($reportType == 'summary') {
            $unassignedDataFields = array_filter(array_unique( array_merge($unassignedDataFields, array_keys($this->_columns))));
        }
        
        //CRM-2063 - Use "Unassigned" as value in summary/Detailed  reports for NULL values
        foreach ($rows as $rowNum => $row) {
            foreach($this->_columns as $key=>$value) {
                if (array_key_exists($key, $row) && in_array($key,$unassignedDataFields)) {
                    $fieldNameKey = ($row[$key]!= NULL)? $row[$key] : 'Unassigned';
                    $rows[$rowNum][$key] = $fieldNameKey;
                    //create function to convert field name to link for detailed report for sort_name and total_amount field
                }
                $this->fieldWithLink($key,$rows,$row,$rowNum);
            }
        }
        //change rows to display report results of monthly/yearly reports accordingly
        if($this->isPeriodicSummary()){
            $resultingRow = [];
            $finalDisplay = [];
            $currencies = [];
            $fieldName = array_key_first($this->_columns);
            //Roll up row to be appended in the end
            $rollupTotalRow = [$fieldName => 'Grand Total'];
            //filtering out resulting rows by the column filedname key
            foreach($rows as $rowNum => $row) {
                $fieldNameKey = ($row[$fieldName]!= NULL)? $row[$fieldName] : 'Unassigned';
                $resultingRow[$fieldNameKey][] = $row;
                $currencies[$row['currency']] = $row['currency'];
            }
            foreach($resultingRow as $key => $rowValue) {
                $count = $total_amount = 0;
                $columnHeaderValue = [];
                //fieldName grouped by month/year
                foreach($rowValue as $k=>$result) {
                    //
                    $count += $result['total_count'];
                    $total_amount += $result['total_contribution_sum'];
                    $rollupTotalRow['total_contribution_sum'] += $result['total_contribution_sum'];
                    if($this->hasYearlyBreakdown()) {
                        $columnHeaderValue['total_amount_'.$result['year']] = $result['total_contribution_sum'];
                        $rollupTotalRow['total_amount_'.$result['year']] += $result['total_contribution_sum'];
                        
                    }else if($this->hasMonthlyBreakdown()) {
                        $columnHeaderValue['total_amount_'.$result['month'].'_'.$result['year']] = $result['total_contribution_sum'];
                        $rollupTotalRow['total_amount_'.$result['month'].'_'.$result['year']] += $result['total_contribution_sum'];
                    }
                }
                //If any of the monthly/yearly contribution supports multiple currencies, pass currency parameter in row
                $multiple_currency = ''; 
                foreach($currencies as $currency){
                    if (count(explode(',', $currency)) > 1) {
                        $multiple_currency = $currency;
                    }
                }
                $displayRows = [
                    $fieldName => $key,
                    'total_count' => $count,
                    'total_contribution_sum' => $total_amount,
                    'currency' => $multiple_currency
                ];
                $finalDisplay[] = array_merge($displayRows,$columnHeaderValue);
            }
            //Adding rollup row to Displayrow
            $finalDisplay[] = $rollupTotalRow;
            $rows = $finalDisplay;
        }
    }

    /**
     * 
     * Retrieve statistics information for the report
     *
     * @param array $rows Results of the initial query
     * @param bool $showDetailed Whether to also display average, net amount and fees 
     * @return array
     */
    public function alterStatistics(array $rows, bool $showDetailed = false): array {
        $statistics = [];
        $showSybntLybnt = false;
        $showRepeatContributionStats = false;
        $showRecurringContributionStats = false;

        // Check if we have multiple currencies
        $groupByCurrency = false;
        foreach ($rows as $rowNum => $row) {
            if (empty($row['currency'])) {
                $row['currency'] = "CAD";
            }
            if (count(explode(',', $row['currency'])) > 1) {
                $groupByCurrency = true;
                break;
            }
        }

        // if result has multiple currencies then add group by currency clause to statistics query
        if ($groupByCurrency) {
            if (empty($this->_groupBy))
                $this->_groupBy =  ' GROUP BY 1';
            $this->_groupBy .= ', '.$this->getEntityTable('contribution').'.currency';
        }

        $statEntity = $this->isOpportunityReport() ? $this->getEntity():'contribution';
        $statTotalAmountField = $this->isOpportunityReport() ? 'amount_total':'total_amount';
    
        // Add statistics to SELECT statement
        $select   = [];
        $select[] = "COUNT(DISTINCT ".$this->getEntityTable($statEntity).".id ) as total_count";
        $select[] = "SUM(".$this->getEntityTable($statEntity).".".$statTotalAmountField.") as total_contribution_sum";
        $select[] = $this->getEntityTable($statEntity).".currency as currency";

        if($this->isLYBNTSYBNTReport())
        {
            $showSybntLybnt = true;
            $select[] = "MAX(".$this->getEntityTable('contribution').".receive_date) as lastContributionTime";
            
            foreach (array_keys($rows[0]) as $columnName) {
                if(in_array($columnName,['last_four_year_total_amount','last_three_year_total_amount','last_two_year_total_amount','last_year_total_amount'])){
                    $select[] = $this->getCalculatedFieldStatement($columnName).' as '.$columnName;
                }
            }
            $last_four_year_total_amount = $last_three_year_total_amount = $last_two_year_total_amount =$last_year_total_amount = [];
        }

        //Name report by report define select statement
        if($this->isComparisonReport() || $this->isRecurringContributionReport() || $this->isContribRetentionReport())
        {
            if($this->isComparisonReport()) {
                $showRepeatContributionStats = true;
                $range_one_statistics = $range_two_statistics = $range_one_avg =$range_two_avg = $range_one_total_contribution_count = $range_two_total_contribution_count =  [];
            }
            
            if($this->getReportName() == 'contrib_recurring') {
                $showRecurringContributionStats = true;
                $recurringContribThisMonthStats =  $recurringContribLastMonthStats = [];
            }
            
    
            foreach ($this->_statisticsCalculatedFields as $columnName => $row) {
                if(COUNT($row['select'])>1) {
                    foreach ($row['select'] as $aliasFieldRef => $selectCondition) {
                        $select[] = $selectCondition.' as '.$aliasFieldRef; 
                        if($columnName == $aliasFieldRef)
                           $select[] = $selectCondition."  as avg_".$columnName;  
                    }
                    
                }else{
                    $select[] = $row['select'][$columnName].'as '.$columnName;
                }
            }           
        }
        if ($showDetailed) {
            $select[] = "ROUND(AVG(".$this->getEntityTable('contribution').".`total_amount`), 2) as avg";
            $select[] = "SUM(".$this->getEntityTable('contribution').".fee_amount) as fee_amount";
            $select[] = "SUM(".$this->getEntityTable('contribution').".net_amount) as net_amount";
        }

        // Update SQL Query
        $query    = "SELECT " 
            . implode(', ', $select) 
            . " " . $this->_from 
            . " " . $this->_where 
            . " " . $this->_groupBy
            . " " . $this->_havingClause;
        $dao = CRM_Core_DAO::executeQuery($query);
        $currencies = $currAmount = $currCount = $totalAmount = [];
        $totalCount = 0;

        $currFees = $currNet = $currAvg = $feeAmount = $netAmount = $avgAmount = 
        $repeatContributionInitialtotalAmount = $repeatContributionSecondtotalAmount = $repeatContributionInitialavgAmount =
        $repeatContributionSecondavgAmount = $recurContributionThisMonthAmount = $recurContributionLastMonthAmount = [];

        while ($dao->fetch()) {
        
            $currAmount[$dao->currency] = $currAmount[$dao->currency]   ?? 0;
            $currCount[$dao->currency]  = $currCount[$dao->currency]    ?? 0;

            //defining currency amount and count based upon currency
            $currAmount[$dao->currency] += $dao->total_contribution_sum;
            $currCount[$dao->currency]  += $dao->total_count;
        
            $totalCount += $dao->total_count;

            if (!in_array($dao->currency, $currencies)) {
                $currencies[] = $dao->currency;
            } 

            if ($showDetailed) {
                $currFees[$dao->currency]   = $currFees[$dao->currency]     ?? 0;
                $currNet[$dao->currency]    = $currNet[$dao->currency]      ?? 0;
                $currAvg[$dao->currency]    = $currAvg[$dao->currency]      ?? 0;
            
                //defining currency fees,Net and avg based upon currency
                $currFees[$dao->currency] += $dao->fee_amount;
                $currNet[$dao->currency] += $dao->net_amount;
                $currAvg[$dao->currency] += $dao->avg;
            }

            if ($showSybntLybnt) {
                //defining currency fees,Net and avg based upon currency
                if($dao->last_four_year_total_amount)
                $last_four_year_total_amount[] += $dao->last_four_year_total_amount;

                if($dao->last_three_year_total_amount)
                $last_three_year_total_amount[] += $dao->last_three_year_total_amount;

                if($dao->last_two_year_total_amount)
                $last_two_year_total_amount[] += $dao->last_two_year_total_amount;

                if($dao->last_year_total_amount)
                $last_year_total_amount[] += $dao->last_year_total_amount;
            }

            if($showRecurringContributionStats)
            {
                if($dao->recurring_contribution_total_amount)
                $recurringContribThisMonthStats[$dao->currency] += $dao->recurring_contribution_total_amount;

                if($dao->last_month_amount)
                $recurringContribLastMonthStats[$dao->currency] += $dao->last_month_amount;

            }
            if ($showRepeatContributionStats) {

                //defining range one and two statistics, total contribution count and avg based upon currency
                if($dao->range_one_stat)
                $range_one_statistics[$dao->currency] += $dao->range_one_stat;

                if($dao->avg_range_one_stat)
                $range_one_avg[$dao->currency] += $dao->avg_range_one_stat;

                if($dao->primary_total_contribution_count)
                $range_one_total_contribution_count[$dao->currency] += $dao->primary_total_contribution_count;

                if($dao->range_two_stat)
                $range_two_statistics[$dao->currency] += $dao->range_two_stat;

                if($dao->avg_range_two_stat)
                $range_two_avg[$dao->currency] += $dao->avg_range_two_stat;

                if($dao->second_total_contribution_count)
                $range_two_total_contribution_count[$dao->currency] += $dao->second_total_contribution_count;
            }
        }

        foreach ($currencies as $currency) {
            if (empty($currency))
                continue;
        
            $currencyCountText = " (" . $currCount[$currency] . ") (".$currency.")";    
            $totalAmount[]  = CRM_Utils_Money::format($currAmount[$currency], $currency) . $currencyCountText;
            
            if ($showDetailed) {
                $feeAmount[]    = CRM_Utils_Money::format($currFees[$currency], $currency) . $currencyCountText;
                $netAmount[]    = CRM_Utils_Money::format($currNet[$currency], $currency) . $currencyCountText;
                $predetermine   = ($currAvg[$currency]/$currCount[$currency]);
                $avgAmount[]    = CRM_Utils_Money::format($predetermine, $currency) .$currencyCountText;
            }

            if($showRecurringContributionStats) {
               $recurContributionThisMonthAmount[]    = CRM_Utils_Money::format($recurringContribThisMonthStats[$currency], $currency) ;
               $recurContributionLastMonthAmount[]    = CRM_Utils_Money::format($recurringContribLastMonthStats[$currency], $currency) ;
            }

            if ($showRepeatContributionStats) {
                $currencyCountText = " (" . $range_one_total_contribution_count[$currency] . ") (".$currency.")";    
                $currencyCountText2 = " (" . $range_two_total_contribution_count[$currency] . ") (".$currency.")";    
                $repeatContributionInitialtotalAmount[]    = CRM_Utils_Money::format($range_one_statistics[$currency], $currency) . $currencyCountText;
                $repeatContributionSecondtotalAmount[]    = CRM_Utils_Money::format($range_two_statistics[$currency], $currency) . $currencyCountText2;
                if($range_one_avg[$currency] && $range_one_total_contribution_count[$currency])
                {
                    $predetermineRepeatContrib1   = ($range_one_avg[$currency]/$range_one_total_contribution_count[$currency]);
                    $repeatContributionInitialavgAmount[]    = CRM_Utils_Money::format($predetermineRepeatContrib1, $currency) .$currencyCountText;
                }
                if($range_two_avg[$currency] && $range_two_total_contribution_count[$currency])
                {
                    $predetermineRepeatContrib2   = ($range_two_avg[$currency]/$range_two_total_contribution_count[$currency]);
                    $repeatContributionSecondavgAmount[]    = CRM_Utils_Money::format($predetermineRepeatContrib2, $currency) .$currencyCountText2;
                }        
            }
        }
        // total amount
        $statistics['counts']['amount'] = [
            'title' => ts('Total Amount'),
            'value' => implode(', ', $totalAmount),
            'type'  => CRM_Utils_Type::T_STRING,
        ];

        // total contribution count
        $totalCountLabel = ($this->getEntity() == 'grant') ? 'Total Opportunities' : 'Total Contributions';
        $statistics['counts']['total_count'] = [
            'title' => ts($totalCountLabel),
            'value' => $totalCount,
        ];

        if ($showDetailed) {
            // total Average count
            $statistics['counts']['avg'] = [
                'title' => ts('Average'),
                'value' => implode(', ', $avgAmount),
                'type' => CRM_Utils_Type::T_STRING,
            ];
            
            // total fees count
            $statistics['counts']['fees'] = [
                'title' => ts('Fees'),
                'value' => implode(', ', $feeAmount),
                'type' => CRM_Utils_Type::T_STRING,
            ];
            
            // total Net count
            $statistics['counts']['net'] = [
                'title' => ts('Net'),
                'value' => implode(',  ', $netAmount),
                'type' => CRM_Utils_Type::T_STRING,
            ];
        }

        if ($showSybntLybnt) {
            // total Average count
            if(count($last_four_year_total_amount) > 0){
                $statistics['counts']['last_four_year_total_amount'] = [
                    'title' => $this->_columnHeaders['last_four_year_total_amount']['title'],
                    'value' => array_sum($last_four_year_total_amount),
                    'type' => CRM_Utils_Type::T_MONEY,
                ];
            }   
            
            // total fees count
            if(count($last_three_year_total_amount) > 0){
                $statistics['counts']['last_three_year_total_amount'] = [
                    'title' => $this->_columnHeaders['last_three_year_total_amount']['title'],
                    'value' => array_sum($last_three_year_total_amount),
                    'type' => CRM_Utils_Type::T_MONEY,
                ];
            }
            
            // total Net count
            if(count($last_two_year_total_amount) > 0){
                $statistics['counts']['last_two_year_total_amount'] = [
                    'title' => $this->_columnHeaders['last_two_year_total_amount']['title'],
                    'value' => array_sum($last_two_year_total_amount),
                    'type' => CRM_Utils_Type::T_MONEY,
                ];
            }
            if(count($last_year_total_amount) > 0){
                $statistics['counts']['last_year_total_amount'] = [
                    'title' => $this->_columnHeaders['last_year_total_amount']['title'],
                    'value' =>array_sum($last_year_total_amount),
                    'type' => CRM_Utils_Type::T_MONEY,
                ];
            }
        }

        if($showRecurringContributionStats) {
            unset($statistics['counts']['amount']);
            unset($statistics['counts']['count']);
            if(count($recurringContribThisMonthStats) > 0){
                $statistics['counts']['recurring_contribution_total_amount'] = [
                    'title' => $this->_statisticsCalculatedFields['recurring_contribution_total_amount']['title'],
                    'value' => implode(',  ', $recurContributionThisMonthAmount),
                    'type' => CRM_Utils_Type::T_MONEY,
                ];
            }   
            if(count($recurringContribLastMonthStats) > 0){
                $statistics['counts']['last_month_amount'] = [
                    'title' => $this->_statisticsCalculatedFields['last_month_amount']['title'],
                    'value' => implode(',  ', $recurContributionLastMonthAmount),
                    'type' => CRM_Utils_Type::T_MONEY,
                ];
            }   
        }

        if ($showRepeatContributionStats) {
            unset($statistics['counts']['amount']);
            unset($statistics['counts']['total_count']);
            $statistics['counts']['range_one_title'] = array('title' => ts('Initial Date Range:'));
            if(count($range_one_statistics) > 0){
                $statistics['counts']['range_one_stat'] = [
                    'title' => ts('Total Amount'),
                    'value' => implode(',  ', $repeatContributionInitialtotalAmount),
                    'type' => CRM_Utils_Type::T_MONEY,
                ];
            }   

            if(count($range_one_total_contribution_count) > 0){
                $statistics['counts']['primary_total_contribution_count'] = [
                    'title' =>ts('Total Donations'),
                    'value' => $range_one_total_contribution_count[$currency]
                ];
            }

            if(count($range_one_avg) > 0){
                $statistics['counts']['avg_range_one_stat'] = [
                    'title' => 'Average',
                    'value' => implode(',  ', $repeatContributionInitialavgAmount),
                    'type' => CRM_Utils_Type::T_MONEY,
                ];
            }

            $statistics['counts']['range_two_title'] = array(
                'title' => ts('Second Date Range:'),
              );

            if(count($range_two_statistics) > 0){
                $statistics['counts']['range_two_stat'] = [
                    'title' => ts('Total Amount'),
                    'value' => implode(',  ', $repeatContributionSecondtotalAmount),
                    'type' => CRM_Utils_Type::T_MONEY,
                ];
            }

            if(count($range_two_total_contribution_count) > 0){
                $statistics['counts']['second_total_contribution_count'] = [
                    'title' =>ts('Total Donations'),
                    'value' => $range_two_total_contribution_count[$currency]
                ];
            }      
            
            if(count($range_two_avg) > 0){
                $statistics['counts']['avg_range_two_stat'] = [
                    'title' => 'Average',
                    'value' => implode(',  ', $repeatContributionSecondavgAmount),
                    'type' => CRM_Utils_Type::T_MONEY,
                ];
            }
        }
        if($this->isTopDonorReport())
        $statistics = [];

        //CRM-1257
        if($this->isContribRetentionReport()) {
            $statistics = [];
        }
        return $statistics;
    }


    private function fieldWithLink(string $fieldName,&$rows,$row,$rowNum){
        $rows[$rowNum][$fieldName. "_class"] = ''; 
        switch ($fieldName) {
            case 'display_name':
            case 'sort_name':
                if (array_key_exists($fieldName, $row) &&!empty($rows[$rowNum][$fieldName]) && array_key_exists('civicrm_contact_id', $row)) {
                    $url = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' . $row['civicrm_contact_id']);
                    $rows[$rowNum][$fieldName. "_link"] = $url;
                    $rows[$rowNum][$fieldName. "_hover"] = ts('View Contact Summary for this Contact');
                    
                }
                break;
            case 'total_amount':
                if($this->_settings['type'] == 'detailed' && $this->_settings['entity'] == 'contribution')
                if (!empty($rows[$rowNum][$fieldName]) && !empty($rows[$rowNum]['civicrm_contribution_contribution_id']) && !empty($rows[$rowNum]['civicrm_contact_id']) && CRM_Core_Permission::check('access CiviContribute')) {
                    $url = CRM_Utils_System::url("civicrm/contact/view/contribution",
                    [
                        'reset' => 1,
                        'id' => $row['civicrm_contribution_contribution_id'],
                        'cid' => $row['civicrm_contact_id'],
                        'action' => 'view',
                    ]);
                    $rows[$rowNum][$fieldName. "_link"] = $url;
                    $rows[$rowNum][$fieldName. "_hover"] = ts('View Details of this Contribution');
                }
                break;
            case 'receive_date_start':
                if($this->isPeriodicDetailed()){
                    if(!in_array($row['receive_date_start'],['Yearly Subtotal','Grand Total'])){
                        $latestContribReport = $this->getReportInstanceDetailsByName('contrib_latest_dashlet');
                        if ( $this->hasMonthlyBreakdown() ){
                            $dateStart = date('Ym01', strtotime($row['receive_date_start']));
                            $dateEnd = date('Ymt', strtotime($row['receive_date_start']));
                        } else {
                            $year = date('Y', strtotime($row['receive_date_start']));
                            $month = 3 * $row['quartername'] -2;
                            $dateStart = date('Ym01', strtotime($year.'-'.$month.'-01'));
                            $dateEnd = date("Ymd", mktime(0, 0, 0, $month + 3,1 - 1, $year));
                        }
                        $url = CRM_Report_Utils_Report::getNextUrl('instance/'.$latestContribReport['id'],
                        "reset=1&force=1&receive_date_from={$dateStart}&receive_date_to={$dateEnd}");
                        $rows[$rowNum]['receive_date_start'] = date("F Y",strtotime($row['receive_date_start']));
                        $rows[$rowNum][$fieldName. "_link"] = $url;
                        $rows[$rowNum][$fieldName. "_hover"] = ts('View Details for this date');
                    }
                }
                break;
            }
        }

    public function unsetEmptyFilterEntity($filters) {
        foreach($filters as $fk => $filter) {
            if(empty($filters[$fk]))
            {
                unset($filters[$fk]);
            }
        }
        return $filters;
    }

    public function setPreSelectField(array $elementObj) {
            foreach( $elementObj as $elementIndex => $elements) {
                
                if ( isset( $elements->_attributes ) && in_array($elements->_attributes['name'],$this->getDefaultColumns())) {
                    $elementObj[$elementIndex]->_flagFrozen = 1;
                }
            }
        return $elementObj;
    }

    //set default option value to Sort by section
    // TODO: explain + rename?
    public function setDefaultOptionSortBy(array $defaults) {
        if(!empty($this->_settings['order_bys'])) {
            unset($defaults['order_bys']);
            foreach($this->_settings['order_bys'] as $fieldName => $orderConfig) {
                $defaults['order_bys'][] = [
                    'column'=>$fieldName,
                    'order'=>$orderConfig['order'],
                    'section'=> isset($orderConfig['header']) ? true : false
                ];
            }
        }
        return $defaults;
    }

    // todo: this should be switched to public once all reporting refactoring is done
    static function fixFilterOption(string $fieldName, &$filterData) {
        switch ($fieldName) {
            case 'contribution_page_id':
                // TODO: deprecated
                $filterData['options'] = CRM_Contribute_BAO_Contribution::buildOptions('contribution_page_id');
                break;
        
        }
    }
    
    /**
     * 
     * Returns SQL JOIN statement to retrieve data for OptionValue
     *
     * @param string $groupName  Name of the Option Group
     * @param string $fieldName  Name of the Option Value field
     * @param string $tableName  Name of the Option Value database table
     * @return string
     */
    protected function getSQLJoinForOptionValue($groupName, $fieldName, $tableName,$columnName): string {
        $tableName_group = $tableName.'_'.$columnName.'_group';
        $tableName_value = $tableName.'_'.$columnName.'_value';
      return " 
      LEFT JOIN civicrm_option_group as ".$tableName_group." ON ".$tableName_group.".name = '".$groupName."' 
      LEFT JOIN civicrm_option_value as ".$tableName_value." ON ".$tableName_value.".option_group_id = ".$tableName_group.".id 
      AND ".$tableName_value.".value = ".$tableName.".".$fieldName;
    }

    /**
     * 
     * Returns SQL JOIN statement to retrieve data for a field
     *
     * @param string $fieldName  Name of the field
     * @param string $tableName  Name of the database table to join
     * @param string $entityTableName  Name of the main entity database table on which to do the join
     * @param string $tableFieldName Name of the field on which to do the join. Default is `id`
     * @return string
     */
    protected function getSQLJoinForField($fieldName, $tableName, $entityTableName = NULL, $tableFieldName = "id", $joinType = "LEFT"): string {
        $entityTableName = ($entityTableName == NULL) ? $this->getEntityTable() : $entityTableName;
        return "$joinType JOIN $tableName ON $tableName.$tableFieldName = $entityTableName.$fieldName";
    }
    // Returns SQL JOIN statement for address, email, phone fields to avoid wrong Amount calculation if contact has 2 primary emails
    protected function getSQLJoinForAddressField($fieldName, $tableName, $entityTableName = NULL, $tableFieldName = "id", $joinType = "LEFT" ): string {
        $entityTableName = ($entityTableName == NULL) ? $this->getEntityTable() : $entityTableName;
        return "$joinType JOIN $tableName ON $tableName.id = (
            SELECT
            $tableName.id
            FROM $tableName
            WHERE 
                $tableName.$tableFieldName = $entityTableName.$fieldName
                AND $tableName.is_primary = 1
            LIMIT 1
        )  ";
    }

    //having clause
    public function whereClauseLast4Year($fieldName) {
      return "$fieldName BETWEEN '" . $this->getFirstDateOfPriorRangeNYears(4) . "' AND '" . $this->getLastDateOfPriorRange() . "'";
    }
    public function whereClauseLastNYears($fieldName, $count) {
      return "$fieldName BETWEEN '" . $this->getFirstDateOfPriorRangeNYears($count) . "' AND '" . $this->getLastDateOfPriorRangeNYears($count) . "'";
    }

    public function getFirstDateOfPriorRangeNYears($count) {
      return date('YmdHis', strtotime("- $count years", strtotime($this->getFirstDateOfCurrentRange())));
    }

    public function getFirstDateOfCurrentRange() {
      $current_year = $this->_params['yid_value'];
      if (CRM_Utils_Array::value('yid_op', $this->_params, 'calendar') == 'calendar') {
        return "{$current_year }-01-01";
      }
      else {
        $fiscalYear = CRM_Core_Config::singleton()->fiscalYearStart;
        return "{$current_year}-{$fiscalYear['M']}-{$fiscalYear['d']}";
      }
    }

    public function getLastDateOfPriorRange() {
      return date('YmdHis', strtotime('- 1 second', strtotime($this->getFirstDateOfCurrentRange())));
    }

    public function getLastDateOfPriorRangeNYears($count) {
      return date('YmdHis', strtotime("+ 1 years - 1 second", strtotime($this->getFirstDateOfPriorRangeNYears($count))));
    }

    public function whereClauseLastYear($fieldName) {
      return "$fieldName BETWEEN '" . $this->getFirstDateOfPriorRange() . "' AND '" . $this->getLastDateOfPriorRange() . "'";
    }

    public function getFirstDateOfPriorRange() {
      return date('YmdHis', strtotime('- 1 year', strtotime($this->getFirstDateOfCurrentRange())));
    }
  
    public function getLastNYearColumnTitle($year) {
      if (CRM_Utils_Array::value('yid_op', $this->_params, 'calendar') == 'calendar') {
        return ts('Total for ') . ($this->_params['yid_value'] - $year);
      }
      return ts('Total for Fiscal Year ') . ($this->_params['yid_value'] - $year) . '-' . ($this->_params['yid_value']);
    }

    public function getLastYearColumnTitle() {
      if (CRM_Utils_Array::value('yid_op', $this->_params, 'calendar') == 'calendar') {
        return ts('Total for ') . ($this->_params['yid_value'] - 1);
      }
      return ts('Total for Fiscal Year ') . ($this->_params['yid_value'] - 1) . '-' . ($this->_params['yid_value']);
    }

    static function getReportInstanceDetailsByName( $name ): array {
        $result = civicrm_api3('ReportInstance', 'get', [
            'sequential' => 1,
            'return' => ["id"],
            'name' => $name,
            ]);
    
        return $result['values'][0];

        // todo: use API4 after upgrade
        // $reportInstance = \Civi\Api4\ReportInstance::get(TRUE)
        // ->addSelect('name', 'title')
        // ->addWhere('id', '=', $reportId)
        // ->execute()
        // ->itemAt(1);
    }
    //get entity table name using fieldName 
    public function getEntityTableFromField($fieldName,$select = NULL) : string {
        $fieldInfo = $this->getFieldInfo($fieldName);
        if(isset($fieldInfo['custom'])) {
            $entityTableName = EU::getTableNameByName($fieldInfo['group_name']);
            if($select)
            {
                $entityTableName = isset($fieldInfo['dependent_table_entity'])? $this->getEntityTable($fieldInfo['dependent_table_entity']): $entityTableName;
            }
        } else if(isset($fieldInfo['entity'])) {
            $entityTableName = $this->getEntityTable($fieldInfo['entity']);
            if($select)
            {
               $entityTableName = isset($fieldInfo['dependent_table_entity'])? $this->getEntityTable($fieldInfo['dependent_table_entity']): $this->getEntityTable($fieldInfo['entity']);
            } 
        } else {
            //watchdog("debug", "Field doesn't have entity defined. ($fieldName)");
            return '';
        }
        return $entityTableName;
    }
    //get entity clause field through fieldName 'tablename.columnName'
    public function getEntityClauseFromField($fieldName, $forceId = false) : string {
        $fieldInfo = $this->getFieldInfo($fieldName);
        $isCalculatedField = isset($fieldInfo['calculated_field']) && $fieldInfo['calculated_field'] === true;
        $entityTable = $fieldInfo['table_alias'] ?? $this->getEntityTableFromField($fieldName);
        $entityField = ($forceId) ? 'id' : $this->getEntityField($fieldName);
        //don't include entity table for calculated fields as they don't belong to any entity
        $entityClauseStatement = ($isCalculatedField) ? $this->getCalculatedFieldStatement($fieldName) : $entityTable.'.'.$entityField ;
           
        return $entityClauseStatement;
    }
    //get entity field name using fieldName 
    public function getEntityField($fieldName) : string {
        $fieldInfo = $this->getFieldInfo($fieldName);
        if(isset($fieldInfo['custom'])){
            $entityField = E::getColumnNameByName($fieldInfo['custom_fieldName']);
        }else{
            $entityField = $fieldInfo['field_name'] ?? $fieldName;
        }
        return $entityField;
    }

    public function getCalculatedFieldStatement($fieldName): string {
        if ( isset($this->_calculatedFields[$fieldName]) ) {
            return $this->_calculatedFields[$fieldName][$fieldName];
        }
        return $fieldName;
    }

     /**
     * 
     * Returns Custom field Table Name and Custom Column Name 
     *
     * @param string $fieldName  Name of the field
     * @return array
     */
    public function getCustomTableNameColumnName($fieldName): array {
        $customField = \Civi\Api4\CustomField::get()
      ->addSelect('name', 'column_name', 'custom_group_id:name', 'custom_group_id.table_name')
      ->addWhere('name', '=', $fieldName)
      ->execute()
      ->first();
      if(isset($customField) && !empty($customField['custom_group_id.table_name']) && !empty($customField['column_name']))
      {
        return [$customField['custom_group_id.table_name'],$customField['column_name']];
      }
      return [];
    }

    //to be removed
    //get group name name using fieldName 
    public function getGroupNameField($fieldName) {
        $fieldInfo = $this->getFieldInfo($fieldName);
        if(isset($fieldInfo['custom'])){
            $columnName = E::getColumnNameByName($fieldInfo['custom_fieldName']);
            $entityGroup = E::getOptionGroupNameByColumnName($columnName);
          }else{
            $entityGroup = $fieldInfo['group_name'] ?? NULL;
          }
        return $entityGroup;
    }
    
    public function getDefaultFromClause(&$from) {
        // Add defaults for entity
        $from[] = $this->getEntityTable();
        $this->_fromEntity[] = $this->getEntityTable();
        // Automatically join on Contact for reports that are not Contact reports, such Contribution reports 
        if ($this->getEntity() != 'contact') {
            $from[] = $this->getSQLJoinForField('contact_id', $this->getEntityTable('contact'), $this->getEntityTable(), 'id', "INNER");
            $this->_fromEntity[] = $this->getEntityTable('contact');
        }else{
            $from[] = $this->getSQLJoinForField('id', $this->getEntityTable('contribution'), $this->getEntityTable(), 'contact_id', "INNER");
            $this->_fromEntity[] = $this->getEntityTable('contribution');
        }
    }
    //common from clause for summary and detailed report based upon fields
    public function getCommonFromClause(&$from) {

        $fieldsForFromClauses = array_merge($this->_columns,$this->_orderByFieldsFrom,$this->_filters);

         // Add columns joins (if needed)
        foreach($fieldsForFromClauses as $fieldName => $nodata) {
            $fieldInfo   = $this->getFieldInfo($fieldName);
            $entityName  = $this->getEntityTableFromField($fieldName);
            $actualTable = $fieldInfo['table_alias'] ?? $entityName;
            $groupName = $this->getGroupNameField($fieldName);

            $fieldInfo['join_entity'] = $fieldInfo['join_entity'] ?? FALSE;
            $fieldInfo['custom'] = $fieldInfo['custom'] ?? FALSE;

            $alreadyIncluded = false;
            // option value always need a join
            if ( isset($fieldInfo['select_name']) && $fieldInfo['select_name'] === 'option_value' ) {
                $alreadyIncluded = false;
            // field belong to group already joined
            } else if ($groupName !== NULL && in_array($groupName,$this->_fromEntity)) {
                $alreadyIncluded = true;
            // field belong to entity table already joined
            } else if ( in_array($actualTable,$this->_fromEntity)  ) {
                $alreadyIncluded = true;
            }
            // specific cases that do not fit in regular process
            switch ($fieldName) {
                case 'gl_account': // GL Account
                    $from[] = $this->getSQLJoinForField("id", $this->getEntityTable('line_item'), $this->getEntityTable('contribution'), "contribution_id");

                    $from[] = "LEFT JOIN (
                        SELECT financial_account_id,entity_id,entity_table 
                        FROM ".$this->getEntityTable('financial_item')."  
                        GROUP BY entity_id,financial_account_id HAVING SUM(amount)>0
                        ) ".$this->getEntityTable('financial_item')." 
                        ON ( ".$this->getEntityTable('financial_item').".entity_table = 'civicrm_line_item' 
                        AND ".$this->getEntityTable('financial_item').".entity_id = ".$this->getEntityTable('line_item').".id) ";

                    $from[] = $this->getSQLJoinForField('financial_account_id', $this->getEntityTable('financial_account'), $this->getEntityTable('financial_item'));
                    $alreadyIncluded = true;
                    //To avaoid loading 'financial_account' join multiple times
                    $this->_fromEntity[] = $this->getEntityTable('financial_account');
                    break;

                case 'range_one_stat':
                case 'range_two_stat':
                    if($fieldName === 'range_one_stat')
                    {
                        $aliasTableName = 'civicrm_contribution_primaryset';
                        $filterFieldName = 'repeat_contri_initial_date_range';
                    }
                    if($fieldName === 'range_two_stat')
                    {
                        $aliasTableName = 'civicrm_contribution_secondset';
                        $filterFieldName = 'repeat_contri_second_date_range';
                    }
                    $from[] = "LEFT JOIN ".$this->getEntityTable('contribution')." as ".$aliasTableName." ON ".$this->getEntityTable('contribution').".id = ".$aliasTableName.".id";
                    if (in_array($filterFieldName,$this->getFieldNamesForFilters())){
                        if($this->_params[$filterFieldName.'_relative'])
                        $relative = $this->_params[$filterFieldName.'_relative'];
                        if($this->_params[$filterFieldName.'_from'])
                        $fromDate = $this->_params[$filterFieldName.'_from'];
                        if($this->_params[$filterFieldName.'_to'])
                        $toDate = $this->_params[$filterFieldName.'_to'];
                        $intialFilterDateRange = CRM_Utils_Date::getFromTo($relative, $fromDate, $toDate);
                        $firstDateRange = $intialFilterDateRange[0];
                        $secondDateRange = $intialFilterDateRange[1];
                        if(isset($firstDateRange) && isset($secondDateRange))
                        $from[] = "AND ( ".$aliasTableName.".receive_date >= ".$firstDateRange.") 
                        AND ( ".$aliasTableName.".receive_date <= ".$secondDateRange.")";
                    }
                    $alreadyIncluded = true;
                    $this->_fromEntity[] = $aliasTableName;
                    break;

                //prerequisite group join
                case ($entityName === 'civicrm_group'):
                    if (!$alreadyIncluded) {
                        $from[] = "LEFT JOIN ".$this->getEntityTable($fieldInfo['join_entity']).
                            " ON  ".$this->getEntityTable($fieldInfo['join_entity']).".contact_id = ".$this->getEntityTable('contact').".id";
                    
                        $from[] = $this->getSQLJoinForField($fieldInfo['join_field_name'], $entityName, $this->getEntityTable($fieldInfo['join_entity']),'id');
                        $from[] = "AND ".$this->getEntityTable($fieldInfo['join_entity']).".status = 'Added'";
                        $this->_fromEntity[] = $entityName;
                        $alreadyIncluded = true;
                    }
                        
                    break;
                case 'Opportunity_Owner':
                    //CRM-2266: This field requires two joins 
                    if(!in_array($entityName,$this->_fromEntity)){
                        //As this join is mandatory for any field related to grant entity, in case user only selects this field, its necessary to have this join  
                        $from[] = $this->getSQLJoinForField($fieldInfo['join_field_name'], $entityName, $this->getEntityTable($fieldInfo['join_entity']),'entity_id');
                        $this->_fromEntity[] = $entityName;
                    }       
                    $entityField = $this->getEntityField($fieldName);
                    //This join will output correct Opportunity owner name as it is going to be join between contact table and custom opportunity_owner field
                    $from[] = "LEFT JOIN ".$this->getEntityTable('contact').
                    " AS ".$fieldInfo['dependent_table_entity']." ON  ".$fieldInfo['dependent_table_entity'].".id = ".$entityName.".".$entityField;
                    $alreadyIncluded = true;
                    $this->_fromEntity[] = $fieldInfo['dependent_table_entity'];
                    break;
            }

            //CRM-2157 pre-requision join for retentiion report
            if($this->isContribRetentionReport()){
                $from[] = "LEFT JOIN ".$this->getEntityTable()." as future_contrib".
                " ON ".$this->getEntityTable().".contact_id = future_contrib.contact_id AND YEAR(future_contrib.receive_date) = YEAR(".$this->getEntityTable().".receive_date) - 1";
            }

            // adding financial_account_debit / credit
            if ( isset($fieldInfo['entity']) && ( $fieldInfo['entity'] == "financial_account" || $fieldInfo['entity'] == "financial_trxn" || 
                (($fieldInfo['join_entity'] == "financial_account_debit" || $fieldInfo['join_entity'] == "financial_account_credit" ) ) 
                    && !in_array($fieldInfo['join_entity'],$this->_fromEntity)) || $entityName === 'civicrm_batch'){

                    // adding financial_trxn joins
                    $prerequisiteTable = "financial_trxn";

                    if (!in_array($this->getEntityTable($prerequisiteTable),$this->_fromEntity) && ($fieldName !== 'gl_account')) {
                        //modified from clause for financial_trxn entity to prevent multiple entries for the contribution 
                        $from[] = "LEFT JOIN ( SELECT * FROM ".$this->getEntityTable('entity_' . $prerequisiteTable)." WHERE entity_table = 'civicrm_contribution'  GROUP BY entity_id HAVING SUM(amount)>0 )
                        as ".$this->getEntityTable('entity_' . $prerequisiteTable).
                        " ON (".$this->getEntityTable('contribution').".id = ".$this->getEntityTable('entity_' . $prerequisiteTable).".entity_id )";
                    
                        $from[] = "LEFT JOIN ".$this->getEntityTable($prerequisiteTable)." as ".$this->getEntityTable($prerequisiteTable).
                        " ON ".$this->getEntityTable($prerequisiteTable).".id = ".$this->getEntityTable('entity_' . $prerequisiteTable).".financial_trxn_id";
                
                        $this->_fromEntity[] = $this->getEntityTable($prerequisiteTable);
                    
                    }
                    //CRM-2022-1 Repositioned batch condition from case to normal because civicrm_batch entity requires financial_trxn table join with entity_financial_trxn
                    if ($entityName === 'civicrm_batch') {
                        $from[] = "LEFT JOIN ".$this->getEntityTable('entity_batch').
                            " ON  ".$this->getEntityTable('financial_trxn').".id = ".$this->getEntityTable('entity_batch').".entity_id". 
                            " AND ".$this->getEntityTable('entity_batch').".entity_table = 'civicrm_financial_trxn'";
                
                        $from[] = $this->getSQLJoinForField('batch_id', $this->getEntityTable('batch'), $this->getEntityTable('entity_batch'),'id');
                        $this->_fromEntity[] = $entityName;
                        $alreadyIncluded = true;
                    }

                    // adding financial_account joins
                    if($actualTable == "financial_account_debit" || $actualTable == "financial_account_credit"
                    || ($fieldInfo['join_entity'] == "financial_account_debit" || $fieldInfo['join_entity'] == "financial_account_credit"))
                    {
                        if ( !in_array("financial_account_debit",$this->_fromEntity) && !in_array("financial_account_credit",$this->_fromEntity) ) {
            
                            $prerequisiteTable = "financial_account";

                            $from[] = "LEFT JOIN ".$this->getEntityTable($prerequisiteTable)." as financial_account_debit".
                                " ON financial_account_debit.id = ".$this->getEntityTable('financial_trxn').".to_financial_account_id";
                            $from[] = "LEFT JOIN ".$this->getEntityTable($prerequisiteTable)." as financial_account_credit".
                                " ON financial_account_credit.id = ".$this->getEntityTable('financial_trxn').".from_financial_account_id";
                                    
                            $this->_fromEntity[] = "financial_account_debit";
                            $this->_fromEntity[] = "financial_account_credit";
                        }
                    }
                }

            
             //Adding predefine address joins for join_entity
             if ($fieldInfo['join_entity'] == "address" && !in_array($this->getEntityTable($fieldInfo['entity']),$this->_fromEntity)) {
        
                if(!in_array($this->getEntityTable($fieldInfo['join_entity']),$this->_fromEntity))
                {
                    //to prevent multiple enteries for the contact to be added , we are considering only primary key entry for address entity 
                    $from[] = $this->getSQLJoinForAddressField('id', $this->getEntityTable($fieldInfo['join_entity']), $this->getEntityTable('contact'),'contact_id');
                    $this->_fromEntity[] = $this->getEntityTable('address');
                }       
                $from[] = $this->getSQLJoinForField($fieldInfo['join_field_name'], $entityName, $this->getEntityTable($fieldInfo['join_entity']),'id');
                $this->_fromEntity[] = $this->getEntityTable($fieldInfo['entity']);

            }
            
            if(!$alreadyIncluded) {

                //option value
                if (isset($fieldInfo['select_name']) && $fieldInfo['select_name'] === 'option_value' ) {
                    
                    // custom fields + option value
                    if ($fieldInfo['custom'] === true) { 
                        $from[] = $this->getSQLJoinForField($fieldInfo['join_field_name'], $entityName, $this->getEntityTable($fieldInfo['join_entity']),'entity_id');
                    }
                    
                    if ($fieldInfo['custom'] !== true && isset($fieldInfo['join_entity']) && isset($fieldInfo['join_field_name'])) {
                        //Adding this left joint for fields which has join with option value table but also has join_entity defined without being a custom field
                        $from[] = $this->getSQLJoinForField($fieldInfo['join_field_name'], $entityName, $this->getEntityTable($fieldInfo['join_entity']),'id');
                        $from[] = $this->getSQLJoinForOptionValue($groupName,$fieldInfo['join_field_name'],$this->getEntityTable($fieldInfo['join_entity']),$fieldName);
                    } else {
                        $entityField = $this->getEntityField($fieldName);
                        $from[] = $this->getSQLJoinForOptionValue($groupName,$entityField,$entityName,$fieldName);
                    }

                // custom fields
                } else if ($fieldInfo['custom'] === true) { 
                    $from[] = $this->getSQLJoinForField($fieldInfo['join_field_name'], $entityName, $this->getEntityTable($fieldInfo['join_entity']),'entity_id');
                
                // contact fields
                } else if($fieldInfo['join_entity'] === 'contact'){ 
                    //to prevent multiple enteries for the contact to be added , we are considering only primary key entry for address,phone, email entities
                    if(in_array($fieldInfo['entity'], ['phone' , 'email', 'address'])){
                        $from[] = $this->getSQLJoinForAddressField($fieldInfo['join_field_name'], $entityName, $this->getEntityTable($fieldInfo['join_entity']),'contact_id');
                    }else{
                        $from[] = $this->getSQLJoinForField($fieldInfo['join_field_name'], $entityName, $this->getEntityTable($fieldInfo['join_entity']),'contact_id');
                    }
                //entity_tag fields
                } else if($fieldInfo['join_entity'] === 'entity_tag'){ 
                    $from[] = $this->getSQLJoinForField('id', $this->getEntityTable($fieldInfo['join_entity']), $this->getEntityTable('contact'),'entity_id');
                    $from[] = $this->getSQLJoinForField($fieldInfo['join_field_name'], $entityName, $this->getEntityTable($fieldInfo['join_entity']),'id');
                
                // contribution and other entity fields
                } else {
                    $recheckEntityName = $fieldInfo['table_alias'] ?? $entityName;
                    
                    if(!in_array($recheckEntityName,$this->_fromEntity) && isset($fieldInfo['join_field_name']) ) {
                        $joinFieldName = (  preg_match('/_id$/', $fieldInfo['join_field_name']) ) ? 'id' : $this->getEntityField($fieldName);    
                        $from[] = "LEFT JOIN $entityName as $actualTable ON $actualTable." . $joinFieldName . " = " . $this->getEntityTable($fieldInfo['join_entity']) . "." . $fieldInfo['join_field_name'];
                        
                        if ( isset($fieldInfo['join_extra']) ) {
                            $from[] = "AND " . $fieldInfo['join_extra'];
                        }
                        $entityName = $actualTable; // so that we don;t include twice, but still include others with a different alias
                    }
                  
                }
                
                $this->_fromEntity[] = ($groupName !== NULL)? $groupName : $entityName;
            }
        }
        $from = array_unique($from);
    }

    //common from clause for summary and detailed report based upon fields
    public function getCommonSelectClause($fieldName) {

        $fieldInfo = $this->getFieldInfo($fieldName);
        // select clause from option value
        if(isset($fieldInfo['select_name']) && $fieldInfo['select_name'] === 'option_value' )
        {
          if(isset($fieldInfo['custom'])){
            $customTablename = EU::getTableNameByName($fieldInfo['group_name']);
            $selectOption = $customTablename.'_'.$fieldName.'_value.label';
            //For CH Fund fields contributions that are not associated with any Ch-funds should be named as 'Unassigned' and contributions that are associated with empty CH-fund names should be named as blank spaces.
            if($fieldName === 'ch_fund') {
                $selectOption = "CASE
                WHEN  (".$customTablename.".".$this->getEntityField($fieldName)." = ' ') THEN ' '
                ELSE ".$customTablename."_".$fieldName."_value.label
                END";
            }
          } else {
            if ( isset($fieldInfo['custom']) && $fieldInfo['custom'] !== true && isset($fieldInfo['join_entity']) && isset($fieldInfo['join_field_name'])) 
            $selectOption = $this->getEntityTable($fieldInfo['join_entity']).'_'.$fieldName.'_value.label';
                else
            $selectOption = $this->getEntityTable($fieldInfo['entity']).'_'.$fieldName.'_value.label';
          }
          $selectStatement = $selectOption;
        }else if(isset($fieldInfo['select_name'])) //select clause from table
        {
          $selectStatement = $this->getEntityTableFromField($fieldName,true). "." . $fieldInfo['select_name'];

        //normal clause
        } else{ 
          $selectStatement =  $this->getEntityClauseFromField($fieldName);
        }
        
        return $selectStatement;
        
    }
    //This function will fetch report name and ID for report instance
    // Will fetch only report name for template
    public static function getReportDetail( string $reportPath ): array {
        $reportPathArr = explode('/', $reportPath);
        $reportId = end($reportPathArr);
        
        if (strpos($reportPath,'instance') !== false) {
            $reportInfo = CRM_Chreports_Reports_BaseReport::getReportInstanceDetails($reportId);
            $reportName = $reportInfo['name'] ?? $reportInfo['title'];
        } else {
            switch($reportId){
                case 'contrib_glaccount':
                    $reportName = 'contrib_glaccount_payment_reconciliation';
                    break;
                case 'contrib_period_detailed':
                    $reportName = 'contrib_quarterly_past_year';
                    break;
                default:
                    $reportName = $reportId;
                    break;
            }
            $reportId = NULL;
        }
        return [$reportId , $reportName];
    }
}

?>