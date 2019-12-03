<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 =>
  array (
    'name' => 'CRM_Chreports_Form_Report_ExtendSummary',
    'entity' => 'ReportTemplate',
    'module' => 'biz.jmaconsulting.chreports',
    'update' => 'never',
    'params' =>
    array (
      'version' => 3,
      'label' => 'Extend Contribution Summary',
      'description' => 'ExtendSummary (biz.jmaconsulting.chreports)',
      'class_name' => 'CRM_Chreports_Form_Report_ExtendSummary',
      'report_url' => 'biz.jmaconsulting.chreports/extendsummary',
      'component' => '',
    ),
  ),
);
