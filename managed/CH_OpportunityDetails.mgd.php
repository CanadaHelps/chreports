<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Opportunity Report',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/opportunity_detailed',
      'title' => ts('Opportunity Report'),
      'name' => 'opportunity_detailed',
      "description" => "This report is meant to list all active opportunities",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => NULL,
      'is_reserved' =>  0,
    ),
  ),
);
