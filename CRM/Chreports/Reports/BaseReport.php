<?php
use CRM_Canadahelps_ExtensionUtils as EU;
use CRM_Chreports_ExtensionUtil as E;
class CRM_Chreports_Reports_BaseReport {

    private $_id;
    private $_name;
    private $_settings = [];

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

    protected $_where = NULL;
   
    public $_filters = NULL;

    protected $_isPagination = FALSE;
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

    private function loadSettings() {
        //get the values from json file based upon the name of the report
        $jsonFileName = strtolower($this->_name);
        $jsonFileName = str_replace("(","", $jsonFileName);
        $jsonFileName = str_replace(")","", $jsonFileName);
        $jsonFileName = str_replace(" ","_", $jsonFileName);

        $sourcePath = dirname(__DIR__, 1)  . "/Config/" . $jsonFileName.'.json';
        if (is_file($sourcePath)) {
            $this->_settings = json_decode(file_get_contents($sourcePath),true); 
        }
    }

    public function getEntity(): string {
        return $this->_entity;
    }
    //get the name of the entity table. Default entity is contribution
    public function getEntityTable(string $entity = null): string {
        $entity = ($entity != NULL) ? $entity : $this->getEntity();
        return "civicrm_" . $entity;
    }
    // access settings from extendedSummary
    public function getSettings(): array {
        return $this->_settings;
    }
    // extract reporting fields from JSON file
    public function getReportingFields(): array {
        return array_keys($this->_settings['fields']);
    }
    // extract reporting filter fields from JSON file
    public function getReportingFilters(): array {
        $filterValues = [];
       
        if($this->_settings['use_default_filters'] == TRUE)
        {
            $filterValues = $this->_defaultFilters;
        }
        if(count($this->_settings['filters']) > 0)
        {
            $filterValues = array_merge($filterValues, $this->_settings['filters']);
        }
        return $filterValues;
    }
    // extract default reporting fields from JSON file
    public function getReportingDefaultFields(): array {
        $defaultFields = [];
        foreach($this->_settings['fields'] as $fieldKey => $value)
            {
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
        return $this->_select;
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

    // to access pagination variable to alter select clause
    public function isPagination(bool $addPage) {
        $this->_isPagination = $addPage;
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


    /* 
    *
    */
    public function filteringReportOptions(&$var) {
        //set form values for mapping for columns
       $this->setFieldsMapping($var);
        // Fields
        $this->filteringReportFields($var);
        //custom fields
        $this->filteringReportAddCustomField('ch_fund',$var); //CH Fund 
        // Grouping
        $this->filteringReportGroupOptions($var);

        // Filters
        $this->filteringReportFilterOptions($var);
        //custom filter field
        $this->filteringReportAddCustomFilter('contribution_source',$var); //Contribution Source
        $this->filteringReportAddCustomFilter('payment_instrument_id',$var); //Payment Method
        $this->filteringReportAddCustomFilter('campaign_type',$var); //Campaign Type
        $this->filteringReportAddCustomFilter('ch_fund',$var); //CH Fund
    }

    private function filteringReportFields(&$var) {
        foreach ($var as $entityName => $entityData) {
            foreach ($entityData['fields'] as $fieldName => $fieldData) {
                    
                // We do not want to show this field
                if (!in_array($fieldName, $this->getReportingFields())) {
                    unset($var[$entityName]['fields'][$fieldName]);
            
                // We want this
                } else {   
                    //set default field
                    $this->setDefaultColumn($fieldName, $var[$entityName]['fields'][$fieldName]);
                    // Fix empty / different titles
                    $this->fixFieldTitle($fieldName, $var[$entityName]['fields'][$fieldName]['title']);
                    $this->fixFieldStatistics($fieldName, $var[$entityName]['fields'][$fieldName]);

                    // Assigning order bys options based on fields
                    // Adding missing title to order by options
                    if(!in_array($fieldName,["total_amount","currency","id"])){
                        $var[$entityName]['order_bys'][$fieldName] = [
                            'title' => $var[$entityName]['fields'][$fieldName]['title']
                        ];
                    }
                }
            }
        }
    }
    //Add Custom fields to fields and group by and order by section
    private function filteringReportAddCustomField($fieldName,&$var) {
        if(in_array($fieldName,$this->getReportingFields())){
            switch ($fieldName) {
                case 'ch_fund':
                    $fieldDetails = [];
                    $customTablename = EU::getTableNameByName('Additional_info');
                    $trial = EU::getCustomFieldID('Fund');
                    $fieldDetails = $this->_mapping[$customTablename]['fields'][$trial];
                    $fieldDetails ['table_name'] = $customTablename;
                    foreach( ['fields','group_bys','order_bys'] as $entityName) {
                        switch ($entityName) {
                            case 'fields':
                            case 'group_bys':
                                $var[$this->getEntityTable()][$entityName][$fieldName] = $fieldDetails;
                                $this->setDefaultColumn($fieldName, $var[$this->getEntityTable()][$entityName][$fieldName]);
                                break;
                            case 'order_bys':
                                $var[$this->getEntityTable()][$entityName][$fieldName] = [
                                    'title' => $fieldDetails['title']
                                ];
                                break;
                        }

                    }
                    break;
            }
        }
    }

    private function filteringReportGroupOptions(&$var) {
        foreach ($var as $entityName => $entityData) {
            foreach ($entityData['group_bys'] as $fieldName => $fieldData) {
                // We do not want to show this group_bys
                if (!in_array($fieldName, $this->getReportingFields())) {
                    unset($var[$entityName]['group_bys'][$fieldName]);
                }else{
                    $this->setDefaultColumn($fieldName, $var[$entityName]['group_bys'][$fieldName]);
                } 
            }
        }
    }

    private function filteringReportFilterOptions(&$var) {
        foreach ($var as $entityName => $entityData) {
            foreach ($entityData['filters'] as $fieldName => $fieldData) {
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
            }
        }
    }
    // manage display of resulting rows
    //TODO : try to replace campaign_id with the name it self in the query rather than performing additional alter display
    public function alterDisplayRows(&$rows) {

        $contributionPages = CRM_Contribute_PseudoConstant::contributionPage(NULL, TRUE);
        $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
        $getCampaigns = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns(NULL, NULL, FALSE, FALSE, TRUE);
        foreach ($rows as $rowNum => $row) {
    
          if ($value = CRM_Utils_Array::value('contribution_page_id', $row)) {
            $rows[$rowNum]['contribution_page_id'] = $contributionPages[$value];
            
          }
          // If using campaigns, convert campaign_id to campaign title
          if (array_key_exists('campaign_id', $row)) {
            if ($value = $row['campaign_id']) {
              $rows[$rowNum]['campaign_id'] = $getCampaigns['campaigns'];
            }
          }
    
        }
      }
      //manage statistics query 
      public function alterStatistics(array $rows):array {
        $statistics = [];

        $groupByCurrency = false;

        foreach ($rows as $rowNum => $row) {
            if(count(explode(',', $row['currency'])) > 1)
            {
                $groupByCurrency = true;
            }
        }
        //if result has multiple currencies then add group by currency clause to statistics query
        if($groupByCurrency)
        {
            if($this->_groupBy)
            {
                $this->_groupBy .= ', '.$this->getEntityTable().'.currency';
            }else{
                $this->_groupBy .= ' GROUP BY ' .$this->getEntityTable().'.currency';
            }
        }

    $contriQuery = 'COUNT(DISTINCT '.$this->getEntityTable().'.id ) as count,
        SUM('.$this->getEntityTable().'.total_amount) as total_amount,
        '.$this->getEntityTable().'.currency as currency '.$this->_from.' '.$this->_where;

    $contriSQL = "SELECT {$contriQuery} {$this->_groupBy}";

    $contriDAO = CRM_Core_DAO::executeQuery($contriSQL);
    $currencies = $currAmount = $currCount = $totalAmount = [];

        while ($contriDAO->fetch()) {
        
            if (!isset($currAmount[$contriDAO->currency])) {
                $currAmount[$contriDAO->currency] = 0;
            }
            if (!isset($currCount[$contriDAO->currency])) {
                $currCount[$contriDAO->currency] = 0;
            }
            //defining currency amount and count based upon currency
            $currAmount[$contriDAO->currency] += $contriDAO->total_amount;
            $currCount[$contriDAO->currency] += $contriDAO->count;
        
            $count += $contriDAO->count;

            if (!in_array($contriDAO->currency, $currencies)) {
                $currencies[] = $contriDAO->currency;
            } 
        }

        foreach ($currencies as $currency) {
            if (empty($currency)) {
            continue;
            }
            $totalAmount[] = CRM_Utils_Money::format($currAmount[$currency], $currency) .
            " (" . $currCount[$currency] . ") (".$currency.")";
        }
        // total amount
        $statistics['counts']['amount'] = [
            'title' => ts('Total Amount'),
            'value' => implode(',  ', $totalAmount),
            'type' => CRM_Utils_Type::T_STRING,
        ];

        // total contribution count
        $statistics['counts']['count'] = [
            'title' => ts('Total Contributions'),
            'value' => $count,
        ];

        return $statistics;
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
        
        }
    }
    //set default fields and group by checkbox checked according to default field defined
    private function setDefaultColumn(string $fieldName, &$field) {
        if($this->_settings['fields'][$fieldName]['default']){
            $field['default'] = [TRUE];
        }
        else{
            unset($field['default']);
        }
    }
    public function setPreSelectField(array $elementObj) {
            foreach( $elementObj as $elementIndex => $elements) {
                
                if ( isset( $elements->_attributes ) && in_array($elements->_attributes['name'],$this->getReportingDefaultFields())) {
                    $elementObj[$elementIndex]->_flagFrozen = 1;
                }
            }
        return $elementObj;
    }
    //set default option value to Sort by section
    public function setDefaultOptionSortBy(array $defaults) {
        if(!empty($this->getReportingDefaultFields()))
        {
            unset($defaults['order_bys']);
            foreach($this->getReportingDefaultFields() as $value)
            {
                $defaults['order_bys'][] = ['column'=>$value,'order'=>'ASC'];
            }
        }
        return $defaults;
    }

    private function fixFieldStatistics(string $fieldName, &$statistics) {
        switch ($fieldName) {
            case 'total_amount':
                $statistics['statistics'] = ['count' => ts('Number of Contributions'), 'sum' => ts('Total Amount')];
                break;
        }
    }

    // todo: this should be switched to public once all reporting refactoring is done
    static function fixFilterOption(string $fieldName, &$filterData) {
        switch ($fieldName) {
            case 'contribution_page_id':
                $filterData = CRM_Contribute_PseudoConstant::contributionPage(NULL, TRUE);
                break;
        
        }
    }

    /* 
    *
    */
    static function getReportInstanceDetails( $id ) {
        $result = civicrm_api3('ReportInstance', 'get', [
            'sequential' => 1,
            'return' => ["name", "title"],
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
}

?>