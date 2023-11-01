<?php
use CRM_Canadahelps_ExtensionUtils as EU;
use CRM_Chreports_ExtensionUtil as E;
class CRM_Chreports_Reports_BaseReport extends CRM_Chreports_Reports_ReportConfiguration {

    private $_name;
    private $_pagination = FALSE;
    private $_entityTableMapping = [];

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
    protected $_having = NULL;
    protected $_limit = NULL;
   
    public $_filters = NULL;

    
    // Default filters for report
    protected $_defaultFilters = 
    [
        "receive_date",
        "receipt_date",
        "contribution_status_id", //op
        "contribution_page_id", //op //campaign
        "financial_type_id", //fund  //op
        "contribution_recur_id", //op
        "total_amount",
        "non_deductible_amount",
        "total_sum",
        "campaign_id", //campaign group //op
        "card_type_id", //op
        "batch_id", //op
        "amount",
        "tagid", //op
        "gid", //op
        "total_sum",
        "total_count",
        "total_avg",
        "contribution_source", //op
        "payment_instrument_id", //op //payment method
        "campaign_type", //op
        "ch_fund" //op //custom
    ];

    public function __construct( string $entity, int $id, string $name ) {
        parent::__construct($id);
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
        if (in_array($entity, ["financial_account_debit", "financial_account_credit"]))
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
    private function getReportingFilters(): array {
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
            if($this->_settings['fields'][$fieldKey]['default']){
                $defaultFields[] = $fieldKey;
            }
        }
        return $defaultFields;
    }
    // manage pre set filters
    // public function setFieldsMapping(array $mapping) {
    //     $this->_mapping = $mapping;
    // }

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
          $selectStatement = preg_replace('/SELECT(\s+SQL_CALC_FOUND_ROWS)?\s+/i', 'SELECT SQL_CALC_FOUND_ROWS ', $selectStatement);
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
    public function getLimit(): string {
        return $this->_limit;
    }
     // manage columns from extendedSummary
     public function setLimit(string $limit) {
        $this->_limit = $limit;
    }

     //Opportunity array defined for fields, orderby, filters
     public function setOpportunityFields(&$var) {
        return;
        $specificCols = [
          'civicrm_grant' => [
            'dao' => 'CRM_Grant_DAO_Grant',
            'fields' => [
              'grant_type_id' => [
                'name' => 'grant_type_id',
                'title' => ts('Grant Type'),
              ],
              'status_id' => [
                'name' => 'status_id',
                'title' => ts('Grant Status'),
              ],
              'amount_total' => [
                'name' => 'amount_total',
                'title' => ts('Opportunity Amount'),
                'type' => CRM_Utils_Type::T_MONEY,
              ],
              'amount_granted' => [
                'name' => 'amount_granted',
                'title' => ts('Amount Received'),
              ],
              'application_received_date' => [
                'name' => 'application_received_date',
                'title' => ts('Application Deadline'),
                'default' => TRUE,
                'type' => CRM_Utils_Type::T_DATE,
              ],
              'money_transfer_date' => [
                'name' => 'money_transfer_date',
                'title' => ts('Money Transfer Date'),
                'type' => CRM_Utils_Type::T_DATE,
              ],
              'grant_due_date' => [
                'name' => 'grant_due_date',
                'title' => ts('Report Due'),
                'type' => CRM_Utils_Type::T_DATE,
              ],
              'decision_date' => [
                'name' => 'decision_date',
                'title' => ts('Decision Date'),
                'type' => CRM_Utils_Type::T_DATE,
              ],
              'rationale' => [
                'name' => 'rationale',
                'title' => ts('Rationale'),
              ],
              'grant_report_received' => [
                'name' => 'grant_report_received',
                'title' => ts('Grant Report Received'),
              ],
            ],
            'filters' => [
              'grant_type' => [
                'name' => 'grant_type_id',
                'title' => ts('Grant Type'),
                'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                'options' => CRM_Core_PseudoConstant::get('CRM_Grant_DAO_Grant', 'grant_type_id'),
              ],
              'status_id' => [
                'name' => 'status_id',
                'title' => ts('Grant Status'),
                'type' => CRM_Utils_Type::T_INT,
                'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                'options' => CRM_Core_PseudoConstant::get('CRM_Grant_DAO_Grant', 'status_id'),
              ],
              'amount_granted' => [
                'title' => ts('Amount Received'),
                'operatorType' => CRM_Report_Form::OP_INT,
              ],
              'amount_total' => [
                'title' => ts('Opportunity Amount'),
                'operatorType' => CRM_Report_Form::OP_INT,
              ],
              'application_received_date' => [
                'title' => ts('Application Deadline'),
                'operatorType' => CRM_Report_Form::OP_DATE,
                'type' => CRM_Utils_Type::T_DATE,
              ],
              'money_transfer_date' => [
                'title' => ts('Money Transfer Date'),
                'operatorType' => CRM_Report_Form::OP_DATE,
                'type' => CRM_Utils_Type::T_DATE,
              ],
              'grant_due_date' => [
                'title' => ts('Report Due'),
                'operatorType' => CRM_Report_Form::OP_DATE,
                'type' => CRM_Utils_Type::T_DATE,
              ],
              'decision_date' => [
                'title' => ts('Decision Date'),
                'operatorType' => CRM_Report_Form::OP_DATE,
                'type' => CRM_Utils_Type::T_DATE,
              ],
            ],
            'group_bys' => [
              'grant_type_id' => [
                'title' => ts('Grant Type'),
              ],
              'status_id' => [
                'title' => ts('Grant Status'),
              ],
              'application_received_date' => [
                'title' => ts('Application Deadline'),
              ],
              'money_transfer_date' => [
                'title' => ts('Money Transfer Date'),
              ],
              'decision_date' => [
                'title' => ts('Decision Date'),
              ],
            ],
            'order_bys' => [
              'grant_type_id' => [
                'title' => ts('Grant Type'),
              ],
              'status_id' => [
                'title' => ts('Grant Status'),
              ],
              'amount_total' => [
                'title' => ts('Opportunity Amount'),
              ],
              'amount_granted' => [
                'title' => ts('Amount Received'),
              ],
              'application_received_date' => [
                'title' => ts('Application Deadline'),
              ],
              'money_transfer_date' => [
                'title' => ts('Money Transfer Date'),
              ],
              'decision_date' => [
                'title' => ts('Decision Date'),
              ],
            ],
          ],
        ];
        $var = array_merge($var, $specificCols);
    }

    // public function setAddressField(&$var) {
    //     return;
    //     $addressCols = [
    //         'civicrm_address' => [
    //             'dao' => 'CRM_Core_DAO_Address',
    //             'fields' => [
    //                 'address_name' => [
    //                     'title' => ts('Address Name'),
    //                     'default' => FALSE,
    //                     'name' => 'name',
    //                 ],
    //             'street_address' => [
    //                 'title' => ts('Address'),
    //                 'default' => FALSE,
    //             ],
    //             'supplemental_address_1' => [
    //                 'title' => ts('Supplementary Address Field 1'),
    //                 'default' => FALSE,
    //             ],
    //             'supplemental_address_2' => [
    //                 'title' => ts('Supplementary Address Field 2'),
    //                 'default' => FALSE,
    //             ],
    //             'supplemental_address_3' => [
    //                 'title' => ts('Supplementary Address Field 3'),
    //                 'default' => FALSE,
    //             ],
    //             'street_number' => [
    //                 'title' => ts('Street Number'),
    //                 'default' => FALSE,
    //             ],
    //             'street_name' => [
    //                 'title' => ts('Street Name'),
    //                 'default' => FALSE,
    //             ],
    //             'street_unit' => [
    //                 'title' => ts('Street Unit'),
    //                 'default' => FALSE,
    //             ],
    //             'city' => [
    //                 'title' => 'City',
    //                 'default' => FALSE,
    //             ],
    //             'state_province_id' => [
    //                 'title' => 'Province',
    //                 'default' => FALSE,
    //                 'alter_display' => 'alterStateProvinceID',
    //             ],
    //             'postal_code' => [
    //                 'title' => 'Postal Code',
    //                 'default' => FALSE,
    //             ],
    //             'postal_code_suffix' => [
    //                 'title' => 'Postal Code Suffix',
    //                 'default' => FALSE,
    //             ],
    //             'country_id' => [
    //                 'title' => 'Country',
    //                 'default' => FALSE,
    //                 'alter_display' => 'alterCountryID',
    //             ],
    //             ],
    //             'grouping' => 'location-fields',
    //         ],
    //     ];
    //     $var = array_merge($var, $addressCols);
    // }
    
    //get type of the report from settings
    public function getReportType(): string {
        return $this->_settings['type'];
    }
    //get name of the report
    public function getReportName(): string {
        return $this->_settings['name'];
    }
     //get type of the report from settings
     public function isFiscalQuarterReport(): bool {
        if($this->_settings['type'] == 'fiscal' || $this->_settings['type'] == 'quarterly')
        {
            return true; 
        }
        return false;
    }
    //get type of the report from settings
    public function isMonthlyYearlyReport(): bool {
        if($this->_settings['type'] == 'monthly' || $this->_settings['type'] == 'yearly')
        {
            $this->_limit = '';
            return true; 
        }
        return false;
    }

    //check if report is repeat contribution report
    public function isRepeatContributionReport(): bool {
        if($this->_settings['name'] == 'repeat_contributions_detailed' && $this->_settings['entity'] == 'contact')
        {
            $this->setEntity($this->_settings['entity']);
            return true; 
        }
        return false;
    }
    //check if report is opportunity report
    public function isOpportunityReport(): bool {
        if($this->_settings['name'] == 'opportunity_details' && $this->_settings['entity'] == 'grant')
        {
            return true; 
        }
        return false;
    }
    //check if report is opportunity report
    public function isLYBNTSYBNTReport(): bool {
        if($this->_settings['name'] == 'sybunt' || $this->_settings['name'] == 'lybunt')
        {
            return true; 
        }
        return false;
    }
    
    //manage filters variable
    public function getFilters(): array {
        return $this->_filters;
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
                        if( !preg_match('/_id$/', $fieldInfo['field_name']) )
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
                    $filterNames[$fieldName] = 'nll';
                    break;

                case 'nnll':
                    $filterNames[$fieldName] = 'nnll';
                    break;

                default:
                    if ($matches[2] == 'from' && $params[$fieldName.'_from']) {
                        $filterNames[$fieldName] = [
                            'from' => $params[$fieldName.'_from'],
                            'to' => $params[$fieldName.'_to']
                        ];
                    } elseif ($matches[2] == 'relative' && $params[$matches[0]]) {
                        $filterNames[$fieldName]['relative'] = $params[$matches[0]];
                    } elseif($params[$fieldName.'_value']) {
                        $filterNames[$matches[1]][$params[$matches[0]]] = $params[$fieldName.'_value'];
                    }
                    break;
            }
        }
        return $filterNames;
    }

    
    //get field details from array
    public function getFieldMapping(string $fieldEntity, string $fieldName): array {
        $entityTable = ($fieldEntity != NULL) ? $fieldEntity : $this->getEntity();
       
        //$entityTable = ($this->_entityTableMapping[$fieldName] == NULL) ? $this->getEntityTable() : $this->_entityTableMapping[$fieldName];
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
     * 
     * Retrieve GL Accountand Payment Method Reconciliation Report
     *
     * @return bool
     */
    public function isGLAccountandPaymentMethodReconciliationReport()
    {
        if($this->_settings['type'] == 'detailed' && $this->_settings['entity'] == 'contribution' 
        && $this->_settings['name'] == 'gl_account_payment_method_reconciliation_report_full')
        {
            //$this->setEntity($this->_settings['entity']);
            return true; 
        }
        return false;
    }
    /**
     * 
     * Retrieve Repeat Contributions Report
     *
     * @return bool
     */
    public function isRepeatContributionsReport()
    {
        if($this->_settings['type'] == 'detailed' && $this->_settings['entity'] == 'contact' 
        && $this->_settings['name'] == 'repeat_contributions_detailed')
        {
            $this->setEntity($this->_settings['entity']);
            return true; 
        }
        return false;
    }
    /**
     * 
     * Retrieve Recurring Contribution Report
     *
     * @return bool
     */

    public function isRecurringContributionReport()
    {
        if($this->_settings['type'] == 'detailed' && $this->_settings['entity'] == 'contact' 
        && $this->_settings['name'] == 'recurring_contributions_summary')
        {
            $this->setEntity($this->_settings['entity']);
            return true; 
        }
        return false;
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
            //$entityName = $fieldInfo['entity'];
            $var[$entityName]['fields'][$fieldName] = [
                "title" => $fieldInfo["title"],
                "default" => ( $fieldInfo["default"] === true || $fieldInfo["selected"] === true ),
                "type" => $filterType['type'],
                "custom_alias" =>  $entityName.'_'.$fieldName,
            ];
            //set order by fields //sort by
            if(isset($this->_settings['orderByDisplay']) && $this->_settings['orderByDisplay'] === false){
                unset($var[$entityName]['order_bys'][$fieldName]);
            }else{
                $var[$entityName]['order_bys'][$fieldName] = [
                    'title' => $fieldInfo["title"]
                ];
            }

            //set group by
            if(isset($this->_settings['groupByDisplay']) && $this->_settings['groupByDisplay'] === false){
                unset($var[$entityName]['group_bys'][$fieldName]);
            }else{
                $var[$entityName]['group_bys'][$fieldName] = [
                    'title' => $fieldInfo["title"],
                    "default" => ( $fieldInfo["default"] === true || $fieldInfo["selected"] === true ),
                ];
            }

        }
    }
    
    private function setFormFilterOptions(&$var) {
        foreach ($this->getReportingFilters() as $fieldName => $fieldInfo) {

            //check if filter value needs to be pre set
            if(count($fieldInfo) > 0) { 
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

    private function filteringReportAddCustomFilter($fieldName,&$var) {
        return;
        if(in_array($fieldName,$this->getReportingFilters())){
            switch ($fieldName) {
                case 'contribution_source':
                    $source = EU::getSourceDropdownList();
                    $var[$this->getEntityTable()]['filters']['contribution_source'] = [
                        'title' => ts('Contribution Source'),
                        'type' => CRM_Utils_Type::T_STRING,
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'options' => $source,
                        'table_name' => $this->getEntityTable(),
                        'alias' => $this->getEntityTable(),
                        'dbAlias' => $this->getEntityTable().'.source'
                    ];
                    break;
                case 'payment_instrument_id':
                    $var[$this->getEntityTable()]['filters']['payment_instrument_id'] = [
                        'title' => ts('Payment Method'),
                        'type' => CRM_Utils_Type::T_INT,
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'options' => CRM_Core_OptionGroup::values('payment_instrument'),
                    ];
                    break;
                case 'campaign_type';
                    if ($columnName = E::getColumnNameByName('Campaign_Type')) {
                        $optionGroupName = E::getOptionGroupNameByColumnName($columnName);
                        $customTablename = EU::getTableNameByName('Campaign_Information');
                        $var[$this->getEntityTable()]['filters']['campaign_type'] = [
                        'title' => ts('Contribution Page Type'),
                        'type' => CRM_Utils_Type::T_STRING,
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'table_name' => $customTablename,
                        'column_name' => $columnName,
                        'options' => CRM_Core_OptionGroup::values($optionGroupName),
                        'dbAlias' => $customTablename.".".$columnName,
                        ];
                    }
                    break;
                case 'ch_fund';
                    if ($columnName = E::getColumnNameByName('Fund')) {
                        $optionGroupName = E::getOptionGroupNameByColumnName($columnName);
                        $customTablename = EU::getTableNameByName('Additional_info');
                        $var[$this->getEntityTable()]['filters'][$fieldName] = [
                        'title' => ts('CH Fund'),
                        'column_name' => $columnName,
                        'table_name' => $customTablename,
                        'type' => CRM_Utils_Type::T_STRING,
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'options' => CRM_Core_OptionGroup::values($optionGroupName),
                        'dbAlias' => $customTablename.".".$columnName,
                        ];
                    }
                    break;
                case 'application_submitted';
                    if ($columnName = E::getColumnNameByName('application_submitted')) {
                        $customTablename = EU::getTableNameByName('Grant');
                        $var[$this->getEntityTable('grant')]['filters'][$fieldName] = [
                        'title' => ts('Application Submitted'),
                        'column_name' => $columnName,
                        'table_name' => $customTablename,
                        'type' => CRM_Utils_Type::T_BOOLEAN,
                        'dbAlias' => $customTablename.".".$columnName,
                        ];
                    }
                    break;
                case 'probability';
                    if ($columnName = E::getColumnNameByName('probability')) {
                        $optionGroupName = E::getOptionGroupNameByColumnName($columnName);
                        $customTablename = EU::getTableNameByName('Grant');
                        $var[$this->getEntityTable('grant')]['filters'][$fieldName] = [
                        'title' => ts('Probability'),
                        'column_name' => $columnName,
                        'table_name' => $customTablename,
                        'type' => CRM_Utils_Type::T_STRING,
                        'options' => CRM_Core_OptionGroup::values($optionGroupName),
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'dbAlias' => $customTablename.".".$columnName,
                        ];
                    }
                    break;
                case 'Opportunity_Name';
                    if ($columnName = E::getColumnNameByName('Opportunity_Name')) {
                        $customTablename = EU::getTableNameByName('Grant');
                        $var[$this->getEntityTable('grant')]['filters'][$fieldName] = [
                        'title' => ts('Opportunity Name'),
                        'column_name' => $columnName,
                        'table_name' => $customTablename,
                        'type' => CRM_Utils_Type::T_STRING,
                        'dbAlias' => $customTablename.".".$columnName,
                        ];
                    }
                    break;
                case 'Opportunity_Owner';
                    if ($columnName = E::getColumnNameByName('Opportunity_Owner')) {
                        $customTablename = EU::getTableNameByName('Grant');
                        $var[$this->getEntityTable('grant')]['filters'][$fieldName] = [
                        'title' => ts('Opportunity Owner'),
                        'column_name' => $columnName,
                        'table_name' => $customTablename,
                        'type' => CRM_Utils_Type::T_STRING,
                        'dbAlias' => $customTablename.".".$columnName,
                        ];
                    }
                    break;
                case 'on_hold';
                    $var[$this->getEntityTable()]['filters'][$fieldName] = [
                        'title' => ts('On Hold'),
                        'type' => CRM_Utils_Type::T_INT,
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'options' => ['' => ts('Any')] + CRM_Core_PseudoConstant::emailOnHoldOptions(),
                    ];
                    break;
                case 'yid':
                    $yearsInPast = 10;
                    $yearsInFuture = 1;
                    $date = CRM_Core_SelectValues::date('custom', NULL, $yearsInPast, $yearsInFuture);
                        $count = $date['maxYear'];
                        while ($date['minYear'] <= $count) {
                        $optionYear[$date['minYear']] = $date['minYear'];
                        $date['minYear']++;
                        }
                        $var[$this->getEntityTable()]['filters'][$fieldName] = [
                        //'name' => 'receive_date',
                        'title' => ts('This Year'),
                        'operatorType' => CRM_Report_Form::OP_SELECT,
                        'options' => $optionYear,
                        'default' => date('Y'),
                        'type' => CRM_Utils_Type::T_INT,
                    ];
                    break; 
                case 'total_range':
                        $var[$this->getEntityTable()]['filters'][$fieldName] = [
                            'title' => ts('Show no. of Top Donors'),
                            'type' => CRM_Utils_Type::T_INT,
                            'default_op' => 'eq',
                    ];
                    break; 
                    //repeat contributions report
                case 'total_lifetime_contributions';
                    if ($columnName = E::getColumnNameByName('Total_Lifetime_Contributions')) {
                        $customTablename = EU::getTableNameByName('Summary_Fields');
                        $var[$this->getEntityTable()]['filters'][$fieldName] = [
                        'title' => ts('Total Lifetime Contributions'),
                        'column_name' => $columnName,
                        'table_name' => $customTablename,
                        'type' => CRM_Utils_Type::T_MONEY,
                        'dbAlias' => $customTablename.".".$columnName,
                        ];
                    }
                    break;
                case 'amount_of_last_contribution';
                    if ($columnName = E::getColumnNameByName('Amount_of_last_contribution')) {
                        $customTablename = EU::getTableNameByName('Summary_Fields');
                        $var[$this->getEntityTable()]['filters'][$fieldName] = [
                        'title' => ts('Amount of last contribution'),
                        'column_name' => $columnName,
                        'table_name' => $customTablename,
                        'type' => CRM_Utils_Type::T_MONEY,
                        'dbAlias' => $customTablename.".".$columnName,
                        ];
                    }
                    break;
                case 'date_of_last_contribution';
                    if ($columnName = E::getColumnNameByName('Date_of_Last_Contribution')) {
                        $customTablename = EU::getTableNameByName('Summary_Fields');
                        $var[$this->getEntityTable()]['filters'][$fieldName] = [
                        'title' => ts('Date of Last Contributions'),
                        'column_name' => $columnName,
                        'table_name' => $customTablename,
                        'operatorType' => CRM_Report_Form::OP_DATETIME,
                        'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
                        'dbAlias' => $customTablename.".".$columnName,
                        ];
                    }
                    break;
                case 'date_of_first_contribution';
                    if ($columnName = E::getColumnNameByName('Date_of_First_Contribution')) {
                        $customTablename = EU::getTableNameByName('Summary_Fields');
                        $var[$this->getEntityTable()]['filters'][$fieldName] = [
                        'title' => ts('Date of First Contributions'),
                        'column_name' => $columnName,
                        'table_name' => $customTablename,
                        'operatorType' => CRM_Report_Form::OP_DATETIME,
                        'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
                        'dbAlias' => $customTablename.".".$columnName,
                        ];
                    }
                    break;
                case 'largest_contribution';
                    if ($columnName = E::getColumnNameByName('Largest_Contribution')) {
                        $customTablename = EU::getTableNameByName('Summary_Fields');
                        $var[$this->getEntityTable()]['filters'][$fieldName] = [
                        'title' => ts('Largest Contribution'),
                        'column_name' => $columnName,
                        'table_name' => $customTablename,
                        'type' => CRM_Utils_Type::T_MONEY,
                        'dbAlias' => $customTablename.".".$columnName,
                        ];
                    }
                    break;
                case 'count_of_contributions';
                    if ($columnName = E::getColumnNameByName('Count_of_Contributions')) {
                        $customTablename = EU::getTableNameByName('Summary_Fields');
                        $var[$this->getEntityTable()]['filters'][$fieldName] = [
                        'title' => ts('Count of Contributions'),
                        'column_name' => $columnName,
                        'table_name' => $customTablename,
                        'operatorType' => CRM_Report_Form::OP_INT,
                        'type' => CRM_Utils_Type::T_INT,
                        'dbAlias' => $customTablename.".".$columnName,
                        ];
                    }
                    break;
            }
        }
    }

    /**
     * Alter the column headers for display
     * 
     * @param array  $var Array used to show columns.
     * @return void
     */
    public function alterColumnHeadersForDisplay(&$var, &$columnHeaders ){
        //Hide currency column from display result
        unset($columnHeaders['currency']);
        // Hide contact id and contribution id from display result
        unset($columnHeaders['civicrm_contribution_contribution_id']);
        unset($columnHeaders['civicrm_contact_id']);
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
        if($this->isMonthlyYearlyReport()){
            unset($columnHeaders['count']);
            $reportType = $this->getReportType();
            
            //
            foreach ($var as $rowId => $row) {
                if($reportType == 'monthly')
                {
                    $columnTitle = date("M", mktime(0, 0, 0, (int) $row['month'], 10)) . ' ' . $row['year'];
                    $columnKey = 'total_amount_'.$row['month'].'_'.$row['year'];
                }else{
                    $columnKey = 'total_amount_'.$row['year'];
                    $columnTitle = $row['year'];
                } 
                $columnHeaders[$columnKey] = ['title' => $columnTitle,'type'=> CRM_Utils_Type::T_MONEY];
             }
             if($reportType == 'monthly')
             {
                unset($columnHeaders['month']);
             }
            unset($columnHeaders['year']);
            //re arrange column headers, move 'total_amount' field at the last
            $fieldsToBeAranged = ['total_amount'];
            $this->rearrangeColumnHeaders($fieldsToBeAranged,$columnHeaders);
          }
        }

    public function rearrangeColumnHeaders($fieldsToBeAranged,&$columnHeaders){
        $arrangedColumns = [];
        foreach ($fieldsToBeAranged as $name) {
            if (array_key_exists($name, $columnHeaders)) {
              $arrangedColumns[$name] = $columnHeaders[$name];
              unset($columnHeaders[$name]);
            }
          }
          $columnHeaders = array_merge($columnHeaders, $arrangedColumns);
    }

    /**
     * Alter the sections based on our configuration
     * 
     * @param array  $var Array used to show columns.
     * @return void
     */
    public function updateSelectWithSortBySections(){
        $select = [];
        $columnHeader = [];
       
        // loop
        foreach ($this->_orderByFields as $fieldName => $value) {
            
            $columnInfo = $this->getFieldMapping( $this->getEntityTableFromField($fieldName), $fieldName);
            if(($this->isRecurringContributionReport()) && ($fieldName == 'total_amount' || $fieldName == 'last_month_amount' || $fieldName == 'completed_contributions' || $fieldName == 'start_date'))
           {
            $sortByAlias = ($columnInfo['custom_alias']) ? $columnInfo['custom_alias'] : $fieldName;
           }else{
            $sortByAlias = ($columnInfo['custom_alias']) ? $columnInfo['custom_alias'] : $columnInfo['table_name'].'_'.$fieldName;
           }
            
            // adding sort field to column headers
            $columnHeader[$sortByAlias] = [
                'title' => $columnInfo['title']
            ];
            if(($this->isRecurringContributionReport()) && ($fieldName == 'total_amount' || $fieldName == 'last_month_amount' || $fieldName == 'completed_contributions' || $fieldName == 'start_date'))
           {
            $selectStatement = ($columnInfo['select_clause_alias'] && $columnInfo['custom_alias']) ? $columnInfo['select_clause_alias'] : $columnInfo['name'];
           }else if($this->isFiscalQuarterReport()){
                $selectStatement = "CONCAT(MONTHNAME($value),' ', YEAR($value))";
            } else{
            $selectStatement = $value;
           }
           if(($this->isRecurringContributionReport()) && ($fieldName == 'total_amount' || $fieldName == 'last_month_amount' || $fieldName == 'completed_contributions' || $fieldName == 'start_date'))
           {
           }else{
            $select[] = $selectStatement ." as ". $sortByAlias;
           }
           
        }
        $this->_select = array_merge( $this->_select, $select);
        $this->_selectClauses = array_merge( $this->_selectClauses, $select);
        $this->_columnHeaders = array_merge( $this->_columnHeaders, $columnHeader);
    }

    // manage display of resulting rows
    //TODO : try to replace campaign_id with the name it self in the query rather than performing additional alter display
    public function alterDisplayRows(&$rows) {
        if($this->isFiscalQuarterReport()){
            $rollupTotalRow = ['receive_date_start' => 'Grand Total'];
            $resultingRow = [];
            foreach($rows as $rowNum => $row)
            {
                if($this->getReportType() == 'quarterly')
                {
                    $year = date('Y', strtotime($row['receive_date_start']));
                    $resultingRow[$year][] = $row;
                }
                $rollupTotalRow['total_amount'] += $row['total_amount'];
                $rollupTotalRow['count'] += $row['count'];
            }
            if($this->getReportType() == 'quarterly'){
                $finalDisplay = [];
                foreach($resultingRow as $key => $rowValue)
                {
                    $subTotal = ['receive_date_start' => 'Total'];
                    foreach($rowValue as $k=>$result)
                    {
                        $subTotal['total_amount'] += $result['total_amount'];
                        $subTotal['count'] += $result['count'];
                        $finalDisplay[] = $result;
                    }
                $finalDisplay[] = $subTotal;
                }
                $rows = $finalDisplay;
            }
            $rows[] = $rollupTotalRow;
        }
        if($this->_settings['name'] == "SYBNT"){
            $rollupTotalRow = ['exposed_id' => 'Grand Total'];
           
            foreach($rows as $rowNum => $row)
            {
                foreach($row as $key=>$value)
            {
                if($key == 'civicrm_life_time_total' || $key == 'last_year_total_amount' || $key == 'last_four_year_total_amount' || $key == 'last_three_year_total_amount' || $key == 'last_two_year_total_amount')
                {
                    $rollupTotalRow[$key] += $row[$key];
                }
            }
            }
            $rows[] = $rollupTotalRow;
        }
        $unassignedDataFields = [];
        $reportType = $this->getReportType();
        if(array_column($this->_params['order_bys'], 'section')) {
        foreach($this->_params['order_bys'] as $orderbyColumnKey=>$orderbyColumnValue)
        {
            if($orderbyColumnValue['section'])
            $unassignedDataFields[] = $orderbyColumnValue['column'];
        }
        if($reportType == 'summary')
        $unassignedDataFields = array_filter(array_unique( array_merge($unassignedDataFields, array_keys($this->_columns))));
        } else {
            if($reportType == 'summary')
            $unassignedDataFields = array_filter(array_unique( array_merge($unassignedDataFields, array_keys($this->_columns))));
        }
        foreach ($rows as $rowNum => $row) {
            //CRM-2063 - Use "Unassigned" as value in summary/Detailed  reports for NULL values
            foreach($this->_columns as $key=>$value)
            {
                if (array_key_exists($key, $row) && in_array($key,$unassignedDataFields))
                {
                    $fieldNameKey = ($row[$key]!= NULL)? $row[$key] : 'Unassigned';
                    $rows[$rowNum][$key] = $fieldNameKey;
                    //create function to convert field name to link for detailed report for sort_name and total_amount field
                }
                $this->fieldWithLink($key,$rows,$row,$rowNum);
            }
        }
        //change rows to display report results of monthly/yearly reports accordingly
        if($this->isMonthlyYearlyReport()){
        $resultingRow = [];
        $finalDisplay = [];
        $fieldName = array_key_first($this->_columns);
        //Roll up row to be appended in the end
        $rollupTotalRow = [$fieldName => 'Grand Total'];
        //filtering out resulting rows by the column filedname key
         foreach($rows as $rowNum => $row)
         {
            $fieldNameKey = ($row[$fieldName]!= NULL)? $row[$fieldName] : 'Unassigned';
            $resultingRow[$fieldNameKey][] = $row;
         }
         foreach($resultingRow as $key => $rowValue)
         {
            $count = $total_amount = 0;
            $columnHeaderValue = [];
            //fieldName grouped by month/year
            foreach($rowValue as $k=>$result)
            {
                //
                $count += $result['count'];
                $total_amount += $result['total_amount'];
                $rollupTotalRow['total_amount'] += $result['total_amount'];
                if($this->getReportType() == 'yearly' )
                {
                    $columnHeaderValue['total_amount_'.$result['year']] = $result['total_amount'];
                    $rollupTotalRow['total_amount_'.$result['year']] += $result['total_amount'];
                    
                }else if($this->getReportType() == 'monthly')
                {
                    $columnHeaderValue['total_amount_'.$result['month'].'_'.$result['year']] = $result['total_amount'];
                    $rollupTotalRow['total_amount_'.$result['month'].'_'.$result['year']] += $result['total_amount'];
                }
                
            }
            $displayRows = [
                $fieldName => $key,
                'count' => $count,
                'total_amount' => $total_amount,
              ];
            $finalDisplay[] = array_merge($displayRows,$columnHeaderValue);
         }
         //Adding rollup row to Displayrow
         $finalDisplay[] = $rollupTotalRow;
         $rows = $finalDisplay;
        }
      }

    
    public function alterRecurringStatistics(array $rows, bool $showDetailed = false): array {
        $statistics = [];
        if($this->isRecurringContributionReport())
        {
            $contriQuery = "IFNULL((CASE WHEN 
            YEAR(".$this->getEntityTable('contribution').".receive_date) = YEAR(NOW()) AND MONTH(".$this->getEntityTable('contribution').".receive_date) = MONTH(NOW()) THEN SUM(".$this->getEntityTable('contribution').".total_amount) 
            END),0) AS total_amount,
            IFNULL((CASE WHEN 
            YEAR(".$this->getEntityTable('contribution').".receive_date) = YEAR(MAX(".$this->getEntityTable('contribution').".receive_date)) AND MONTH(".$this->getEntityTable('contribution').".receive_date) = MONTH(MAX(".$this->getEntityTable('contribution').".receive_date)) THEN SUM(".$this->getEntityTable('contribution').".total_amount) 
            END),0) AS last_month_amount ".$this->_from.' '.$this->_where;
    
            $contriSQL = "SELECT {$contriQuery} {$this->_groupBy}";
            $contriDAO = CRM_Core_DAO::executeQuery($contriSQL);
            $thisMonthAmount=$lastMonthAmount=[];
            $count=0;
            while ($contriDAO->fetch()) {
                if(!empty($contriDAO->total_amount))
                $thisMonthAmount[] += $contriDAO->total_amount;
                if(!empty($contriDAO->total_amount))
                $lastMonthAmount[] += $contriDAO->last_month_amount;
            
            }
            $statistics['counts']['total_amount'] = [
                'title' => ts('This Month Total Amount'),
                'value' => array_sum($thisMonthAmount),
                'type' => CRM_Utils_Type::T_MONEY
            ];

            // total contribution count
            $statistics['counts']['last_month_amount'] = [
                'title' => ts('Last Month Total Amount'),
                'value' => array_sum($lastMonthAmount),
                'type' => CRM_Utils_Type::T_MONEY
            ];
            return $statistics;
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
        $select[] = "COUNT(DISTINCT ".$this->getEntityTable($statEntity).".id ) as count";
        $select[] = "SUM(".$this->getEntityTable($statEntity).".".$statTotalAmountField.") as total_amount";
        $select[] = $this->getEntityTable($statEntity).".currency as currency";

        if($this->getReportName() == 'sybunt' || $this->getReportName() == 'lybunt')
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
        if($this->getReportName() == 'repeat_contributions_detailed' || $this->getReportName() == 'recurring_contributions_summary')
        {
            if($this->getReportName() == 'repeat_contributions_detailed') {
                $showRepeatContributionStats = true;
                $range_one_statistics = $range_two_statistics = $range_one_avg =$range_two_avg = $range_one_total_contribution_count = $range_two_total_contribution_count =  [];
            }
            
            if($this->getReportName() == 'recurring_contributions_summary') {
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
            . " " . $this->_groupBy;

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
            $currAmount[$dao->currency] += $dao->total_amount;
            $currCount[$dao->currency]  += $dao->count;
        
            $totalCount += $dao->count;

            if (!in_array($dao->currency, $currencies)) {
                $currencies[] = $dao->currency;
            } 

            if ($showDetailed) {
                $currFees[$dao->currency]   = $currFees[$dao->currency]     ?? 0;
                $currNet[$dao->currency]    = $currNet[$dao->currency]      ?? 0;
                $currAvg[$dao->currency]    = $currAvg[$dao->currency]      ?? 0;
            
                //defining currency fees,Net and avg based upon currency
                $currFees[$dao->currency] += $dao->fees;
                $currNet[$dao->currency] += $dao->net;
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
        $statistics['counts']['count'] = [
            'title' => ts('Total Contributions'),
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
            unset($statistics['counts']['count']);
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
        return $statistics;
    }


    private function fieldWithLink(string $fieldName,&$rows,$row,$rowNum){
        $string = '';
        switch ($fieldName) {
            case 'display_name':
            case 'sort_name':
                if (array_key_exists($fieldName, $row) &&!empty($rows[$rowNum][$fieldName]) && array_key_exists('civicrm_contact_id', $row)) {
                    $separator = ($this->_outputMode !== 'csv') ? "<br/>" : ' ';
                    $url = CRM_Utils_System::url("civicrm/contact/view",
                    'reset=1&cid=' . $row['civicrm_contact_id'],
                    $this->_absoluteUrl);
                    $value = CRM_Utils_Array::value($fieldName, $row);
                    $string = $string . ($string ? $separator : '') ."<a href='{$url}'>{$value}</a> ";
                    $rows[$rowNum][$fieldName] = $string;
                }
                break;
            case 'total_amount':
                if($this->_settings['type'] == 'detailed' && $this->_settings['entity'] == 'contribution')
                if ($value = CRM_Utils_Array::value('total_amount', $row)) {
                    if (CRM_Core_Permission::check('access CiviContribute')) {
                        $separator = ($this->_outputMode !== 'csv') ? "<br/>" : ' ';
                        $url = CRM_Utils_System::url("civicrm/contact/view/contribution",
                        [
                            'reset' => 1,
                            'id' => $row['civicrm_contribution_contribution_id'],
                            'cid' => $row['civicrm_contact_id'],
                            'action' => 'view',
                            'context' => 'contribution',
                            'selectedChild' => 'contribute',
                        ],$this->_absoluteUrl);
                        $string = $string . ($string ? $separator : '') .
                        "<a href='{$url}'>".CRM_Utils_Money::format($value)."</a> ";
                        $rows[$rowNum]['total_amount'] = $string;
                    }
                }
                break;
            case 'receive_date_start':
                if($this->isFiscalQuarterReport()){
                    if(!in_array($row['receive_date_start'],['Total','Grand Total'])){
                    $latestContribReport = $this->getReportInstanceDetailsByName('Latest Contributions (Dashlet)');
                    $dateReformat=date("F Y",strtotime($row['receive_date_start']));
                    if($this->getReportType() == 'fiscal')
                    {
                        $dateStart = date('Ym01', strtotime($row['receive_date_start']));
                        $dateEnd = date('Ymt', strtotime($row['receive_date_start']));
                    }else{
                        $year = date('Y', strtotime($row['receive_date_start']));
                        $month = 3 * $row['quartername'] -2;
                        $dateStart = date('Ym01', strtotime($year.'-'.$month.'-01'));
                        $dateEnd = date("Ymd", mktime(0, 0, 0, $month + 3,1 - 1, $year));
                    }
                    $url = CRM_Report_Utils_Report::getNextUrl('instance/'.$latestContribReport['id'],
                    "reset=1&force=1&receive_date_from={$dateStart}&receive_date_to={$dateEnd}");
                        $string = $string . ($string ? $separator : '') .
                        "<a href='{$url}'>".$dateReformat."</a> ";
                        $rows[$rowNum]['receive_date_start'] = $string;
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
            //$filters['civicrm_contribution']['contribution_status_id']['default'] = [1];
        }
        return $filters;
    }

    private function fixFieldTitle(string $fieldName, &$title) {
        switch ($fieldName) {
            case 'financial_type':
                $title = ts('Fund');
                break;
            case 'campaign_id':
                $title = ts('Campaign');
                break;
            case 'receive_date':
                $title = ts('Date received');
                break;
            case 'financial_type_id':
                $title = ts('Fund');
                break;
            case 'email':
                $title = ts('Email');
                break;
            case 'phone':
                $title = ts('Phone');
                break;
            case 'display_name':
                $title = ts('Prospect');
                break;
            case 'do_not_phone':
                $title = ts('Do Not Phone');
                break;
            case 'is_opt_out':
                $title = ts('Is Opt Out');
                break;
            case 'do_not_mail':
                $title = ts('Do Not Mail');
                break;
            case 'is_deceased':
                $title = ts('Is Deceased');
                break;
            case 'do_not_sms':
                $title = ts('Do Not SMS');
                break;
            case 'preferred_language':
                $title = ts('Preffered Language');
                break;
            case 'do_not_email':
                $title = ts('Do Not Mail');
                break;
            case 'do_not_trade':
                $title = ts('Do Not Trade');
                break;
            case 'job_title':
                $title = ts('Job Title');
                break;
        }
    }
    //set default fields and group by checkbox checked according to default field defined
    private function setDefaultColumn(string $fieldName, &$field) {
        foreach( ['fields','group_bys'] as $entityName) {
            switch ($entityName) {
                case 'fields':
                case 'group_bys':
                    //if default field would llok like [x]
                    //if preSelected then on page load field would be checked
                    if($this->_settings['fields'][$fieldName]['default'] || $this->_settings['fields'][$fieldName]['preSelected']){
                        $field[$entityName][$fieldName]['default'] = TRUE;
                    }else{
                        unset($field[$entityName][$fieldName]['default']);  
                    }
                break;
            }
        }
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
        // TODO: optimize
        if(!empty($this->getDefaultColumns()))
        {
            unset($defaults['order_bys']);
            foreach($this->getDefaultColumns() as $value)
            {
                $defaults['order_bys'][] = ['column'=>$value,'order'=>'ASC'];
            }
        }
        return $defaults;
    }

    // TODO: explain + rename
    private function fixFieldStatistics(string $fieldName, &$statistics) {
        switch ($fieldName) {
            case 'total_amount':
                $statistics['statistics'] = [
                    'count' => ts('Number of Contributions'), 
                    'sum' => ts('Total Amount')
                ];
                break;
        }
    }

    // todo: this should be switched to public once all reporting refactoring is done
    static function fixFilterOption(string $fieldName, &$filterData) {
        switch ($fieldName) {
            case 'contribution_page_id':
                // TODO: deprecated
                $filterData['options'] = CRM_Contribute_PseudoConstant::contributionPage(NULL, TRUE);
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
        if(isset($fieldInfo['custom'])){
            $entityTableName = EU::getTableNameByName($fieldInfo['group_name']);
            if($select)
            {
                $entityTableName = isset($fieldInfo['dependent_table_entity'])? $this->getEntityTable($fieldInfo['dependent_table_entity']): $entityTableName;
            }
          }else{
            $entityTableName = $this->getEntityTable($fieldInfo['entity']);
            if($select)
            {
               $entityTableName = isset($fieldInfo['dependent_table_entity'])? $this->getEntityTable($fieldInfo['dependent_table_entity']): $this->getEntityTable($fieldInfo['entity']);
            }
            
          }
        return $entityTableName;
    }
    //get entity clause field through fieldName 'tablename.columnName'
    public function getEntityClauseFromField($fieldName, $forceId = false) : string {
        $fieldInfo = $this->getFieldInfo($fieldName);
        $isCalculatedField = isset($fieldInfo['calculated_field']) && $fieldInfo['calculated_field'] === true;
        $entityTable = $fieldInfo['table_alias'] ?? $this->getEntityTableFromField($fieldName);
        $entityField = ($forceId) ? 'id' : $this->getEntityField($fieldName);
        //$entityField =  $this->getEntityField($fieldName);
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
                    $from[] = "LEFT JOIN ".$this->getEntityTable('contribution')." as ".$aliasTableName." ON ".$this->getEntityTable('contribution').".id = civicrm_contribution_primaryset.id";
                    if (in_array($filterFieldName,$this->getFieldNamesForFilters())){
                        if($this->_params[$filterFieldName.'_relative'])
                        $relative = $this->_params[$filterFieldName.'_relative'];
                        if($this->_params[$filterFieldName.'_from'])
                        $from = $this->_params[$filterFieldName.'_from'];
                        if($this->_params[$filterFieldName.'_to'])
                        $to = $this->_params[$filterFieldName.'_to'];
                        $intialFilterDateRange = CRM_Utils_Date::getFromTo($relative, $from, $to);
                        $firstDateRange = $intialFilterDateRange[0];
                        $secondDateRange = $intialFilterDateRange[1];
                        if(isset($firstDateRange) && isset($secondDateRange))
                        $from[] = "AND ( ".$aliasTableName.".receive_date >= ".$firstDateRange.") 
                        AND ( ".$aliasTableName.".receive_date <= ".$secondDateRange.")";
                    }
                    $alreadyIncluded = true;
                    $this->_fromEntity[] = $aliasTableName;
                    break;
                // case 'range_one_stat':
                //     $from[] = "LEFT JOIN ".$this->getEntityTable('contribution')." as civicrm_contribution_primaryset ON ".$this->getEntityTable('contribution').".id = civicrm_contribution_primaryset.id";
                //     $filterFieldName = 'repeat_contri_initial_date_range';
                //     if (in_array($filterFieldName,$this->getFieldNamesForFilters())){
                //         if($this->_params[$filterFieldName.'_relative'])
                //         $relative = $this->_params[$filterFieldName.'_relative'];
                //         if($this->_params[$filterFieldName.'_from'])
                //         $from = $this->_params[$filterFieldName.'_from'];
                //         if($this->_params[$filterFieldName.'_to'])
                //         $to = $this->_params[$filterFieldName.'_to'];
                //         $intialFilterDateRange = CRM_Utils_Date::getFromTo($relative, $from, $to);
                //         $firstDateRange = $intialFilterDateRange[0];
                //         $secondDateRange = $intialFilterDateRange[1];
                //         if(isset($firstDateRange) && isset($secondDateRange))
                //         $from[] = "AND ( civicrm_contribution_primaryset.receive_date >= ".$firstDateRange.") 
                //         AND ( civicrm_contribution_primaryset.receive_date <= ".$secondDateRange.")";
                //     }
                //     $alreadyIncluded = true;
                //     $this->_fromEntity[] = 'civicrm_contribution_primaryset';
                //     break;
                // case 'range_two_stat':
                //     $from[] = "LEFT JOIN ".$this->getEntityTable('contribution')." as civicrm_contribution_secondset  ON ".$this->getEntityTable('contribution').".id = civicrm_contribution_secondset.id";
                //     $filterFieldName = 'repeat_contri_second_date_range';
                //     if (in_array($filterFieldName,$this->getFieldNamesForFilters())){
                //         if($this->_params[$filterFieldName.'_relative'])
                //         $relative = $this->_params[$filterFieldName.'_relative'];
                //         if($this->_params[$filterFieldName.'_from'])
                //         $from = $this->_params[$filterFieldName.'_from'];
                //         if($this->_params[$filterFieldName.'_to'])
                //         $to = $this->_params[$filterFieldName.'_to'];
                //         $intialFilterDateRange = CRM_Utils_Date::getFromTo($relative, $from, $to);
                //         $firstDateRange = $intialFilterDateRange[0];
                //         $secondDateRange = $intialFilterDateRange[1];
                //         if(isset($firstDateRange) && isset($secondDateRange))
                //         $from[] = "AND ( civicrm_contribution_secondset.receive_date >= ".$firstDateRange.") 
                //         AND ( civicrm_contribution_secondset.receive_date <= ".$secondDateRange.")";
                //     }
                //     $alreadyIncluded = true;
                //     $this->_fromEntity[] = 'civicrm_contribution_secondset';
                //     break;

                case ($entityName === 'civicrm_batch'):
                    if (!$alreadyIncluded) {
                        $from[] = "LEFT JOIN ".$this->getEntityTable('entity_batch').
                            " ON  ".$this->getEntityTable('financial_trxn').".id = ".$this->getEntityTable('entity_batch').".entity_id". 
                            " AND ".$this->getEntityTable('entity_batch').".entity_table = 'civicrm_financial_trxn'";
                
                        $from[] = $this->getSQLJoinForField('batch_id', $this->getEntityTable('batch'), $this->getEntityTable('entity_batch'),'id');
                        $this->_fromEntity[] = $entityName;
                        $alreadyIncluded = true;
                    }
                    
                    break;
            }

            // adding financial_account_debit / credit
            if ( $fieldInfo['entity'] == "financial_account" || 
                (($fieldInfo['join_entity'] == "financial_account_debit" || $fieldInfo['join_entity'] == "financial_account_credit") 
                    && !in_array($fieldInfo['join_entity'],$this->_fromEntity)) || $fieldInfo['join_entity'] == "address") {

                // adding financial_trxn joins
                $prerequisiteTable = "financial_trxn";

                if (!in_array($this->getEntityTable($prerequisiteTable),$this->_fromEntity) ) {

                    $from[] = "LEFT JOIN ".$this->getEntityTable('entity_' . $prerequisiteTable)." as ".$this->getEntityTable('entity_' . $prerequisiteTable).
                    " ON (".$this->getEntityTable('contribution').".id = ".$this->getEntityTable('entity_' . $prerequisiteTable).".entity_id ".
                    " AND ".$this->getEntityTable('entity_' . $prerequisiteTable).".entity_table = 'civicrm_contribution')";
                
                    $from[] = "LEFT JOIN ".$this->getEntityTable($prerequisiteTable)." as ".$this->getEntityTable($prerequisiteTable).
                    " ON ".$this->getEntityTable($prerequisiteTable).".id = ".$this->getEntityTable('entity_' . $prerequisiteTable).".financial_trxn_id";
            
                    $this->_fromEntity[] = $this->getEntityTable($prerequisiteTable);
                    
                }

                // adding financial_account joins
                if ( !in_array("financial_account_debit",$this->_fromEntity) && !in_array("financial_account_credit",$this->_fromEntity) ) {
        
                    $prerequisiteTable = "financial_account";

                    $from[] = "LEFT JOIN ".$this->getEntityTable($prerequisiteTable)." as financial_account_debit".
                        " ON financial_account_debit.id = ".$this->getEntityTable('financial_trxn').".to_financial_account_id";
                    $from[] = "LEFT JOIN ".$this->getEntityTable($prerequisiteTable)." as financial_account_credit".
                        " ON financial_account_credit.id = ".$this->getEntityTable('financial_trxn').".from_financial_account_id";
                            
                    $this->_fromEntity[] = "financial_account_debit";
                    $this->_fromEntity[] = "financial_account_credit";

                    
                
                }

                //Adding predefine address joins for join_entity
                if ($fieldInfo['join_entity'] == "address" && !in_array($this->getEntityTable($fieldInfo['entity']),$this->_fromEntity)) {
        
                    if(!in_array($this->getEntityTable($fieldInfo['join_entity']),$this->_fromEntity))
                    {
                        $from[] = $this->getSQLJoinForField('id', $this->getEntityTable($fieldInfo['join_entity']), $this->getEntityTable('contact'),'contact_id');
                        $this->_fromEntity[] = $this->getEntityTable('address');
                    }       
                    $from[] = $this->getSQLJoinForField($fieldInfo['join_field_name'], $entityName, $this->getEntityTable($fieldInfo['join_entity']),'id');
                    $this->_fromEntity[] = $this->getEntityTable($fieldInfo['entity']);
                }
        
                

            }

            //if(!in_array($trialValue,$this->_fromEntity) || (isset($fieldInfo['select_name']) && $fieldInfo['select_name'] === 'option_value')) {
            if(!$alreadyIncluded) {

                //option value
                if (isset($fieldInfo['select_name']) && $fieldInfo['select_name'] === 'option_value' ) {
                    
                    // custom fields + option value
                    if ($fieldInfo['custom'] === true) { 
                        $from[] = $this->getSQLJoinForField($fieldInfo['join_field_name'], $entityName, $this->getEntityTable($fieldInfo['join_entity']),'entity_id');
                    }
                    
                    if ($fieldInfo['custom'] !== true && isset($fieldInfo['join_entity']) && isset($fieldInfo['join_field_name'])) {
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
                    $from[] = $this->getSQLJoinForField($fieldInfo['join_field_name'], $entityName, $this->getEntityTable($fieldInfo['join_entity']),'contact_id');
                
                //entity_tag fields
                } else if($fieldInfo['join_entity'] === 'entity_tag'){ 
                    $from[] = $this->getSQLJoinForField('id', $this->getEntityTable($fieldInfo['join_entity']), $this->getEntityTable('contact'),'entity_id');
                    $from[] = $this->getSQLJoinForField($fieldInfo['join_field_name'], $entityName, $this->getEntityTable($fieldInfo['join_entity']),'id');
                
                // contribution and other entity fields
                } else {
                    
                    if(!in_array($entityName,$this->_fromEntity))
                    {

                    
                    $joinFieldName = ( preg_match('/_id$/', $fieldInfo['join_field_name']) ) ? 'id' : $this->getEntityField($fieldName);    
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
          }else{
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

}

?>