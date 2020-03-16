<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 =>
  array (
    'name' => 'CRM_Chreports_Form_Report_GLAccountDetail',
    'entity' => 'ReportTemplate',
    'module' => 'biz.jmaconsulting.chreports',
    'params' =>
    array (
      'version' => 3,
      'label' => 'GLAccountDetail',
      'description' => 'GLAccountDetail (biz.jmaconsulting.chreports)',
      'class_name' => 'CRM_Chreports_Form_Report_GLAccountDetail',
      'report_url' => 'biz.jmaconsulting.chreports/glaccountdetail',
      'component' => 'CiviContribute',
    ),
  ),
);
