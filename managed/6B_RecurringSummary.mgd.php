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
      "description" => "Total amounts raised by Recurring Contributions with individual Contribution information",
      'name' => 'Recurring Contributions (Summary)',
      'permission' => 'access CiviReport',
      'is_active' => 1,
      'form_values' => "a:29:{s:6:\"fields\";a:15:{s:2:\"id\";s:1:\"1\";s:10:\"exposed_id\";s:1:\"1\";s:9:\"sort_name\";s:1:\"1\";s:14:\"street_address\";s:1:\"1\";s:4:\"city\";s:1:\"1\";s:11:\"postal_code\";s:1:\"1\";s:17:\"state_province_id\";s:1:\"1\";s:10:\"country_id\";s:1:\"1\";s:5:\"phone\";s:1:\"1\";s:5:\"email\";s:1:\"1\";s:12:\"total_amount\";s:1:\"1\";s:6:\"source\";s:1:\"1\";s:23:\"completed_contributions\";s:1:\"1\";s:10:\"start_date\";s:1:\"1\";s:17:\"last_month_amount\";s:1:\"1\";}s:12:\"sort_name_op\";s:3:\"has\";s:15:\"sort_name_value\";s:0:\"\";s:6:\"id_min\";s:0:\"\";s:6:\"id_max\";s:0:\"\";s:5:\"id_op\";s:3:\"lte\";s:8:\"id_value\";s:0:\"\";s:21:\"receive_date_relative\";s:4:\"nnll\";s:17:\"receive_date_from\";s:0:\"\";s:15:\"receive_date_to\";s:0:\"\";s:14:\"campaign_id_op\";s:2:\"in\";s:17:\"campaign_id_value\";a:0:{}s:8:\"tagid_op\";s:2:\"in\";s:11:\"tagid_value\";a:0:{}s:6:\"gid_op\";s:2:\"in\";s:9:\"gid_value\";a:0:{}s:9:\"order_bys\";a:1:{i:1;a:1:{s:6:\"column\";s:1:\"-\";}}s:11:\"description\";s:35:\"Overview of Recurring Contributions\";s:13:\"email_subject\";s:0:\"\";s:8:\"email_to\";s:0:\"\";s:8:\"email_cc\";s:0:\"\";s:9:\"row_count\";s:0:\"\";s:9:\"view_mode\";s:4:\"view\";s:13:\"cache_minutes\";s:2:\"60\";s:10:\"permission\";s:17:\"access CiviReport\";s:9:\"parent_id\";s:0:\"\";s:8:\"radio_ts\";s:0:\"\";s:6:\"groups\";s:0:\"\";s:11:\"instance_id\";N;}",
    ),
  ),
);
