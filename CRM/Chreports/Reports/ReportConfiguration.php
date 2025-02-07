<?php
use CRM_Canadahelps_ExtensionUtils as EU;
use CRM_Chreports_ExtensionUtil as E;

class CRM_Chreports_Reports_ReportConfiguration {
    private $_id;

    protected $_settings = [];
    protected $_mappings = [];

    protected $_extraFieldNames = [
        'civicrm_contact_display_name' => 'sort_name',
        'civicrm_contact_contact_id' => 'contact_id',
        'display_name' => 'contact_name',
        'email_email' => 'email',
        'exposed_id' => 'contact_id',
        'contribution_payment_instrument_id' => 'payment_instrument_id',
        'contribution_total_amount' => 'total_amount',
        'contribution_receive_date' => 'receive_date',
        'contribution_contribution_status_id' => 'contribution_status_id',
    ];

    // Default Action is to View Report
    protected $_action = "view";

    public function __construct($id = NULL, string $name) {
        if($id)
            $this->_id = $id;
        $this->loadMappings();
        // For base templates where JSON file is present but no report_instance in the DB
        if(in_array($name, E::getOnlyBaseTemplates())) {
            $this->_settings = $this->_fetchConfigSettings(['name' => $name, 'id' => $id]);
        } else {
            $this->loadSettings();
        }
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
     * mapping.json file contains all the fields we are using as columns or filters
     * mapping.json contains details about entity, title, custom field or not, option values etc
     * this function loads mapping.json file and save values in _mappings 
     * @return void
     */
    private function loadMappings(): void {
        $filePath = dirname(__DIR__, 1)  . "/Templates/mapping.json";
        if (is_file($filePath)) {
            $this->_mappings = json_decode(file_get_contents($filePath),true);
            return;
        } 
    }



    /**
     * 
     * Loads the report configuration from a JSON file
     * 
     * Template File: <report_id>.json
     * Saved report: UPLOAD_DIR/chreports/saved/<user_id>_<report_id>_<id>.json
     * 
     * @return void
     */
    private function loadSettings(): void {
        //get the values from json file based on the name of the report
        $reportInstanceDetails = $this->getReportInstanceDetails($this->_id);
        $this->_settings = $this->_fetchConfigSettings($reportInstanceDetails);
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
            'return' => ["name", "title", "created_id", "report_id", "description", "form_values"],
            'id' => $id,
            ]);
        
        if ($result['count'] == 0) {
            watchdog("reporting", "Could not load report #" . $id, [], WATCHDOG_ERROR);
            return [];
        }

        return $result['values'][0];

        // todo: use API4 after upgrade
        // $reportInstance = \Civi\Api4\ReportInstance::get(TRUE)
        // ->addSelect('name', 'title')
        // ->addWhere('id', '=', $reportId)
        // ->execute()
        // ->itemAt(1);
    }

    public function getAllColumns( ): array {
        return $this->_settings['fields'];
    }

    /**
     * 
     * Return field information from mapping.json file
     *
     * @param TBD
     * @return array
     */
    public function getFieldInfo( $fieldName ): array {
        if ( isset($this->_mappings[$fieldName]) ){
            return $this->_mappings[$fieldName];
        }
            
        return ["error" => "not found"];
        
    }

    //get operator type values for filters 
    public function getOperatorType($fieldName) : string {
        $fieldInfo = $this->getFieldInfo($fieldName);
        $type = isset($fieldInfo["field_type"]) ? ucwords($fieldInfo["field_type"]) : false;
        $operator = $fieldInfo['options'] ?? false;
        $operatorType = '';
        switch (true) {
            case ($type === "Boolean" || $fieldName==='yid' || $fieldName==='base_year'):
                $operatorType = CRM_Report_Form::OP_SELECT;
                break;
            case ($type === "Datetime"):
                $operatorType = CRM_Report_Form::OP_DATE;
                break;
            case ($type === "Money"):
                $operatorType = CRM_Report_Form::OP_INT;
                break;
            case ($operator === true):
                $operatorType = CRM_Report_Form::OP_MULTISELECT;
                break;
            case ($type === "Int"):
                $operatorType = CRM_Report_Form::OP_INT;
                break;
        }
        return $operatorType;
    }
    //get filter type values for fields and filters 
    public function getFilterType($fieldName) : array {
        $fieldInfo = $this->getFieldInfo($fieldName);
        $type = ucwords( ($fieldInfo["field_type"]) ?? "string" );
        $fieldType = [
            "dataType" => $type
        ];

        switch ($type) {
            case "String":
                $fieldType["type"] = CRM_Utils_Type::T_STRING;
                $fieldType["htmlType"] = "Text";
                break;
            case "Int":
                $fieldType["type"] = CRM_Utils_Type::T_INT;
                $fieldType["htmlType"] = "Text";
                break;
            case "Boolean":
                $fieldType["type"] = CRM_Utils_Type::T_BOOLEAN;
                $fieldType["htmlType"] = "Radio";
                break;
            case "Money":
                $fieldType["type"] = CRM_Utils_Type::T_MONEY;
                $fieldType["htmlType"] = "Text";
                break;
            case "Datetime":
                $fieldType["type"] = CRM_Utils_TYPE::T_DATE + CRM_Utils_Type::T_TIME;
                $fieldType["htmlType"] = "Text";
                break;
        }
        return $fieldType;
    }
    //generate filter options based upon values defined in mapping.json file
    public function getFilterOptions($fieldName) : array {

        $options = [];
        $fieldInfo = $this->getFieldInfo($fieldName);

        $fieldNameVal = (isset($fieldInfo['field_name']))? $fieldInfo['field_name']: $fieldName;
        $fieldTable = $this->getEntityTableFromField($fieldName,true);
       
        // option values being created for custom fields such as ch_fund
        if(isset($fieldInfo['custom']) && $fieldInfo['custom'] === true && ($fieldInfo['field_type'] !== 'boolean')){
            
            $columnName = E::getColumnNameByName($fieldInfo['custom_fieldName']);
            $optionGroupName = E::getOptionGroupNameByColumnName($columnName);
            $customTablename = EU::getTableNameByName($fieldInfo['group_name']);
            $options =   $this->getOptionsListForOptionGroup($fieldName,$customTablename,$optionGroupName);
            
        } else if (isset($fieldInfo['use_option_value']) && $fieldInfo['use_option_value'] === true) { 
           // option values being created from option values using option group 
            $groupName = $fieldInfo['group_name'];
            $options =   $this->getOptionsListForOptionGroup($fieldNameVal,$fieldTable,$groupName);

        // option values for boolean fields
        } else if (isset($fieldInfo['field_type']) && ($fieldInfo['field_type'] === 'boolean')){ 
            
            $options =   [
              '' => ts('Any'),
              TRUE => ts('Yes'),
              FALSE => ts('No'),
            ];

        // get option values for entity table 
        } else if (isset($fieldInfo['select_option']) ){
            $fieldNameVal = $fieldInfo['select_option'];
            $options = $this->getOptionsListForEntity($fieldNameVal,$fieldTable);
            
        //get option values for fields which are not coming from db
        } else {
            
            switch ($fieldName) {
                case "on_hold":
                    $options = CRM_Core_PseudoConstant::emailOnHoldOptions();
                    break;
                case "base_year":    
                case "yid":
                    $yearsInPast = 10;
                    $yearsInFuture = 1;
                    $date = CRM_Core_SelectValues::date('custom', NULL, $yearsInPast, $yearsInFuture);
                        $count = $date['maxYear'];
                        while ($date['minYear'] <= $count) {
                        $optionYear[$date['minYear']] = $date['minYear'];
                        $date['minYear']++;
                        }
                        $options = $optionYear;
                    break;
            }
        }
        return $options;
    }
    //get option values from the entity defined for that field
    public function getOptionsListForEntity($fieldName,$fieldTable) {
        $optionValue = [];
        $selectClause =  implode(', ', $fieldName);
    
        $optionsListings = CRM_Core_DAO::executeQuery('SELECT DISTINCT '.$selectClause.' FROM '.$fieldTable)->fetchAll();
        switch ($selectClause) {
            case "contact_sub_type":
                $entityTable = $this->getEntityTable('contact_type');
                $optionsListings = $this->getContactSubTypeOptions($entityTable,$selectClause);
                break;
        }
        foreach($optionsListings as $optionsListing) {
          if($optionsListing) {
            if(count($fieldName) > 1){
                $optionValue[$optionsListing[$fieldName[0]]] = $optionsListing[$fieldName[1]];
            }else{
                $optionValue[$optionsListing[$fieldName[0]]] = $optionsListing[$fieldName[0]];
            }
          }
        }
        //$optionValue = array('' => '- select -') + $optionValue;
        return array_filter($optionValue);
      }
      
      //Get contact_sub_type filter dropdown options
      public static function getContactSubTypeOptions($tableName,$fieldName) : array {
        $contactSubTypeList = [];
        $query = "SELECT name as ".$fieldName." FROM ".$tableName." WHERE parent_id IS NOT NULL";
        $contactSubTypeList =  CRM_Core_DAO::executeQuery($query)->fetchAll();
        return $contactSubTypeList;
      }
      //get option values using civicrm_option_group,civicrm_option_value left join
      public static function getOptionsListForOptionGroup($fieldName,$fieldTable,$groupName) {
        $optionValue = [];
    
        $tableName_group = $fieldTable.'_group';
        $tableName_value = $fieldTable.'_value';

        $subselectClause = " 
        LEFT JOIN civicrm_option_group as ".$tableName_group." ON ".$tableName_group.".name = '".$groupName."' 
        LEFT JOIN civicrm_option_value as ".$tableName_value." ON ".$tableName_value.".option_group_id = ".$tableName_group.".id ";
        
        $optionsListings = CRM_Core_DAO::executeQuery('SELECT DISTINCT '.$tableName_value.'.value,'.$tableName_value.'.label FROM '.$fieldTable.' '.$subselectClause)->fetchAll();
      
        foreach($optionsListings as $optionsListing) {
            if(count(array_filter($optionsListing)) > 0) {
            
                $optionValue[$optionsListing['value']] = $optionsListing['label'];
            }
          }
        
        return $optionValue;
      }

    /**
     * Fetch the JSON Config from different paths depending on the Report Instance & Report ID type
     *
     * @param array $reportInstanceDetails Contains name, title, id, report_id and created_id
     * @return void
     */
    private function _fetchConfigSettings($reportInstanceDetails) {
        $filePath = $this->getFilePath($reportInstanceDetails);
        if (is_file($filePath['source'])) {
            return json_decode(file_get_contents($filePath['source']),true);
        }
        // In case of missing JSON file
        // Redirect to the Report List Page
        watchdog('reporting', 'Missing JSON File for Report ID: '. $this->_id, NULL, WATCHDOG_ERROR);
        CRM_Core_Session::setStatus('Missing Configuration file. Unable to load report.', 'Error' ,'error');
        CRM_Utils_System::redirect('/dms/report/list?reset=1');
    }


    /**
     * Set the preset filter from Config Files to Params
     *
     * @return void
     */
    public function setDefaultFilterValues(): void {
        $params = $this->getFormParams();
        if(isset($this->_preselected_filter)) {
            $defaultFilterParams = $this->createCustomFilterParams();
            foreach($defaultFilterParams as $k => $v) {
                if ($v && (!array_key_exists($k, $params) || !$params[$k])) {
                    $params[$k] = $v;
                }
            }
            $this->_params = $params;
        }
    }

    /**
     *
     * Build Json File from all report params and values
     * @param string $action The task action coming from the form
     * @return void
     */
    public function buildJsonConfigSettings(): void {
        $config = [];
        //Set Default Params
        $params = $this->getFormParams();
        $fields = $this->getColumns();
        $filters = $this->getCustomFilterValues();

        //Load Base template settings to get default Values
        $baseTemplateSettings = $this->_settings;
        $config['name'] = $params['name'];
        $config['title'] = $params['title'];

        // Get entity
        $entity = $this->getEntity();
        
        //Set sorting if applicable
        if(isset($params['order_bys'])) {
            foreach($params['order_bys'] as $kOrder => $vOrder) {
                if($vOrder['column'] == '-') {
                    continue;
                }

                // check whether the field is part of mapping.json
                $orderByField = $this->parseFieldNameFormValue([$vOrder['column'] => true], $entity);
                
                // Skip field and log
                if (empty($orderByField)) {
                    $orderByFieldName = $vOrder['column'];
                    $config['log_messages'][] = 'Unable to port OrderBy Field "'. $vOrder['column'] . '", no mapping found';
                    watchdog("reporting", "Failed to map order by field: ".$orderByFieldName . " -> ".$config['title'] . "", [], WATCHDOG_DEBUG);
                    continue;
                }

                // Unset Column, it's not used
                if(isset($vOrder['column']))
                    unset($vOrder['column']);

                $orderByFieldName = array_key_first($orderByField);
                $config['order_bys'][$orderByFieldName] = $vOrder;
                if(isset($vOrder['section'])) {
                    //Rename Section -> Header instead
                    $config['order_bys'][$orderByFieldName]['header'] = $vOrder['section'];
                    unset($config['order_bys'][$orderByFieldName]['section']);
                }
            }
        }

        // First Convert the fields to the parsed field names from mapping.json
        if (!empty($fields)) {
            $fields = $this->parseFieldNameFormValue($fields, $entity);
        }

        // Copy Columns with preSelected and Default Values
        foreach ($baseTemplateSettings['fields'] as $fieldKey => $fieldValue) {
            if(isset($fieldValue['default'])) {
                $config['fields'][$fieldKey] = $fieldValue;
            } elseif (isset($fields[$fieldKey])) {
                $config['fields'][$fieldKey] = ['selected' => true];
            } else {
                $config['fields'][$fieldKey] = [];
            }
        }

        // Copy leftover field Values those are not part of base template but present in formValues ($params['fields'])
        foreach($fields as $fieldKey => $fieldValue) {
            if(!empty($fieldValue) && !isset($config['fields'][$fieldKey]['default'])) {
                $config['fields'][$fieldKey] = ['selected' => true];
            }
        }

        //Get BaseTemplate Filters
        $baseTemplateFilters = $this->getReportingFilters();

        // Convert the filters to the parsed field names from mapping.json
        if(!empty($filters)) {
            $filters = $this->parseFieldNameFormValue($filters, $entity);
        }
        foreach ($baseTemplateFilters as $filterKey => $filterValue) {
            if(isset($filters[$filterKey])) {
                $config['filters'][$filterKey] = $filters[$filterKey];
            } else {
                $config['filters'][$filterKey] = $filterValue;
            }
        }

        // Copy leftover filter Values (if applicable)
        // Note: this will also overwrite the filter value of baseTemplate to the one coming from FormValues
        foreach($filters as $filterKey => $filterValue) {
            if(!empty($filterValue)) {
                $config['filters'][$filterKey] = $filterValue;
            }
        }

        // Copy rest of the leftover settings if applicable
        foreach($baseTemplateSettings as $k => $v) {
            if (!isset($config[$k])) {
                $config[$k] = $baseTemplateSettings[$k];
            }
        }
        if($this->_action == 'copy') {
            $config['is_copy'] = (int) 1;
            unset($config['is_migrated']);
        }
        if($this->_action == 'migrate') {
            $config['is_migrated'] = (int) 1;
        }
        $this->_settings = $config;
    }

    /**
     *
     * Write the newly or edited JSON for the custom report
     * Save the entry to DB & Redirect to the newly created report
     * @param string $redirect Default set to true, redirect to the newly created report
     * @return void
     */
    public function writeJsonConfigFile($redirect = 'true'): void {

        $config = $this->_settings;

        if(empty($config)) {
            CRM_Core_Session::setStatus(ts("Cannot Create Report. No Settings Found."), ts('Report Save Error'), 'error');
            return;
        }
        $params = $this->getFormParams();

        // Save Report Instance to DB without formvalues
        $params['owner_id'] = 'NULL';
        if (!empty($params['add_to_my_reports'])) {
            $params['owner_id'] = CRM_Core_Session::getLoggedInContactID();
        }
        // Make Form Values, header and footer empty, not needed for custom reports
        unset($params['form_values']);
        unset($params['report_header']);
        unset($params['report_footer']);

        //Set report template name to params
        $params['name'] = $config['name'];

        //Set Report ID
        $params['report_id'] = CRM_Report_Utils_Report::getValueFromUrl($this->_id);
        
        //CRM-2219 copy action suggests new report creation
        if ($this->_action == 'copy') {
            $isNewReport = true;
        }
        // If instance id is present and report action is save, updating existing instance
        if ($this->_id && $this->_action == 'save') {
            $params['instance_id'] = $this->_id;
        }
        // Create the report
        $instance = CRM_Report_BAO_ReportInstance::create($params);

        // Get the base and source file name
        $filePath = self::getFilePath((array) $instance, $this->_action);

        // Ensure the directory exists, create it if necessary
        if (!is_dir($filePath['base'])) {
            $pathDir = CRM_Core_Config::singleton()->customFileUploadDir.'reports/saved/';
            mkdir($pathDir, 0775, true);
        }

        // Unset Log message (if present)
        if(isset($config['log_messages'])) {
            // Save it to a variable to use it for future context
            $logMessages = $config['log_messages'];
            unset($config['log_messages']);
        }

        // Encode the $config array as JSON
        $jsonConfig = json_encode($config, JSON_PRETTY_PRINT);

        // Write the JSON data to the file
        if (file_put_contents($filePath['source'], $jsonConfig) !== false) {

            if ($this->_id && !$isNewReport) {
                // updating existing instance
                $statusMsg = ts('"%1" report has been updated.', [1 => $instance->title]);
            }
            elseif ($this->_id && $isNewReport) {
                $statusMsg = ts('Your report has been successfully copied as "%1". You are currently viewing the new copy.', [1 => $instance->title]);
            }
            else {
                $statusMsg = ts('Your report has been successfully created as "%1". You are currently viewing the new report instance.', [1 => $instance->title]);
            }
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

    /**
     *
     * Create a json file for the migrated report
     * Update the entry to DB 
     * @return array
     */
    public function writeMigrateConfigFile(): array {

        $config = $this->_settings;
        if(empty($config)) {
            return ['success' => false, 'error' => 'Config Missing'];
        }

        $params = $this->getFormParams();
        // Make Form Values, header and footer NULL, not needed for custom reports
        // CIVI Hack: Setting them NULL as a string
        $params['form_values'] = 'NULL';
        $params['header'] = 'NULL';
        $params['footer'] = 'NULL';

        // Unset Params Navigation
        unset($params['navigation']);
        unset($params['is_navigation']);

        //Filter Out unnecessary keys from params
        $allowedKeys = ['id', 'title', 'name', 'description', 'report_id', 'created_id', 'form_values', 'header', 'footer'];
        $params = array_intersect_key($params, array_flip($allowedKeys));

        // Update the report
        $instance = CRM_Report_BAO_ReportInstance::create($params);

        // Get the base and source file name
        $filePath = self::getFilePath((array) $instance, $this->_action);

        // Ensure the directory exists, create it if necessary
        if (!is_dir($filePath['base'])) {
            $pathDir = CRM_Core_Config::singleton()->customFileUploadDir.'reports/saved/';
            mkdir($pathDir, 0775, true);
        }

        // Encode the $config array as JSON
        $config['name'] = $instance->name;

        // Unset Log message (if present)
        if(isset($config['log_messages'])) {
            // Save it to a variable to use it for future context
            $logMessages = $config['log_messages'];
            unset($config['log_messages']);
        }

        $jsonConfig = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Write the JSON data to the file
        if (file_put_contents($filePath['source'], $jsonConfig) !== false) {
            // Log success message
            watchdog('report_migration', 'Successfully migrated report '. $instance->id, NULL, WATCHDOG_INFO);
            return ['success' => true, 'log_messages' => $logMessages ?? ''];
        } else {
            // Log Error writing the file
            watchdog('report_migration', 'Error Writing the file', NULL, WATCHDOG_ERROR);
            return ['success' => false, 'error' => 'Error Writing to the file'];
        }
        return ['success' => false, 'error' => 'Unknown error'];
    }


    /**
     *
     * Set Fields, Filters and Sorting from exisitng report's form_values
     * @param array $config contains the pre-compiled config array that has to be saved as it is
     * @param string $reportId contains base template report ID
     * @return void
     */
    public function setParamsForMigration($reportInstance, $reportId, $reportName): void {
        // Add more keys to the params
        $reportInstance['form_values']['id'] = (int) $reportInstance['id'];
        $reportInstance['form_values']['title'] = $reportInstance['title'];
        $reportInstance['form_values']['name'] = $reportName;
        $reportInstance['form_values']['report_id'] = $reportId;
        if(!empty($reportInstance['description']))
            $reportInstance['form_values']['description'] = $reportInstance['description'];
        $reportInstance['form_values']['created_id'] = $reportInstance['created_id'];
        if (!empty($reportInstance['form_values']['owner_id'])) {
            $reportInstance['form_values']['owner_id'] = $reportInstance['owner_id'];
        }
        $this->setAction('migrate');
        $this->setFormParams($reportInstance['form_values']);
        if(!empty($reportInstance['form_values']['fields'])) {
            $this->setColumns($reportInstance['form_values']['fields']);
        }
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
     * Return Filepath and basepath depending on the report Instance details and action
     *
     * @return array
     */
    public static function getFilePath(array $reportDetails, $action = 'save'): array {
        if(!empty($reportDetails['name']))
            $report_id = self::escapeFileName($reportDetails['name']);
        
        if($action == 'copy' || $action == 'migrate' || !empty($reportDetails['created_id'])) {
            $filePath['base'] = CRM_Core_Config::singleton()->customFileUploadDir.'reports/saved/';
            if(isset($report_id))
                $filePath['source'] = $filePath['base']. $reportDetails['created_id']. '_' . $report_id . '_' . $reportDetails['id']. '.json';
            // For migration, possibilty is there that core template report instance is missing
            // as an exception, pull the base Report (redundant call here)
            if(!isset($report_id) || in_array(strtolower($reportDetails['report_id']), E::getMigratedTemplateList())) {
                if($reportDetails['form_values']) {
                    $reportDetails['form_values'] = unserialize(preg_replace_callback ( '!s:(\d+):"(.*?)";!', function($match) {      
                        return ($match[1] == strlen($match[2])) ? $match[0] : 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
                    }, $reportDetails['form_values']));
                }
                $baseReportTemplate = E::getBaseTemplate($reportDetails);
                if($baseReportTemplate) {
                    $report_id = $baseReportTemplate['values'][$reportDetails['id']]['name'];
                }
                $filePath['base'] = dirname(__DIR__, 1)  . "/Templates/";
                $filePath['source'] = $filePath['base']. $report_id.'.json';
            }
        } else {
            $filePath['base'] = dirname(__DIR__, 1)  . "/Templates/";
            $filePath['source'] = $filePath['base']. $report_id.'.json';
            if($reportDetails['id']) {
                $reportInstanceDetails = self::getReportInstanceDetails($reportDetails['id']);
                if(!empty($reportInstanceDetails['name'])) {
                    if($reportInstanceDetails['name'] == $report_id && !empty($reportInstanceDetails['created_id'])) {
                        $filePath['base'] = CRM_Core_Config::singleton()->customFileUploadDir.'reports/saved/';
                        $filePath['source'] = $filePath['base']. $reportInstanceDetails['created_id']. '_' . $report_id . '_' . $reportInstanceDetails['id']. '.json';
                    }
                }
            }
        }
        return $filePath;
    }

    /**
     *
     * Take the field name and if it's a custom field name
     * return the value of corresponding field from mapping.json file
     *
     * @return string
     */

    private function getFieldFromMappingJson($fieldName, $entity = 'contribution'): string {
        if(strpos($fieldName, 'custom_') === 0) {
            $customFieldId = preg_replace("/[^0-9]/", "", $fieldName);
            if(is_numeric($customFieldId)) {
                $cutomFieldValue = CRM_Core_BAO_CustomField::getNameFromID($customFieldId);
                if($cutomFieldValue) {
                    // Not using getFieldMapping() method as it requires entity information to fetch the result
                    if(!empty($this->_mappings)) {
                        $inputField = reset($cutomFieldValue)['field_name'];
                        $matches = array_filter($this->_mappings, function ($value) use ($inputField) {
                            return isset($value['custom_fieldName']) && $value['custom_fieldName'] === $inputField;
                        });
                        return reset(array_keys($matches));
                    }
                }
            }
        } else {
            if(!empty($this->_mappings)) {
                if(in_array($fieldName, array_keys($this->_mappings))) {
                    return $fieldName;
                }
                // if still not found, check for fieldName and entity type pair
                foreach ($this->_mappings as $key => $object) {
                    if (isset($object['field_name']) && 
                        isset($object['entity']) && 
                        $object['field_name'] == $fieldName && 
                        $object['entity'] == $entity
                    ) {
                        return $key;
                    }
                }
            }
        }
        watchdog("reporting", "Failed to map field: ". $fieldName . "", [], WATCHDOG_DEBUG);
        return '';
    }

    /**
     *
     * Take the field names and as an array and parse the correct corresponding name
     * For customField the value of corresponding field from mapping.json file
     *
     * @return array
     */

    private function parseFieldNameFormValue(array $fields, $entity = 'contribution'): array {
        $newFields = [];
        $fieldNameCorrection = $this->_extraFieldNames;
        $fieldNameCorrection['source']      = $entity.'_source';
        $fieldNameCorrection['status_id']   = $entity.'_status_id';
        $fieldNameCorrectionKeys = array_keys($fieldNameCorrection);

        foreach ($fields as $fieldName => $fieldValue) {
            
            // check for field Name corection
            if(in_array($fieldName, $fieldNameCorrectionKeys)) {
                $fieldName = $fieldNameCorrection[$fieldName];
            }

            $newFieldIndex = $this->getFieldFromMappingJson($fieldName, $entity);
            if($newFieldIndex) {
                $newFields[$newFieldIndex] = $fieldValue;
            }
        }
        return $newFields;
    }
}
?>