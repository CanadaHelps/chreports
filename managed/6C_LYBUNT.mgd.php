<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'LYBNT',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/lybunt',
      'title' => ts('LYBNT'),
      'name' => 'contrib_lybunt',
      "description" => "Contributors who gave 'Last Year, But Not This'",
      'permission' => 'administer Reports',
      'is_active' => 1,
      'form_values' => NULL,
      'is_reserved' =>  0,
    ),
  ),
);
