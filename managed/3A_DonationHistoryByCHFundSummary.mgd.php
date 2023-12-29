<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Contribution History by CH Fund (Summary)',
    'update' => 'always',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contrib_summary',
      'name' => 'contrib_summary_chfund',
      'title' => ts('Contribution History by CH Fund (Summary)'),
      "description" => "Total amounts raised by CanadaHelps Fund",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => NULL,
    ),
  ),
);