<?php
use CRM_Canadahelps_ExtensionUtils as EU;
use CRM_Chreports_ExtensionUtil as E;

class CRM_Chreports_Reports_ReportConfiguration {
    private $_id;

    protected $_settings = [];

    // Default Action is to View Report
    protected $_action = "view";

    public function __construct(int $id) {
        $this->_id = $id;
        $this->_action = $action;    
        $this->loadSettings();
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
        if($this->_action == 'copy') {
            $config['is_copy'] = (int) 1;
        }
        $this->_settings = $config;
    }

    /**
     *
     * Write the newly or edited JSON for the custom report
     * Save the entry to DB & Redirect to the newly created report
     * @param array $config contains the pre-compiled config array that has to be saved as it is
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

        // Get the base and source file name
        $filePath = self::getFilePath((array) $instance, $this->_action);

        // Ensure the directory exists, create it if necessary
        if (!is_dir($filePath['base'])) {
            return;
        }

        // Encode the $config array as JSON
        $config['name'] = $instance->name;
        $jsonConfig = json_encode($config, JSON_PRETTY_PRINT);

        // Write the JSON data to the file
        if (file_put_contents($filePath['source'], $jsonConfig) !== false) {
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
        $report_id = self::escapeFileName($reportDetails['report_id']);
        if($action == 'copy' || $reportDetails['created_id']) {
            $filePath['base'] = CRM_Core_Config::singleton()->uploadDir;
            $filePath['source'] = $filePath['base']. 'reports/saved/'. $reportDetails['created_id']. '_' . $report_id . '_' . $reportDetails['id']. '.json';
        } else {
            $filePath['base'] = dirname(__DIR__, 1)  . "/Templates/";
            $filePath['source'] = $filePath['base']. $report_id.'.json';

            // hack for now
            // Else remove it from here after completion of CRM-2111
            if (!is_file($filePath['source'])) {
                $filePath['source'] = $filePath['base']. self::escapeFileName($reportDetails['name']).'.json';
            }
        }
        return $filePath;
    }
}
?>
