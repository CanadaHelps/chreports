<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Contribution History by GL Account (Detailed)',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'biz.jmaconsulting.chreports/glaccountdetail',
      'title' => ts('Donation History by GL Account (Detailed)'),
      'name' => 'Donation History by GL Account (Detailed)',
      "description" => "In depth view of contributions by GL Account",
      'permission' => 'administer Reports',
      'is_active' => 1,
      "form_values" => "a:23:{s:8:\"entryURL\";s:100:\"https://test2-dms.canadahelps.org/civicrm/report/biz.jmaconsulting.chreports/glaccountdetail?reset=1\";s:6:\"fields\";a:9:{s:2:\"id\";s:1:\"1\";s:9:\"sort_name\";s:1:\"1\";s:6:\"source\";s:1:\"1\";s:12:\"receive_date\";s:1:\"1\";s:9:\"gl_amount\";s:1:\"1\";s:21:\"payment_instrument_id\";s:1:\"1\";s:12:\"check_number\";s:1:\"1\";s:7:\"trxn_id\";s:1:\"1\";s:12:\"card_type_id\";s:1:\"1\";}s:21:\"receive_date_relative\";s:0:\"\";s:17:\"receive_date_from\";s:0:\"\";s:22:\"receive_date_from_time\";s:0:\"\";s:15:\"receive_date_to\";s:0:\"\";s:20:\"receive_date_to_time\";s:0:\"\";s:25:\"contribution_status_id_op\";s:2:\"in\";s:28:\"contribution_status_id_value\";a:1:{i:0;s:1:\"1\";}s:11:\"description\";s:44:\"In depth view of contributions by GL Account\";s:13:\"email_subject\";s:0:\"\";s:8:\"email_to\";s:0:\"\";s:8:\"email_cc\";s:0:\"\";s:9:\"row_count\";s:0:\"\";s:13:\"is_navigation\";s:1:\"1\";s:9:\"view_mode\";s:4:\"view\";s:13:\"cache_minutes\";s:2:\"60\";s:10:\"permission\";s:17:\"access CiviReport\";s:9:\"parent_id\";s:3:\"249\";s:8:\"radio_ts\";s:0:\"\";s:6:\"groups\";s:0:\"\";s:11:\"instance_id\";s:2:\"30\";s:10:\"navigation\";a:2:{s:2:\"id\";s:3:\"276\";s:9:\"parent_id\";s:3:\"249\";}}",
      'is_reserved' =>  0,
    ),
  ),
);
