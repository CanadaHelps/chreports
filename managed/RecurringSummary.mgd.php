<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Recurring Contributions (Summary)',
    'update' => 'always',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'biz.jmaconsulting.chreports/recursummary',
      'title' => ts('Recurring Contributions (Summary)'),
      "description" => "Overview of Recurring Contributions",
      'name' => 'Recurring Contributions (Summary)',
      'permission' => 'access CiviReport',
      'is_active' => 1,
      'form_values' => 'a:29:{s:8:"entryURL";s:97:"https://test2-dms.canadahelps.org/civicrm/report/biz.jmaconsulting.chreports/recursummary?reset=1";s:6:"fields";a:16:{s:2:"id";i:1;s:9:"sort_name";i:1;s:12:"total_amount";i:1;s:6:"source";i:1;s:23:"completed_contributions";i:1;s:10:"start_date";i:1;s:17:"last_month_amount";i:1;s:10:"first_name";i:1;s:9:"last_name";i:1;s:14:"street_address";i:1;s:4:"city";i:1;s:11:"postal_code";i:1;s:17:"state_province_id";i:1;s:10:"country_id";i:1;s:5:"phone";i:1;s:5:"email";i:1;}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:21:"receive_date_relative";s:10:"this.month";s:17:"receive_date_from";s:0:"";s:22:"receive_date_from_time";s:0:"";s:15:"receive_date_to";s:0:"";s:20:"receive_date_to_time";s:0:"";s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:11:"description";s:33:"Recurring Contributions (Summary)";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:9:"row_count";s:0:"";s:9:"view_mode";s:4:"view";s:13:"cache_minutes";s:2:"60";s:10:"permission";s:17:"access CiviReport";s:9:"parent_id";s:0:"";s:8:"radio_ts";s:0:"";s:6:"groups";s:0:"";s:11:"instance_id";s:0:"";}',
    ),
  ),
);
