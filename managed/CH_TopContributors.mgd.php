<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Top contributors',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contact_top_donors',
      'name' => 'contact_top_donors',
      'title' => ts('Top Contributors'),
      "description" => "Top contributors",
      'permission' => 'access Reports',
      'is_active' => 1,
      "form_values" => NULL,
    ),
  ),
);
