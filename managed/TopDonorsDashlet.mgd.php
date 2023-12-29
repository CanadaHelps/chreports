<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Top Donors (Dashlet)',
    'update' => 'always',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contact_top_donors',
      'title' => ts('Top Donors (Dashlet)'),
      "description" => "Top Donors for Dashlet",
      'name' => 'contact_top_donors_dashlet',
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => NULL,
    ),
  ),
);
