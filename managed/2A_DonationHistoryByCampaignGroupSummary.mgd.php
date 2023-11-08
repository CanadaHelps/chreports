<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Contribution History by Campaign Group (Summary)',
    'update' => 'always',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contrib_summary',
      'name' => 'contrib_summary_campaign_group',
      'title' => ts('Contribution History by Campaign (Summary)'),
      "description" => "Total amounts raised by Campaign Group",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" =>NULL,
      'is_reserved' =>  0,
    ),
  ),
);
