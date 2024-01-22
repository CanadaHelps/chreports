<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Contribution History by GL Account (Detailed)',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      "report_id" => "chreports/contrib_detailed",
      'title' => ts('Contribution History by GL Account (Detailed)'),
      'name' => 'contrib_detailed_glaccount',
      "description" => "In depth view of contributions by GL Account",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => NULL,
    ),
  ),
);
