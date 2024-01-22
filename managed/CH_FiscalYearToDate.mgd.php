<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Fiscal Year to Date',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contrib_period_detailed',
      'title' => ts('Fiscal Year to Date (Monthly)'),
      'name' => 'contrib_monthly_fiscal_year',
      "description" => "Total amounts raised this fiscal year by month",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      'is_reserved' => 1,
      "form_values" => NULL,
    ),
  ),
);