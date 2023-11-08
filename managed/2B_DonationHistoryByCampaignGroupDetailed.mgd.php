<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Contribution History by Campaign Group (Detailed)',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contrib_detailed',
      'name' => 'contrib_detailed_campaign_group',
      'title' => ts('Contribution History by Campaign (Detailed)'),
      "description" => "In depth view of contributions by Campaign Group",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => NULL,
      'is_reserved' =>  0,
    ),
  ),
);
