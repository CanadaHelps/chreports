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

    protected $_groupBy = '';

    protected $_orderBy = NULL;
    protected $_orderByFields = [];

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

    public function setAddressField(&$var) {
        return;
        $addressCols = [
            'civicrm_address' => [
                'dao' => 'CRM_Core_DAO_Address',
                'fields' => [
                    'address_name' => [
                        'title' => ts('Address Name'),
                        'default' => FALSE,
                        'name' => 'name',
                    ],
                'street_address' => [
                    'title' => ts('Address'),
                    'default' => FALSE,
                ],
                'supplemental_address_1' => [
                    'title' => ts('Supplementary Address Field 1'),
                    'default' => FALSE,
                ],
                'supplemental_address_2' => [
                    'title' => ts('Supplementary Address Field 2'),
                    'default' => FALSE,
                ],
                'supplemental_address_3' => [
                    'title' => ts('Supplementary Address Field 3'),
                    'default' => FALSE,
                ],
                'street_number' => [
                    'title' => ts('Street Number'),
                    'default' => FALSE,
                ],
                'street_name' => [
                    'title' => ts('Street Name'),
                    'default' => FALSE,
                ],
                'street_unit' => [
                    'title' => ts('Street Unit'),
                    'default' => FALSE,
                ],
                'city' => [
                    'title' => 'City',
                    'default' => FALSE,
                ],
                'state_province_id' => [
                    'title' => 'Province',
                    'default' => FALSE,
                    'alter_display' => 'alterStateProvinceID',
                ],
                'postal_code' => [
                    'title' => 'Postal Code',
                    'default' => FALSE,
                ],
                'postal_code_suffix' => [
                    'title' => 'Postal Code Suffix',
                    'default' => FALSE,
                ],
                'country_id' => [
                    'title' => 'Country',
                    'default' => FALSE,
                    'alter_display' => 'alterCountryID',
                ],
                ],
                'grouping' => 'location-fields',
            ],
        ];
        $var = array_merge($var, $addressCols);
    }
    
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
                        $field['dbAlias'] = $field['table_name'] . "." .  ((isset($field['column_name'])) ? $field['column_name'] : $field['name']);
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
        $entityTable = $this->getEntityTable($fieldEntity);
       
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
     * 
     * Set GL Accountand Payment Method Reconciliation Report additional &var params
     *
     * @return array
     */

     public function setGLAccountandPaymentMethodReconciliationReport(&$var) {
        return;
        $specificColsforReport = [
            'civicrm_membership' => [
              'dao' => 'CRM_Member_DAO_Membership',
              'fields' => [
                'id' => [
                  'title' => ts('Membership #'),
                  'name'=>'id',
                  'no_display' => TRUE,
                  'required' => TRUE,
                ],
              ],
            ],
            'civicrm_financial_account' => [
              'dao' => 'CRM_Financial_DAO_FinancialAccount',
              'filters' => [
                'debit_accounting_code' => [
                  'title' => ts('Financial Account Code - Debit'),
                  'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                  'options' => CRM_Contribute_PseudoConstant::financialAccount(NULL, NULL, 'accounting_code', 'accounting_code'),
                  'name' => 'accounting_code',
                  'alias' => 'financial_account_civireport_debit',
                ],
                'debit_contact_id' => [
                  'title' => ts('Financial Account Owner - Debit'),
                  'operatorType' => CRM_Report_Form::OP_SELECT,
                  'type' => CRM_Utils_Type::T_INT,
                  'options' => ['' => '- Select Organization -'] + CRM_Financial_BAO_FinancialAccount::getOrganizationNames(FALSE),
                  'name' => 'contact_id',
                  'alias' => 'financial_account_civireport_debit',
                ],
                'credit_accounting_code' => [
                  'title' => ts('Financial Account Code - Credit'),
                  'type' => CRM_Utils_Type::T_INT,
                  'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                  'options' => CRM_Contribute_PseudoConstant::financialAccount(NULL, NULL, 'accounting_code', 'accounting_code'),
                  'name' => 'accounting_code',
                  'alias' => 'financial_account_civireport_credit',
                ],
                'credit_contact_id' => [
                  'title' => ts('Financial Account Owner - Credit'),
                  'operatorType' => CRM_Report_Form::OP_SELECT,
                  'type' => CRM_Utils_Type::T_INT,
                  'options' => ['' => '- Select Organization -'] + CRM_Financial_BAO_FinancialAccount::getOrganizationNames(FALSE),
                  'name' => 'contact_id',
                  'alias' => 'financial_account_civireport_credit',
                ],
                'debit_name' => [
                  'title' => ts('Financial Account Name - Debit'),
                  'type' => CRM_Utils_Type::T_STRING,
                  'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                  'options' => CRM_Contribute_PseudoConstant::financialAccount(),
                  'name' => 'id',
                  'alias' => 'financial_account_civireport_debit',
                ],
                'credit_name' => [
                  'title' => ts('Financial Account Name - Credit'),
                  'type' => CRM_Utils_Type::T_STRING,
                  'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                  'options' => CRM_Contribute_PseudoConstant::financialAccount(),
                  'name' => 'id',
                  'alias' => 'financial_account_civireport_credit',
                ],
              ],
            ],
            'civicrm_line_item' => [
              'dao' => 'CRM_Price_DAO_LineItem',
              'fields' => [
                'financial_type_id' => [
                  'title' => ts('Financial Type'),
                  'default' => TRUE,
                ],
              ],
              'filters' => [
                'financial_type_id' => [
                  'title' => ts('Financial Type'),
                  'type' => CRM_Utils_Type::T_INT,
                  'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                  'options' => CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search'),
                ],
              ],
              'order_bys' => [
                'financial_type_id' => ['title' => ts('Financial Type')],
              ],
            ],
            'civicrm_batch' => [
              'dao' => 'CRM_Batch_DAO_Batch',
              'fields' => [
                'title' => [
                  'title' => ts('Batch Title'),
                  'alias' => 'batch',
                  'default' => FALSE,
                  'name'=> 'title',
                ],
                'name' => [
                  'title' => ts('Batch Name'),
                  'alias' => 'batch',
                  'default' => TRUE,
                  'name'=> 'name'
                ],
              ],
            ],
            'civicrm_financial_trxn' => [
              'dao' => 'CRM_Financial_DAO_FinancialTrxn',
              'fields' => [
                'check_number' => [
                  'title' => ts('Cheque #'),
                  'default' => TRUE,
                ],
                'currency' => [
                  'required' => TRUE,
                  'no_display' => TRUE,
                ],
                'trxn_date' => [
                  'title' => ts('Transaction Date'),
                  'default' => TRUE,
                  'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
                  'table_name' => 'trxn',
                  'dbAlias' => 'trxn.trxn_date',
                ],
                'trxn_id' => [
                  'title' => ts('Trans #'),
                  'default' => TRUE,
                ],
                'card_type_id' => [
                  'title' => ts('Credit Card Type'),
                ],
              ],
              'filters' => [
                'payment_instrument_id' => [
                  'title' => ts('Payment Method'),
                  'type' => CRM_Utils_Type::T_INT,
                  'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                  'options' => CRM_Contribute_PseudoConstant::paymentInstrument(),
                ],
                'currency' => [
                  'title' => ts('Currency'),
                  'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                  'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
                  'default' => NULL,
                  'type' => CRM_Utils_Type::T_STRING,
                ],
                'trxn_date' => [
                  'title' => ts('Transaction Date'),
                  'operatorType' => CRM_Report_Form::OP_DATETIME,
                  'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
                ],
                'status_id' => [
                  'title' => ts('Financial Transaction Status'),
                  'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                  'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
                  'default' => [1],
                  'table_name' => 'civicrm_financial_trxn_report',
                  'dbAlias' => 'civicrm_financial_trxn_report.status_id',
                ],
                'card_type_id' => [
                  'title' => ts('Credit Card Type'),
                  'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                  'options' => CRM_Financial_DAO_FinancialTrxn::buildOptions('card_type_id'),
                  'default' => NULL,
                  'type' => CRM_Utils_Type::T_STRING,
                ],
              ],
              'order_bys' => [
                'payment_instrument_id' => ['title' => ts('Payment Method')],
                'trxn_date' => ['title' => ts('Transaction Date')],
              ],
            ],
            'civicrm_entity_financial_trxn' => [
              'dao' => 'CRM_Financial_DAO_EntityFinancialTrxn',
              'fields' => [
                'amount' => [
                  'title' => ts('Amount'),
                  'default' => TRUE,
                  'type' => CRM_Utils_Type::T_STRING,
                ],
              ],
              'filters' => [
                'amount' => ['title' => ts('Amount')],
              ],
            ],
        ];

        //Add contribution fields
        $var['civicrm_contribution']['fields']['invoice_id'] = [
                  'title' => ts('Invoice Reference'),
                  'default' => TRUE,
        ];
        $var['civicrm_contribution']['fields']['invoice_number'] = [
            'title' => ts('Invoice Number'),
            'default' => TRUE,
        ];
        $var['civicrm_contribution']['fields']['id'] = [
            'title' => ts('Contribution ID'),
            'default' => TRUE,
        ];
        $var = array_merge($var, $specificColsforReport);
    }

    /**
     * Updates the search form of the report based on configuration
     * 
     * @param array  $var Array used to show columns.
     * @return void
     */
    public function filteringReportOptions(&$var) {
       
        // merge opportunity array defined in BaseReport class with the existing
        //if($this->isOpportunityReport())
        //$this->setOpportunityFields($var);

        //if($this->getEntity() == 'contact') {
        //    $this->setAddressField($var);
        //}
        // Columns
        //CRM-2082 recurring contribution details
        // merge recurring contribution array defined in BaseReport class with the existing
        //if($this->isRecurringContributionReport())
        //$this->setRecurringContributionsFields($var);

        // merge GLAccountandPaymentMethodReconciliation contribution array defined in BaseReport class with the existing
        //if($this->isGLAccountandPaymentMethodReconciliationReport())
        //$this->setGLAccountandPaymentMethodReconciliationReport($var);
        //echo '<pre>';print_r($var);echo '</pre>';
        // stores field configuration so we can use it later on
        // $this->setFieldsMapping($var);
        
        // Fields
        $this->filteringReportFields($var);
        //echo '<pre>';print_r($var);echo '</pre>';
        // Columns: Custom fields
        //$this->filteringReportAddCustomField('ch_fund',$var); //CH Fund 
        
        // Grouping
        $this->filteringReportGroupOptions($var);

        // Filters
        $this->filteringReportFilterOptions($var);
        // Filters: Custom Fields
        // $this->filteringReportAddCustomFilter('contribution_source',$var); //Contribution Source
        // $this->filteringReportAddCustomFilter('payment_instrument_id',$var); //Payment Method
        // $this->filteringReportAddCustomFilter('campaign_type',$var); //Campaign Type
        // $this->filteringReportAddCustomFilter('ch_fund',$var); //CH Fund
        // $this->filteringReportAddCustomFilter('application_submitted',$var); //Application Submitted
        // $this->filteringReportAddCustomFilter('probability',$var); //Probability
        // $this->filteringReportAddCustomFilter('Opportunity_Name',$var); //Opportunity Name
        // $this->filteringReportAddCustomFilter('Opportunity_Owner',$var); //Opportunity Owner
        // //SYBNT fields
        // $this->filteringReportAddCustomFilter('on_hold',$var); //Opportunity Owner
        // $this->filteringReportAddCustomFilter('yid',$var); //Opportunity Owner
        // //top contributors
        // $this->filteringReportAddCustomFilter('total_range',$var); //Opportunity Owner
        // if($this->isRepeatContributionsReport())
        // {
        //     $this->filteringReportAddCustomFilter('total_lifetime_contributions',$var); //Total Lifetime Contributions
        //     $this->filteringReportAddCustomFilter('amount_of_last_contribution',$var); //Amount of last contribution
        //     $this->filteringReportAddCustomFilter('date_of_last_contribution',$var); //Date of Last Contribution
        //     $this->filteringReportAddCustomFilter('date_of_first_contribution',$var); //Date of First Contribution
        //     $this->filteringReportAddCustomFilter('largest_contribution',$var); //Largest Contribution
        //     $this->filteringReportAddCustomFilter('count_of_contributions',$var); //Count of Contributions
        // }
    }

    public function setRecurringContributionsFields(&$var) {

        $var['civicrm_phone']['fields']['phone']['title'] = E::ts('Phone');
        $var['civicrm_email']['fields']['email']['title'] = E::ts('Email');

        $specificCols2 = [
            'civicrm_address' => [
                'dao' => 'CRM_Core_DAO_Address',
                'fields' => [ 
                  'street_address' => ['title' => E::ts('Address - Primary')],
                  'city' => ['title' => E::ts('City')],
                  'postal_code' => ['title' => E::ts('Postal Code')],
                  'state_province_id' => ['title' => E::ts('State/Province')],
                  'country_id' => ['title' => E::ts('Country')],
                ],
                'grouping' => 'contact-fields',
              ],
              'civicrm_contribution' => [
                'dao' => 'CRM_Contribute_BAO_Contribution',
                'fields' => [
                  'total_amount' => [
                    'title' => E::ts('This Month Amount'),
                    'required' => TRUE,
                    'dbAlias' => "temp.this_month_amount",
                  ],
                  'source' => [
                    'title' => E::ts('Contribution Source'),
                  ],
                  'completed_contributions' => [
                    'title' => E::ts('Completed Contributions'),
                    'dbAlias' => 'temp.completed_contributions',
                  ],
                  'start_date' => [
                    'title' => E::ts('Start Date/First Contribution'),
                    'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
                    'dbAlias' => 'temp.start_date',
                  ],
                  'last_month_amount' => [
                    'title' => E::ts('Last Month Amount'),
                    'type' => CRM_Utils_TYPE::T_MONEY,
                    'required' => TRUE,
                    'dbAlias' => "temp.last_month_amount",
                  ],
                ],
                'filters' => [
                  'receive_date' => [
                    'title' => E::ts('Receive Date'),
                    'default' => 'this.month',
                    'operatorType' => CRM_Report_form::OP_DATETIME,
                    'type' => CRM_Utils_TYPE::T_DATE + CRM_Utils_Type::T_TIME,
                  ],
                  'campaign_id' => $var['civicrm_contribution']['filters']['campaign_id']
                ],
                'grouping' => 'contribute-fields',
              ],
        ];
        $var = array_merge($var, $specificCols2);

      }

    private function filteringReportFields(&$var) {
        
        /*
        var
            => civicrm_contribution
                => fields
                    => amount [...]

        template.json
            => fields
                => amount [...]
        */

        // clear var
        $var = [];

        foreach ($this->getAllColumns() as $fieldName => $fieldInfo) {
            $fieldInfo = array_merge( $fieldInfo, $this->getFieldInfo($fieldName) );
            
            // field not found
            if ( isset($fieldInfo['error']) ) {
                //echo "<pre>$fieldName => not found</pre>";
                continue;
            }
            if(isset($fieldInfo['custom'])){
                $entityName = EU::getTableNameByName($fieldInfo['group_name']);
                //echo $entityName = $this->getEntityTable();
                //echo 'hell yeah';
              }else{
                $entityName = $this->getEntityTable($fieldInfo['entity']);
              }
            
              $actualFieldName = ($fieldInfo['field_name']) ?? $fieldName;
            $filterType = $this->getFilterType($fieldName);
            //$entityName = $fieldInfo['entity'];
            $var[$entityName]['fields'][$fieldName] = [
                "title" => $fieldInfo["title"],
                "default" => ( $fieldInfo["default"] === true || $fieldInfo["selected"] === true ),
                "type" => $filterType['type'],
            ];
            //set order by fields //sort by
            $var[$entityName]['order_bys'][$fieldName] = [
                'title' => $fieldInfo["title"]
            ];
            //set group by
            $var[$entityName]['group_bys'][$fieldName] = [
                'title' => $fieldInfo["title"]
            ];

        }

        return;
        
        
        foreach ($var as $entityName => $entityData) {
            if($this->isOpportunityReport())
            unset($var[$entityName]['order_bys']);
            foreach ($entityData['fields'] as $fieldName => $fieldData) {
                    
                // We do not want to show this field
                if (!in_array($fieldName, $this->getColumnsFromConfig())) {
                    unset($var[$entityName]['fields'][$fieldName]);
                    //For add Detailed report fields order by parameters should be same as display column fields
                    if($this->_settings['type'] == 'detailed' && ($this->_settings['entity'] == 'contribution' || $this->_settings['entity'] == 'contact'))
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
                        if($this->_settings['type'] == 'detailed' && ($this->_settings['entity'] == 'contribution' || $this->_settings['entity'] == 'contact')){
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
                case 'receive_date_start': //for fiscal, quarterly report
                        if($this->isFiscalQuarterReport())
                        {
                            $title =($this->getReportType() == 'fiscal')? E::ts('Month') : E::ts('Quarter');
                        }
                    $fieldDetails = [
                        'title' => $title,
                        'name' => 'receive_date',
                        'table_name' => $this->getEntityTable(),
                        //'select_clause_alias' => $this->getEntityTable('contact').'.display_name',
                        'dbAlias' => $this->getEntityTable().'receive_date',
                        'type' => CRM_Utils_Type::T_STRING,
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'last_four_year_total_amount':
                    $fieldDetails = [
                        'title' => E::ts('Last 4rth Year Total'),
                        'type' => CRM_Utils_Type::T_MONEY,
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;   
                case 'last_three_year_total_amount':
                    $fieldDetails = [
                        'title' => E::ts('Last 3 Years total'),
                        'type' => CRM_Utils_Type::T_MONEY,
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break; 
                case 'last_two_year_total_amount':
                    $fieldDetails = [
                        'title' => E::ts('Last 2 Years total'),
                        'type' => CRM_Utils_Type::T_MONEY,
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break; 
                case 'on_hold':
                    $fieldDetails = [
                        'title' => E::ts('Email on hold'),
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break; 
                case 'last_year_total_amount':
                    $fieldDetails = [
                        'title' => ts('Last Year Total'),
                        'type' => CRM_Utils_Type::T_MONEY,
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break; 
                case 'civicrm_life_time_total':
                    $fieldDetails = [
                        'title' => ts('Lifetime Total'),
                        'type' => CRM_Utils_Type::T_MONEY,
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break; 
                //Repeat contributions report custom fields and extra fields
                case 'total_lifetime_contributions':
                    $fieldDetails = [];
                    $customTablename = EU::getTableNameByName('Summary_Fields');
                    $trial = EU::getCustomFieldID('Total_Lifetime_Contributions');
                    $fieldDetails = $this->_mapping[$customTablename]['fields'][$trial];
                    $fieldDetails ['table_name'] = $customTablename;
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'amount_of_last_contribution':
                    $fieldDetails = [];
                    $customTablename = EU::getTableNameByName('Summary_Fields');
                    $trial = EU::getCustomFieldID('Amount_of_last_contribution');
                    $fieldDetails = $this->_mapping[$customTablename]['fields'][$trial];
                    $fieldDetails ['table_name'] = $customTablename;
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'date_of_last_contribution':
                    $fieldDetails = [];
                    $customTablename = EU::getTableNameByName('Summary_Fields');
                    $trial = EU::getCustomFieldID('Date_of_Last_Contribution');
                    $fieldDetails = $this->_mapping[$customTablename]['fields'][$trial];
                    $fieldDetails ['table_name'] = $customTablename;
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'date_of_first_contribution':
                    $fieldDetails = [];
                    $customTablename = EU::getTableNameByName('Summary_Fields');
                    $trial = EU::getCustomFieldID('Date_of_First_Contribution');
                    $fieldDetails = $this->_mapping[$customTablename]['fields'][$trial];
                    $fieldDetails ['table_name'] = $customTablename;
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'largest_contribution':
                    $fieldDetails = [];
                    $customTablename = EU::getTableNameByName('Summary_Fields');
                    $trial = EU::getCustomFieldID('Largest_Contribution');
                    $fieldDetails = $this->_mapping[$customTablename]['fields'][$trial];
                    $fieldDetails ['table_name'] = $customTablename;
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'count_of_contributions':
                    $fieldDetails = [];
                    $customTablename = EU::getTableNameByName('Summary_Fields');
                    $trial = EU::getCustomFieldID('Count_of_Contributions');
                    $fieldDetails = $this->_mapping[$customTablename]['fields'][$trial];
                    $fieldDetails ['table_name'] = $customTablename;
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'range_one_stat':
                    $fieldDetails = [
                        'title' => E::ts('Range One Stat'),
                        'name' => 'range_one_stat',
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'range_two_stat':
                    $fieldDetails = [
                        'title' => E::ts('Range Two Stat'),
                        'name' => 'range_two_stat'
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                
                    //gl account and payment method reconciliation

                case 'debit_name':
                case 'debit_accounting_code':
                //case 'debit_contact_id':

                    if ($fieldName == "debit_name") {
                        $actualFieldNeme = 'name';  
                        $fieldTitle = 'GL Account Name - Debit'   ;  
                    }    
                    else if ($fieldName == "debit_accounting_code") {
                        $actualFieldNeme = 'accounting_code'; 
                        $fieldTitle = 'GL Account Code - Debit'   ;
                    } 
                    $fieldDetails = [
                                    'title' => E::ts($fieldTitle),
                                    'name' => $actualFieldNeme,
                                    'select_clause_alias' => $this->getEntityTable('financial_account').'_debit.' . $actualFieldNeme,
                                    'table_name' => $this->getEntityTable('financial_account'),
                                    //'dbAlias' => $this->getEntityTable('financial_account').'.' . $actualFieldNeme,
                                    'dbAlias' => $this->getEntityTable('financial_account').'_debit.' . $actualFieldNeme,
                                ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;

                case 'credit_name':
                case 'credit_accounting_code':
            
                    if ($fieldName == "credit_name") {
                        $actualFieldNeme = 'name';  
                        $fieldTitle = 'Financial Account Name - Credit'   ;  
                    }    
                    else if ($fieldName == "credit_accounting_code") {
                        $actualFieldNeme = 'accounting_code'; 
                        $fieldTitle = 'GL Account Code - Credit'   ;
                    } 
                    $fieldDetails = [
                        'title' => E::ts($fieldTitle),
                        'name' => $actualFieldNeme,
                        'select_clause_alias' => $this->getEntityTable('financial_account').'_credit.' . $actualFieldNeme,
                        'table_name' => $this->getEntityTable('financial_account'),
                        //'dbAlias' => $this->getEntityTable('financial_account').'.' . $actualFieldNeme,
                        'dbAlias' => $this->getEntityTable('financial_account').'_credit.' . $actualFieldNeme,
                    ];
                    $this->customFieldCreation($fieldName,$var,$fieldDetails);
                    break;
                case 'debit_contact_id':
                case 'credit_contact_id':
                    if ($fieldName == "debit_contact_id") {
                        $fieldTitle = 'GL Account Owner - Debit'   ;  
                        $fieldType = 'debit';
                    }    
                    else if ($fieldName == "credit_contact_id") {
                        $fieldTitle = 'GL Account Owner - Credit'   ;
                        $fieldType = 'credit';
                    } 
                    $fieldDetails = [
                        'title' => E::ts($fieldTitle),
                        'name' => 'organization_name',
                        'select_clause_alias' => $this->getEntityTable('contact').'_'.$fieldType.'.organization_name',
                        'table_name' => $this->getEntityTable('contact'),
                        //'dbAlias' => $this->getEntityTable('contact').'.organization_name',
                        'dbAlias' => $this->getEntityTable('contact').'_'.$fieldType.'.organization_name',    
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
                    
                case 'street_address':
                case 'city':
                case 'postal_code':
                case 'state_province_id':
                case 'country_id':
                    $var['fields'][$fieldName]['select_clause_alias'] = $this->getEntityTable('address').'.'.$fieldName;
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
        //echo "<pre> FILTERS => ".print_r($this->getReportingFilters(), true)."</pre>";
        foreach ($this->getReportingFilters() as $fieldName => $fieldInfo) {
            $fieldInfo = array_merge( $fieldInfo, $this->getFieldInfo($fieldName) );
            
            // field not found
            if ( isset($fieldInfo['error']) ) {
                //echo "<pre>$fieldName => not found</pre>";
                continue;
            }
            if(isset($fieldInfo['custom'])){
                $entityName = EU::getTableNameByName($fieldInfo['group_name']);
                //$entityName = $this->getEntityTable();
              }else{
                $entityName = $this->getEntityTable($fieldInfo['entity']);
              }
            //$entityName = $fieldInfo['entity'];
            $actualFieldName = ($fieldInfo['field_name']) ?? $fieldName;
            $filterType = $this->getFilterType($fieldName);
            $var[$entityName]['filters'][$fieldName] = [
                "name" => $actualFieldName,
                "title" => $fieldInfo["title"],
                "default" => $fieldInfo["default_value"] ?? '',
                "dataType" => $filterType["dataType"],
                "htmlType" => $filterType["htmlType"],
                "type" => $filterType["type"]
            ];


            if ( isset($fieldInfo['options']) && $fieldInfo['options'] === true ) {
                $var[$entityName]['filters'][$fieldName]["operatorType"] = CRM_Report_Form::OP_MULTISELECT;
               //print_r($this->getFilterOptions($fieldName));
                $var[$entityName]['filters'][$fieldName]["options"] = $this->getFilterOptions($fieldName);
                // TODO: options
            }

            // TODO: date / datetime

        }

        //echo "<pre> AFTER FILTERS => ".print_r($var, true)."</pre>";

        return;
        foreach ($var as $entityName => $entityData) {
            foreach ($entityData['filters'] as $fieldName => $fieldData) {
                
                // We do not want to show this filters
                
                    //modify filter option values if required
                    $this->fixFilterOption($fieldName, $var[$entityName]['filters'][$fieldName]);

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
            }
            else if($fieldName == 'sort_name' || $fieldName == 'first_name' || $fieldName == 'last_name' || $fieldName == 'organization_name'|| $fieldName == 'exposed_id')
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
            else{
                $entityName = $this->getEntity();
            }
            $columnInfo = $this->getFieldMapping( $entityName, $fieldName);
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
           }else{
            $selectStatement = ($columnInfo['select_clause_alias'] && $columnInfo['custom_alias']) ? $columnInfo['select_clause_alias'] : $columnInfo['table_name'] ."." . $columnInfo['name'];
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

    public function getEntityTableFromField($fieldName,$select = NULL) : string {
        $fieldInfo = $this->getFieldInfo($fieldName);
        if(isset($fieldInfo['custom'])){
            $entityTableName = EU::getTableNameByName($fieldInfo['group_name']);
          }else{
            $entityTableName = $this->getEntityTable($fieldInfo['entity']);
            if($select)
            {
                $entityTableName = isset($fieldInfo['dependent_table_entity'])? $this->getEntityTable($fieldInfo['dependent_table_entity']): $this->getEntityTable($fieldInfo['entity']);
            }
            
          }
        return $entityTableName;
    }

    public function getEntityClauseFromField($fieldName) : string {
        $fieldInfo = $this->getFieldInfo($fieldName);
        $entityTable = $this->getEntityTableFromField($fieldName);
        if(isset($fieldInfo['custom'])){
            $entityField = E::getColumnNameByName($fieldInfo['custom_fieldName']);
          }else{
            $entityField = (isset($fieldInfo['field_name']))? $fieldInfo['field_name']: $fieldName;
          }
        return $entityTable.'.'.$entityField;
    }
}

?>