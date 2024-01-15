<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Retention Rate Report (Dashlet)',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contrib_retention',
      'title' => ts('Retention Rate Report (Dashlet)'),
      'name' => 'contrib_retention_dashlet',
      "description" => "Retention Rate Report",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => NULL,
      'is_reserved' =>  0,
    ),
  ),
);
