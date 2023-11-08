<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Last Year inc Today',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contrib_period_detailed',
      'title' => ts('Last Year inc. Today'),
      'name' => 'contrib_quarterly_past_year',
      "description" => "Total amounts raised last calendar year by quarters",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      'is_reserved' => 1,
      "form_values" => NULL,
    ),
  ),
);