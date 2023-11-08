<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Contribution History by Campaign (Summary)',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contrib_summary',
      'title' => ts('Contribution History by Contribution Page (Summary)'),
      'name' => 'contrib_summary_campaign',
      "description" => "Overview of Campaign contributions",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      'form_values' => NULL,
      'is_reserved' =>  0,
    ),
  ),
);
