<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Contribution History by Source (Summary)',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contrib_summary',
      'title' => ts('Contribution History by Source (Summary)'),
      'name' => 'contrib_summary_source',
      "description" => "Overview of contributions by Source",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => NULL,
      'is_reserved' =>  0,
    ),
  ),
);
