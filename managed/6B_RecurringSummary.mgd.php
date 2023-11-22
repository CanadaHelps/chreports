<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Recurring Contributions (Summary)',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contrib_recurring',
      'title' => ts('Recurring Contributions'),
      "description" => "Total amounts raised by Recurring Contributions with individual Contribution information",
      'name' => 'contrib_recurring',
      'permission' => 'access CiviReport',
      'is_active' => 1,
      'form_values' => NULL,
    ),
  ),
);
