<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Contribution History by Payment Method (Summary)',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contrib_summary',
      'title' => ts('Contribution History by Payment Method (Summary)'),
      'name' => 'contrib_summary_payment_method',
      "description" => "Overview of contributions by Payment Method",
      'permission' => 'administer Reports',
      'is_active' => 1,
      "form_values" => NULL,
     'is_reserved' =>  0,
     'class_name' => 'CRM_Chreports_Form_Report_ExtendSummary',
    ),
  ),
);
