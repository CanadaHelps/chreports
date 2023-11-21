<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Contribution History by Campaign (Yearly)',
    'update' => 'always',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contrib_summary_yearly',
      'title' => ts('Contribution History by Contribution Page (Yearly)'),
      'name' => 'contrib_yearly_campaign',
      "description" => "Total amounts raised by Campaign year over year",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => NULL,
    ),
  ),
);