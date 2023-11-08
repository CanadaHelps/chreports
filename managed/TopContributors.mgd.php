<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Top contributors',
    'update' => 'always',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/top_donors',
      'name' => 'contact_top_donors',
      'title' => ts('Top Donors'),
      "description" => "Top contributors",
      'permission' => 'administer Reports',
      'is_active' => 1,
      "form_values" => NULL,
    ),
  ),
);
