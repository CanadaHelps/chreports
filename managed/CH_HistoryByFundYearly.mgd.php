<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Contribution History by Fund (Yearly)',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contrib_summary_yearly',
      'title' => ts('Contribution History by Fund (Yearly)'),
      'name' => 'contrib_yearly_fund',
      "description" => "Total amounts raised by Fund year over year",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => NULL,
    ),
  ),
);