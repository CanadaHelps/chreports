<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Contribution History by Campaign (Monthly)',
    'update' => 'always',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contrib_summary_monthly',
      'title' => ts('Contribution History by Contribution Page (Monthly)'),
      'name' => 'contrib_monthly_campaign',
      "description" => "Total amounts raised by Campaign month over month",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => NULL,
    ),
  ),
);