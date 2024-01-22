<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'SYBNT',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      "report_id" => "chreports/contrib_sybunt",
      'name' => 'contrib_sybunt',
      'title' => ts('SYBNT'),
      "description" => "Contributors who gave 'Some Year, But Not This'",
      'permission' => 'access Reports',
      'is_active' => 1,
      'form_values' => NULL,
      'is_reserved' =>  0,
    ),
  ),
);
