<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Opportunity Report',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'grant/detail',
      'title' => ts('Opportunity Report'),
      'name' => 'Opportunity Details',
      "description" => "This report is meant to list all active opportunities",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => "a:47:{s:8:\"entryURL\";s:75:\"https://test2-dms.canadahelps.org/dms/report/instance/36?force=1&amp;reset=1\";s:6:\"fields\";a:8:{s:12:\"display_name\";s:1:\"1\";s:13:\"grant_type_id\";s:1:\"1\";s:9:\"status_id\";s:1:\"1\";s:12:\"amount_total\";s:1:\"1\";s:14:\"amount_granted\";s:1:\"1\";s:25:\"application_received_date\";s:1:\"1\";s:14:\"grant_due_date\";s:1:\"1\";s:9:\"custom_20\";s:1:\"1\";}s:13:\"grant_type_op\";s:2:\"in\";s:16:\"grant_type_value\";a:0:{}s:12:\"status_id_op\";s:2:\"in\";s:15:\"status_id_value\";a:0:{}s:18:\"amount_granted_min\";s:0:\"\";s:18:\"amount_granted_max\";s:0:\"\";s:17:\"amount_granted_op\";s:3:\"lte\";s:20:\"amount_granted_value\";s:0:\"\";s:16:\"amount_total_min\";s:0:\"\";s:16:\"amount_total_max\";s:0:\"\";s:15:\"amount_total_op\";s:3:\"lte\";s:18:\"amount_total_value\";s:0:\"\";s:34:\"application_received_date_relative\";s:0:\"\";s:30:\"application_received_date_from\";s:0:\"\";s:28:\"application_received_date_to\";s:0:\"\";s:28:\"money_transfer_date_relative\";s:0:\"\";s:24:\"money_transfer_date_from\";s:0:\"\";s:22:\"money_transfer_date_to\";s:0:\"\";s:23:\"grant_due_date_relative\";s:0:\"\";s:19:\"grant_due_date_from\";s:0:\"\";s:17:\"grant_due_date_to\";s:0:\"\";s:22:\"decision_date_relative\";s:0:\"\";s:18:\"decision_date_from\";s:0:\"\";s:16:\"decision_date_to\";s:0:\"\";s:12:\"custom_62_op\";s:2:\"in\";s:15:\"custom_62_value\";a:0:{}s:12:\"custom_63_op\";s:2:\"eq\";s:15:\"custom_63_value\";s:0:\"\";s:12:\"custom_20_op\";s:3:\"has\";s:15:\"custom_20_value\";s:0:\"\";s:12:\"custom_21_op\";s:3:\"has\";s:15:\"custom_21_value\";s:0:\"\";s:9:\"order_bys\";a:1:{i:1;a:2:{s:6:\"column\";s:9:\"sort_name\";s:5:\"order\";s:3:\"ASC\";}}s:11:\"description\";s:53:\"This report is meant to list all active opportunities\";s:13:\"email_subject\";s:0:\"\";s:8:\"email_to\";s:0:\"\";s:8:\"email_cc\";s:0:\"\";s:9:\"row_count\";s:0:\"\";s:9:\"view_mode\";s:4:\"view\";s:13:\"cache_minutes\";s:2:\"60\";s:10:\"permission\";s:16:\"access CiviGrant\";s:9:\"parent_id\";s:0:\"\";s:8:\"radio_ts\";s:0:\"\";s:6:\"groups\";s:0:\"\";s:11:\"instance_id\";s:2:\"36\";}",
      'is_reserved' =>  0,
    ),
  ),
);
