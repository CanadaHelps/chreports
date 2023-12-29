<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'In Honour of',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/opportunity_detailed',
      'title' => ts('In Honour of'),
      'name' => 'contrib_detailed_inhonour',
      "description" => "All contributions made In Honour Of",
      'permission' => 'administer Reports',
      'is_active' => 1,
      "form_values" => NULL,
      'is_reserved' =>  0,
    ),
  ),
);
