<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Latest Contributions',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contrib_detailed',
      'title' => ts('Latest Contributions (Dashlet)'),
      'name' => 'contrib_latest_dashlet',
      "description" => "Most recent contributions",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => NULL,
      'is_reserved' =>  0,
    ),
  ),
);
