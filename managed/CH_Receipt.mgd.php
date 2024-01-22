<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Receipts',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contrib_detailed',
      'title' => ts('Receipts'),
      'name' => 'contrib_detailed_receipts',
      "description" => "Overview of contributions with Receipt Number",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => NULL,
      'is_reserved' =>  0,
    ),
  ),
);
