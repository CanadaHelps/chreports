<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Contribution History by Fund (Detailed)',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'contribute/detail',
      'title' => ts('Contribution History by Fund (Detailed)'),
      'name' => 'Contribution History by Fund (Detailed)',
      "description" => "In depth view of contributions by Fund",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => "a:58:{s:8:\"entryURL\";s:74:\"https://test2-dms.canadahelps.org/civicrm/report/contribute/detail?reset=1\";s:6:\"fields\";a:9:{s:9:\"sort_name\";s:1:\"1\";s:10:\"exposed_id\";s:1:\"1\";s:17:\"financial_type_id\";s:1:\"1\";s:20:\"contribution_page_id\";s:1:\"1\";s:6:\"source\";s:1:\"1\";s:21:\"payment_instrument_id\";s:1:\"1\";s:12:\"receive_date\";s:1:\"1\";s:12:\"total_amount\";s:1:\"1\";s:11:\"campaign_id\";s:1:\"1\";}s:23:\"contribution_or_soft_op\";s:2:\"eq\";s:26:\"contribution_or_soft_value\";s:18:\"contributions_only\";s:21:\"receive_date_relative\";s:0:\"\";s:17:\"receive_date_from\";s:0:\"\";s:15:\"receive_date_to\";s:0:\"\";s:22:\"contribution_source_op\";s:3:\"has\";s:25:\"contribution_source_value\";s:0:\"\";s:25:\"non_deductible_amount_min\";s:0:\"\";s:25:\"non_deductible_amount_max\";s:0:\"\";s:24:\"non_deductible_amount_op\";s:3:\"lte\";s:27:\"non_deductible_amount_value\";s:0:\"\";s:20:\"financial_type_id_op\";s:2:\"in\";s:23:\"financial_type_id_value\";a:0:{}s:23:\"contribution_page_id_op\";s:2:\"in\";s:26:\"contribution_page_id_value\";a:0:{}s:24:\"payment_instrument_id_op\";s:2:\"in\";s:27:\"payment_instrument_id_value\";a:0:{}s:25:\"contribution_status_id_op\";s:2:\"in\";s:28:\"contribution_status_id_value\";a:1:{i:0;s:1:\"1\";}s:16:\"total_amount_min\";s:0:\"\";s:16:\"total_amount_max\";s:0:\"\";s:15:\"total_amount_op\";s:3:\"lte\";s:18:\"total_amount_value\";s:0:\"\";s:20:\"cancel_date_relative\";s:0:\"\";s:16:\"cancel_date_from\";s:0:\"\";s:14:\"cancel_date_to\";s:0:\"\";s:16:\"cancel_reason_op\";s:3:\"has\";s:19:\"cancel_reason_value\";s:0:\"\";s:14:\"campaign_id_op\";s:2:\"in\";s:17:\"campaign_id_value\";a:0:{}s:12:\"custom_13_op\";s:2:\"in\";s:15:\"custom_13_value\";a:0:{}s:15:\"card_type_id_op\";s:2:\"in\";s:18:\"card_type_id_value\";a:0:{}s:13:\"ordinality_op\";s:2:\"in\";s:16:\"ordinality_value\";a:0:{}s:7:\"note_op\";s:3:\"has\";s:10:\"note_value\";s:0:\"\";s:8:\"tagid_op\";s:2:\"in\";s:11:\"tagid_value\";a:0:{}s:6:\"gid_op\";s:2:\"in\";s:9:\"gid_value\";a:0:{}s:9:\"group_bys\";a:1:{s:15:\"contribution_id\";s:1:\"1\";}s:9:\"order_bys\";a:1:{i:1;a:3:{s:6:\"column\";s:17:\"financial_type_id\";s:5:\"order\";s:3:\"ASC\";s:7:\"section\";s:1:\"1\";}}s:11:\"description\";s:191:\"Lists specific contributions by criteria including contact, time period, financial type, contributor location, etc. Contribution summary report points to this report for contribution details.\";s:13:\"email_subject\";s:0:\"\";s:8:\"email_to\";s:0:\"\";s:8:\"email_cc\";s:0:\"\";s:9:\"row_count\";s:0:\"\";s:9:\"view_mode\";s:4:\"view\";s:13:\"cache_minutes\";s:2:\"60\";s:10:\"permission\";s:17:\"access CiviReport\";s:9:\"parent_id\";s:0:\"\";s:8:\"radio_ts\";s:0:\"\";s:6:\"groups\";s:0:\"\";s:11:\"instance_id\";N;}",
      'is_reserved' =>  0,
    ),
  ),
);
