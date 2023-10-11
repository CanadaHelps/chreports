<?php
use CRM_Canadahelps_ExtensionUtils as EU;
use CRM_Chreports_ExtensionUtil as E;
class CRM_Chreports_Reports_BaseReport {

    private $_id;
    private $_name;
    private $_settings = [];
    private $_pagination = FALSE;

    private $_entity  = 'contribution';
    protected $_columns = [];
    protected $_mapping = [];
    public $_params = [];

    
    protected $_select = NULL;
    protected $_selectClauses = [];
    protected $_columnHeaders = [];

    protected $_from;

    protected $_groupBy = '';

    protected $_orderBy = NULL;
    protected $_orderByFields = [];

    protected $_where = NULL;
   
    public $_filters = NULL;

    
    // Default filters for report
    protected $_defaultFilters = 
    [
        "receive_date",
        "receipt_date",
        "contribution_status_id",
        "contribution_page_id",
        "financial_type_id",
        "contribution_recur_id",
        "total_amount",
        "non_deductible_amount",
        "total_sum",
        "total_count",
        "campaign_id",
        "card_type_id",
        "batch_id",
        "amount",
        "tagid",
        "gid",
        "total_sum",
        "total_count",
        "total_avg",
        "contribution_source",
        "payment_instrument_id",
        "campaign_type",
        "ch_fund"
    ];

    public function __construct( string $entity, int $id, string $name ) {

        $this->_entity = strtolower($entity);
        $this->_id = $id;
        $this->_name = $name;
        
        $this->loadSettings();
    }

    
    
    /**
     * 
     * Loads the report configuration from a JSON file
     * 
     * Template File: <report_id>.json
     * Saved report: <report_id>_<id>.json
     * 
     * @return void
     */
    private function loadSettings(): void {
        //get the values from json file based upon the name of the report
        $reportInstanceDetails = $this->getReportInstanceDetails($this->_id);
        $this->_settings = $this->_fetchConfigSettings($reportInstanceDetails);
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
        return "civicrm_" . $entity;
    }
    
    /**
     * 
     * Returns the report configuration
     *
     * @return array
     */
    public function getSettings(): array {
        return $this->_settings;
    }

    /**
     *
     * Returns the report action
     *
     * @return string
     */
    public function getAction(): string {
        return $this->_action;
    }

    // Set Form Action
    public function setAction(string $action) {
        $this->_action = $action;
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
            $filterValues = $this->_defaultFilters;
        }
        if (count($this->_settings['filters']) > 0) {
            $filterValues = array_merge($filterValues, $this->_settings['filters']);
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
    
    //Opportunity array defined for fields, orderby, filters
    public function setOpportunityFields(&$var) {
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
    //get type of the report from settings
    public function getReportType(): string {
        return $this->_settings['type'];
    }
    //get type of the report from settings
    public function isMonthlyYearlyReport(): bool {
        if($this->_settings['type'] == 'monthly' || $this->_settings['type'] == 'yearly')
        {
            return true; 
        }
        return false;
    }

    //check if report is opportunity report
    public function isOpportunityReport(): bool {
        if($this->_settings['name'] == 'opportunity_details' && $this->_settings['entity'] == 'grant')
        {
            $this->setEntity($this->_settings['entity']);
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
                        $field['dbAlias'] = $field['table_name'] . "." .  ((isset($field['column_name'])) ? $field['column_name'] : $field['name']);
                        $filters[$fieldName] = $field;
                    }
                }
            }
        }

        $this->_filters = $filters;
    }

    /**
     * Fetch the JSON Config from different paths depending on the Report Instance & Report ID type
     *
     * @param array $reportInstanceDetails Contains name, title, id, report_id and created_id
     * @return void
     */
    private function _fetchConfigSettings($reportInstanceDetails) {
        $fileName = self::escapeFileName($reportInstanceDetails['name']);
        $sourcePath = dirname(__DIR__, 1)  . "/Templates/" . $fileName.'.json';

        //For the Custom Saved reports, fetch it from Files folder instead
        if($reportInstanceDetails['created_id']) {
            $basePath = CRM_Core_Config::singleton()->uploadDir;
            $report_id = self::escapeFileName($reportInstanceDetails['report_id']);
            $sourcePath = $basePath. 'reports/saved/'. $reportInstanceDetails['created_id']. '_' . $report_id . '_' . $reportInstanceDetails['id']. '.json';
        }
        if (is_file($sourcePath)) {
            return json_decode(file_get_contents($sourcePath),true);
        }
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
        $filterPresets = $this->getSettings()['preset_filter_values'];

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
    private function getCustomFilterValues(): array {
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
        $entityTable = $this->getEntityTable($fieldEntity);
       
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
    public function filteringReportOptions(&$var) {
        // merge opportunity array defined in BaseReport class with the existing
        if($this->isOpportunityReport())
        $this->setOpportunityFields($var);

        // stores field configuration so we can use it later on
        $this->setFieldsMapping($var);
        
        // Columns
        $this->filteringReportFields($var);
        // Columns: Custom fields
        $this->filteringReportAddCustomField('ch_fund',$var); //CH Fund 
        
        // Grouping
        $this->filteringReportGroupOptions($var);

        // Filters
        $this->filteringReportFilterOptions($var);
        // Filters: Custom Fields
        $this->filteringReportAddCustomFilter('contribution_source',$var); //Contribution Source
        $this->filteringReportAddCustomFilter('payment_instrument_id',$var); //Payment Method
        $this->filteringReportAddCustomFilter('campaign_type',$var); //Campaign Type
        $this->filteringReportAddCustomFilter('ch_fund',$var); //CH Fund
        $this->filteringReportAddCustomFilter('application_submitted',$var); //Application Submitted
        $this->filteringReportAddCustomFilter('probability',$var); //Probability
        $this->filteringReportAddCustomFilter('Opportunity_Name',$var); //Opportunity Name
        $this->filteringReportAddCustomFilter('Opportunity_Owner',$var); //Opportunity Owner
    }

    private function filteringReportFields(&$var) {
        foreach ($var as $entityName => $entityData) {
            if($this->isOpportunityReport())
            unset($var[$entityName]['order_bys']);
            foreach ($entityData['fields'] as $fieldName => $fieldData) {
                    
                // We do not want to show this field
                if (!in_array($fieldName, $this->getColumnsFromConfig())) {
                    unset($var[$entityName]['fields'][$fieldName]);
                    if($this->_settings['type'] == 'detailed' && $this->_settings['entity'] == 'contribution')
                    unset($var[$entityName]['order_bys'][$fieldName]);
            
                // We want this
                } else {   
                    //Modify field by adding 'select_clause_alias' param to retrieve tablename.fieldname value for select,section clause
                    $this->modifyFieldParams($fieldName, $var[$entityName]);
                    //set default field
                    $this->setDefaultColumn($fieldName, $var[$entityName]);
                    // Fix empty / different titles
                    $this->fixFieldTitle($fieldName, $var[$entityName]['fields'][$fieldName]['title']);
                    $this->fixFieldStatistics($fieldName, $var[$entityName]['fields'][$fieldName]);

                    // Assigning order bys options based on fields
                    // Adding missing title to order by options
                    $orderByFieldList = ["total_amount","currency","id"];
                    //Remove Sorting tab from reporting fields base upon orderByDisplay params from json
                    if(isset($this->_settings['orderByDisplay']) && $this->_settings['orderByDisplay'] === false)
                    {
                        unset($var[$entityName]['order_bys'][$fieldName]);
                    }else{
                        if($this->_settings['type'] == 'detailed' && $this->_settings['entity'] == 'contribution'){
                            array_shift($orderByFieldList);
                        }
                        if(!in_array($fieldName,$orderByFieldList)){
                            $var[$entityName]['order_bys'][$fieldName] = [
                                'title' => $var[$entityName]['fields'][$fieldName]['title']
                            ];
                        }
                    }
                }
            }
        }
    }
    //Add Custom fields to fields and group by and order by section
    private function filteringReportAddCustomField($fieldName,&$var) {
            foreach($this->getColumnsFromConfig() as $key => $fieldName){
            switch ($fieldName) {
                case 'ch_fund':
                    $fieldDetails = [];
                    $customTablename = EU::getTableNameByName('Additional_info');
                    $trial = EU::getCustomFieldID('Fund');
                    $fieldDetails = $this->_mapping[$customTablename]['fields'][$trial];
                    $fieldDetails ['table_name'] = $customTablename;
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'gl_account':
                     $fieldDetails = [
                                'title' => E::ts('Financial Account'),
                                'name' => 'name',
                                'select_clause_alias' => $this->getEntityTable('financial_account').'.name',
                                'table_name' => $this->getEntityTable('financial_account'),
                                'dbAlias' => $this->getEntityTable('financial_account').'.name',
                            ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'gl_code':
                    $fieldDetails = [
                                'title' => E::ts('GL Code'),
                                'name' => 'accounting_code',
                                'table_name' => $this->getEntityTable('financial_account'),
                                'dbAlias' => $this->getEntityTable('financial_account').'.accounting_code',
                            ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'account_type':
                    $fieldDetails = [
                        'title' => E::ts('Account Type'),
                        'name' => 'financial_account_type_id',
                        'select_clause_alias' => $this->getEntityTable('financial_account').'_'.$fieldName.'_value.label',
                        'table_name' => $this->getEntityTable('financial_account'),
                        'dbAlias' => $this->getEntityTable('financial_account').'.financial_account_type_id',
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'payment_instrument_id':
                    $fieldDetails = [
                        'title' => E::ts('Payment Method'),
                        'name' => 'payment_instrument_id',
                        'select_clause_alias' => $this->getEntityTable().'_'.$fieldName.'_value.label',
                        'table_name' => $this->getEntityTable(),
                        'dbAlias' => $this->getEntityTable().'.payment_instrument_id',
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'organization_name':
                    $fieldDetails = [
                        'title' => E::ts('Organization Name'),
                        'name' => 'organization_name',
                        'select_clause_alias' => $this->getEntityTable('contact').'.organization_name',
                        // 'op_group_alias' => TRUE,
                        'table_name' => $this->getEntityTable('contact'),
                        'dbAlias' => $this->getEntityTable('contact').'.organization_name',
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'application_submitted':
                    $customFieldName =  E::getColumnNameByName('application_submitted');
                    $customTablename = EU::getTableNameByName('Grant');
                    $fieldDetails = [
                        'title' => E::ts('Application Submitted'),
                        'name' => $customFieldName,
                        'table_name' => $customTablename,
                        'dbAlias' => $customTablename.$customFieldName,
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'probability':
                    $customFieldName =  E::getColumnNameByName('probability');
                    $customTablename = EU::getTableNameByName('Grant');
                    $fieldDetails = [
                        'title' => E::ts('Probability'),
                        'name' => $customFieldName,
                        'table_name' => $customTablename,
                        'select_clause_alias' => $customTablename.'_'.$fieldName.'_value.label',
                        'dbAlias' => $customTablename.$customFieldName,
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'Opportunity_Name':
                    $customFieldName =  E::getColumnNameByName('Opportunity_Name');
                    $customTablename = EU::getTableNameByName('Grant');
                    $fieldDetails = [
                        'title' => E::ts('Opportunity Name'),
                        'name' => $customFieldName,
                        'table_name' => $customTablename,
                        'dbAlias' => $customTablename.$customFieldName,
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'Opportunity_Owner':
                    $customFieldName =  E::getColumnNameByName('Opportunity_Owner');
                    $customTablename = EU::getTableNameByName('Grant');
                    $fieldDetails = [
                        'title' => E::ts('Opportunity Owner'),
                        'name' => $customFieldName,
                        'table_name' => $customTablename,
                        'select_clause_alias' => $this->getEntityTable('contact').'.display_name',
                        'dbAlias' => $customTablename.$customFieldName,
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
            }
        }
    }

    private function customFieldCreation($fieldName,&$var,$fieldDetails) {
        //Adding 'custom_alias' to custom fields so we can easily access alias for sort by section
        $fieldDetails['custom_alias'] = $this->getEntityTable()."_".$fieldName;
        foreach( ['fields','group_bys','order_bys'] as $entityName) {
            switch ($entityName) {
                case 'fields':
                case 'group_bys':
                    $var[$this->getEntityTable()][$entityName][$fieldName] = $fieldDetails;
                    $this->setDefaultColumn($fieldName, $var[$this->getEntityTable()]);
                    break;
                case 'order_bys':
                    $var[$this->getEntityTable()][$entityName][$fieldName] = [
                        'title' => $fieldDetails['title']
                    ];
                    break;
            }

        }
    }

    //Add 'select_clause_alias' params to form fields so get the field alias in select clause rather than replacing value in alterDisplay  
    private function modifyFieldParams($fieldName,&$var) {
            switch ($fieldName) {
                case 'contribution_page_id': //campaign
                    $var['fields'][$fieldName]['select_clause_alias'] = $this->getEntityTable('contribution_page').'.title';
                    break;
                case 'campaign_id': //campaign group
                    $var['fields'][$fieldName]['select_clause_alias'] = $this->getEntityTable('campaign').'.title';
                    break;
                case 'email':
                case 'phone': 
                    $var['fields'][$fieldName]['select_clause_alias'] = $this->getEntityTable($fieldName).'.'.$fieldName;
                    break;
                case 'grant_type_id': //Opportunity type
                case 'status_id': //Opportunity status
                    $var['fields'][$fieldName]['select_clause_alias'] = $this->getEntityTable().'_'.$fieldName.'_value.label';
                    $var['fields'][$fieldName]['custom_alias'] = $this->getEntityTable().'_'.$fieldName;
                    break;
            }
    }
    private function filteringReportGroupOptions(&$var) {
        foreach ($var as $entityName => $entityData) {
            foreach ($entityData['group_bys'] as $fieldName => $fieldData) {
                if(isset($this->_settings['groupByDisplay']) && $this->_settings['groupByDisplay'] === false)
                    {
                        unset($var[$entityName]['group_bys'][$fieldName]);
                    }else{
                    // We do not want to show this group_bys
                    if (!in_array($fieldName, $this->getColumnsFromConfig())) {
                        unset($var[$entityName]['group_bys'][$fieldName]);
                    }
                }
            }
        }
    }

    private function filteringReportFilterOptions(&$var) {
        foreach ($var as $entityName => $entityData) {
            foreach ($entityData['filters'] as $fieldName => $fieldData) {
                switch ($fieldName) {
                    //update title from 'non deductible amount' to 'Advantage amount'
                    case 'non_deductible_amount':
                       $var[$entityName]['filters'][$fieldName]['title'] = 'Advantage amount';
                       break;
                    }
                // We do not want to show this filters
                if (!in_array($fieldName, $this->getReportingFilters())) {
                    unset($var[$entityName]['filters'][$fieldName]);
                } else{
                    //modify filter option values if required
                    $this->fixFilterOption($fieldName, $var[$entityName]['filters'][$fieldName]['options']);
                    
                }
            }
        }
    }

    private function filteringReportAddCustomFilter($fieldName,&$var) {
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
            $columnInfo = $this->getFieldMapping( $entityName, $fieldName);
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
            if ($fieldName == 'financial_type') {
                $entityName = 'financial_type';
            } else{
                $entityName = $this->getEntity();
            }
            $columnInfo = $this->getFieldMapping( $entityName, $fieldName);
            $sortByAlias = ($columnInfo['custom_alias']) ? $columnInfo['custom_alias'] : $columnInfo['table_name'].'_'.$fieldName;
            // adding sort field to column headers
            $columnHeader[$sortByAlias] = [
                'title' => $columnInfo['title']
            ];
            // adding sort field to select clauses
            $selectStatement = ($columnInfo['select_clause_alias'] && $columnInfo['custom_alias']) ? $columnInfo['select_clause_alias'] : $columnInfo['table_name'] ."." . $columnInfo['name'];
            $select[] = $selectStatement ." as ". $sortByAlias;
        }
        $this->_select = array_merge( $this->_select, $select);
        $this->_selectClauses = array_merge( $this->_selectClauses, $select);
        $this->_columnHeaders = array_merge( $this->_columnHeaders, $columnHeader);
    }

    // manage display of resulting rows
    //TODO : try to replace campaign_id with the name it self in the query rather than performing additional alter display
    public function alterDisplayRows(&$rows) {
        foreach ($rows as $rowNum => $row) {
            //CRM-2063 - Use "Unassigned" as value in summary/Detailed  reports for NULL values
            foreach($this->_columns as $key=>$value)
            {
                if (array_key_exists($key, $row) )
                {
                    $fieldNameKey = ($row[$key]!= NULL)? $row[$key] : 'Unassigned';
                    $rows[$rowNum][$key] = $fieldNameKey;
                    //create function to convert field name to link for detailed report for sort_name and total_amount field
                    $this->fieldWithLink($key,$rows,$row,$rowNum);
                }
               
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

        if ($showDetailed) {
            $select[] = "ROUND(AVG(".$this->getEntityTable().".`total_amount`), 2) as avg";
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

        $currFees = $currNet = $currAvg = $feeAmount = $netAmount = $avgAmount = [];

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
                $filterData = CRM_Contribute_PseudoConstant::contributionPage(NULL, TRUE);
                break;
        
        }
    }

    /**
     * 
     * Returns Get info for a report instance
     *
     * @param int $id ID of the report 
     * @return array
     */
    static function getReportInstanceDetails( $id ): array {
        $result = civicrm_api3('ReportInstance', 'get', [
            'sequential' => 1,
            'return' => ["name", "title", "created_id", "report_id"],
            'id' => $id,
            ]);
    
        return $result['values'][0];

        // todo: use API4 after upgrade
        // $reportInstance = \Civi\Api4\ReportInstance::get(TRUE)
        // ->addSelect('name', 'title')
        // ->addWhere('id', '=', $reportId)
        // ->execute()
        // ->itemAt(1);
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

    /**
     *
     * Build Json File from all report params and values
     * @param string $action The task action coming from the form
     * @return array
     */
    public function buildJsonConfigSettings(string $action): array {
        $config = [];

        //Set Default Params
        $params = $this->getFormParams();
        $fields = $this->getColumns();
        $filters = $this->getCustomFilterValues();

        //Load Base template settings to get default Values
        $baseTemplateSettings = $this->_settings;
        $config['name'] = $params['title'];
        $config['title'] = $params['name'] ?? $params['title'];
        $config['preset_filter_values'] = $filters;

        // Copy Columns with preSelected and Default Values
        foreach ($baseTemplateSettings['fields'] as $fieldKey => $fieldValue) {
            if(isset($fieldValue['default'])) {
                $config['fields'][$fieldKey] = $fieldValue;
            } elseif ($fields[$fieldKey]) {
                $config['fields'][$fieldKey] = ['preSelected' => true];
            } else {
                $config['fields'][$fieldKey] = [];
            }
        }

        // Copy rest of the leftover settings if applicable
        foreach($baseTemplateSettings as $k => $v) {
            if (!isset($config[$k])) {
                $config[$k] = $baseTemplateSettings[$k];
            }
        }
        if($action == 'copy') {
            $config['is_copy'] = (int) 1;
        }
        return $config;
    }

    /**
     *
     * Convert Any strng into save compliant filename
     *
     * @return string
     */
    public static function escapeFileName(string $fileName): string {
        $jsonFileName = str_replace("(","", $fileName);
        $jsonFileName = str_replace(")","", $jsonFileName);
        $jsonFileName = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($jsonFileName)));
        return $jsonFileName;
    }

    /**
     *
     * Write the newly or edited JSON for the custom report
     * Save the entry to DB & Redirect to the newly created report
     * @param array $config contains the pre-compiled config array that has to be saved as it is
     * @param string $redirect Default set to true, redirect to the newly created report
     * @return void
     */
    public function writeJsonConfigFile(array $config, $redirect = 'true'): void {
        $params = $this->getFormParams();

        // Do Not update the name if already present
        $params['name'] = $params['name'] ?? $params['title'];

        // Save Report Instance to DB without formvalues
        $params['owner_id'] = 'null';
        if (!empty($params['add_to_my_reports'])) {
            $params['owner_id'] = CRM_Core_Session::getLoggedInContactID();
        }
        // Make Form Values, header and footer empty, not needed for custom reports
        unset($params['formValues']);
        unset($params['report_header']);
        unset($params['report_footer']);

        //Set Report ID
        $params['report_id'] = CRM_Report_Utils_Report::getValueFromUrl($this->_id);

        // Create the report
        $instance = CRM_Report_BAO_ReportInstance::create($params);

        // Create the filename and make sure to append the ID to the name
        $instance->name =  trim($instance->name). ' '. $instance->id;

        $basePath = CRM_Core_Config::singleton()->uploadDir;
        $report_id = self::escapeFileName($instance->report_id);
        $filePath = $basePath. 'reports/saved/'. $instance->created_id. '_' . $report_id . '_' . $instance->id. '.json';

        // Ensure the directory exists, create it if necessary
        if (!is_dir($basePath)) {
            print_r('here');die;
            return;
        }

        // Encode the $config array as JSON
        $config['name'] = $instance->name;
        $jsonConfig = json_encode($config, JSON_PRETTY_PRINT);

        // Write the JSON data to the file
        if (file_put_contents($filePath, $jsonConfig) !== false) {
            // Append Report Instance ID in the name of the newly created report
            civicrm_api3('ReportInstance', 'create', json_decode(json_encode($instance), true));

            // Set status, check for _createNew param to identify new report
            $statusMsg = ts('Your report has been successfully copied as "%1". You are currently viewing the new copy.', [1 => $instance->title]);
            CRM_Core_Session::setStatus($statusMsg, '', 'success');

            // Redirect to the new report
            if($redirect) {
                $urlParams = ['reset' => 1, 'force' => 1];
                CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/report/instance/{$instance->id}", $urlParams));
            }
        } else {
            // Error writing the file
            CRM_Core_Session::setStatus(ts("Cannot Create ".$instance->title." Report. Check Write Permission to Uploads directory."), ts('Report Save Error'), 'error');
            return;
        }
    }
}

?>