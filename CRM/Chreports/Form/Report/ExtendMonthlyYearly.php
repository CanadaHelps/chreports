<?php
use CRM_Chreports_ExtensionUtil as E;

class CRM_Chreports_Form_Report_ExtendMonthlyYearly extends CRM_Chreports_Form_Report_ExtendSummary {
   
  private $_reportInstance;

  public function __construct() {
    parent::__construct();
  }

  public function getReportInstance(): CRM_Chreports_Reports_MonthlyYearlyReport {
    
    
    // Instantiate Report Instance if doesn't exists yet
    if ($this->_reportInstance == NULL) {
      $reportPath = $this->_attributes['action'];
      $reportId = end(explode('/', $reportPath));
      $reportInfo = CRM_Chreports_Reports_BaseReport::getReportInstanceDetails($reportId);
      $reportName = $reportInfo['name'] ?? $reportInfo['title'];
      $this->_reportInstance = new CRM_Chreports_Reports_MonthlyYearlyReport('contribution', $reportId, $reportName);
    }
    
    return $this->_reportInstance;
  }


}