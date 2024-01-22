<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'In Memory of',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/opportunity_detailed',
      'title' => ts('In Memory of'),
      'name' => 'contrib_detailed_inmemory',
      "description" => "All contributions made In Memory Of",
      'permission' => 'access Reports',
      'is_active' => 1,
      "form_values" => NULL,
      'is_reserved' =>  0,
    ),
  ),
);
