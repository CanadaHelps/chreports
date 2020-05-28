<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
return array (
  0 => 
  array (
    'name' => 'CRM_Chreports_Form_Report_ExtendedDetail',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'Extended Contribution Detail',
      'description' => 'ExtendedDetail (biz.jmaconsulting.chreports)',
      'class_name' => 'CRM_Chreports_Form_Report_ExtendedDetail',
      'report_url' => 'biz.jmaconsulting.chreports/extendeddetail',
      'component' => 'CiviContribute',
    ),
  ),
);
