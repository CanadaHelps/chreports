<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
return array (
  0 => 
  array (
    'name' => 'CRM_Chreports_Form_Report_RecurSummary',
    'entity' => 'ReportTemplate',
    'module' => 'biz.jmaconsulting.chreports',
    'params' => 
    array (
      'version' => 3,
      'label' => 'RecurSummary',
      'description' => 'Total amounts raised by Recurring Contributions with individual Contribution information',
      'class_name' => 'CRM_Chreports_Form_Report_RecurSummary',
      'report_url' => 'biz.jmaconsulting.chreports/recursummary',
      'component' => 'CiviContribute',
    ),
  ),
);
