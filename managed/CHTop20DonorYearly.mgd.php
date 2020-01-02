<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'CH Top 20 Donor (Last 12 Months)',
    'update' => 'always',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'contribute/topDonor',
      'title' => ts('CH Top 20 Donor (Last 12 Months)'),
      "description" => "Top 20 Donors in the Last 12 Months.",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => "a:106:{s:8:\"entryURL\";s:76:\"https://test2-dms.canadahelps.org/civicrm/report/contribute/topDonor?reset=1\";s:6:\"fields\";a:4:{s:12:\"display_name\";s:1:\"1\";s:12:\"total_amount\";s:1:\"1\";s:9:\"custom_14\";s:1:\"1\";s:9:\"custom_15\";s:1:\"1\";}s:25:\"address_street_number_min\";s:0:\"\";s:25:\"address_street_number_max\";s:0:\"\";s:24:\"address_street_number_op\";s:3:\"lte\";s:27:\"address_street_number_value\";s:0:\"\";s:23:\"address_street_name_min\";s:0:\"\";s:23:\"address_street_name_max\";s:0:\"\";s:22:\"address_street_name_op\";s:3:\"lte\";s:25:\"address_street_name_value\";s:0:\"\";s:25:\"address_street_address_op\";s:3:\"has\";s:28:\"address_street_address_value\";s:0:\"\";s:15:\"address_city_op\";s:3:\"has\";s:18:\"address_city_value\";s:0:\"\";s:23:\"address_postal_code_min\";s:0:\"\";s:23:\"address_postal_code_max\";s:0:\"\";s:22:\"address_postal_code_op\";s:3:\"lte\";s:25:\"address_postal_code_value\";s:0:\"\";s:30:\"address_postal_code_suffix_min\";s:0:\"\";s:30:\"address_postal_code_suffix_max\";s:0:\"\";s:29:\"address_postal_code_suffix_op\";s:3:\"lte\";s:32:\"address_postal_code_suffix_value\";s:0:\"\";s:20:\"address_county_id_op\";s:2:\"in\";s:23:\"address_county_id_value\";a:0:{}s:28:\"address_state_province_id_op\";s:2:\"in\";s:31:\"address_state_province_id_value\";a:0:{}s:21:\"address_country_id_op\";s:2:\"in\";s:24:\"address_country_id_value\";a:0:{}s:21:\"receive_date_relative\";s:11:\"ending.year\";s:17:\"receive_date_from\";s:0:\"\";s:15:\"receive_date_to\";s:0:\"\";s:11:\"currency_op\";s:2:\"in\";s:14:\"currency_value\";a:0:{}s:15:\"total_range_min\";s:0:\"\";s:15:\"total_range_max\";s:0:\"\";s:14:\"total_range_op\";s:2:\"eq\";s:17:\"total_range_value\";s:2:\"20\";s:20:\"financial_type_id_op\";s:2:\"in\";s:23:\"financial_type_id_value\";a:0:{}s:25:\"contribution_status_id_op\";s:2:\"in\";s:28:\"contribution_status_id_value\";a:1:{i:0;s:1:\"1\";}s:15:\"card_type_id_op\";s:2:\"in\";s:18:\"card_type_id_value\";a:0:{}s:8:\"tagid_op\";s:2:\"in\";s:11:\"tagid_value\";a:0:{}s:6:\"gid_op\";s:2:\"in\";s:9:\"gid_value\";a:0:{}s:12:\"custom_13_op\";s:2:\"in\";s:15:\"custom_13_value\";a:0:{}s:12:\"custom_25_op\";s:2:\"in\";s:15:\"custom_25_value\";a:0:{}s:18:\"custom_26_relative\";s:0:\"\";s:14:\"custom_26_from\";s:0:\"\";s:12:\"custom_26_to\";s:0:\"\";s:13:\"custom_14_min\";s:0:\"\";s:13:\"custom_14_max\";s:0:\"\";s:12:\"custom_14_op\";s:3:\"lte\";s:15:\"custom_14_value\";s:0:\"\";s:13:\"custom_15_min\";s:0:\"\";s:13:\"custom_15_max\";s:0:\"\";s:12:\"custom_15_op\";s:3:\"lte\";s:15:\"custom_15_value\";s:0:\"\";s:18:\"custom_16_relative\";s:0:\"\";s:14:\"custom_16_from\";s:0:\"\";s:12:\"custom_16_to\";s:0:\"\";s:18:\"custom_17_relative\";s:0:\"\";s:14:\"custom_17_from\";s:0:\"\";s:12:\"custom_17_to\";s:0:\"\";s:13:\"custom_18_min\";s:0:\"\";s:13:\"custom_18_max\";s:0:\"\";s:12:\"custom_18_op\";s:3:\"lte\";s:15:\"custom_18_value\";s:0:\"\";s:13:\"custom_19_min\";s:0:\"\";s:13:\"custom_19_max\";s:0:\"\";s:12:\"custom_19_op\";s:3:\"lte\";s:15:\"custom_19_value\";s:0:\"\";s:12:\"custom_36_op\";s:3:\"has\";s:15:\"custom_36_value\";s:0:\"\";s:12:\"custom_37_op\";s:3:\"has\";s:15:\"custom_37_value\";s:0:\"\";s:13:\"custom_38_min\";s:0:\"\";s:13:\"custom_38_max\";s:0:\"\";s:12:\"custom_38_op\";s:3:\"lte\";s:15:\"custom_38_value\";s:0:\"\";s:12:\"custom_32_op\";s:2:\"eq\";s:15:\"custom_32_value\";s:0:\"\";s:12:\"custom_33_op\";s:2:\"eq\";s:15:\"custom_33_value\";s:0:\"\";s:12:\"custom_34_op\";s:3:\"has\";s:15:\"custom_34_value\";s:0:\"\";s:12:\"custom_35_op\";s:3:\"has\";s:15:\"custom_35_value\";s:0:\"\";s:9:\"order_bys\";a:1:{i:1;a:1:{s:6:\"column\";s:1:\"-\";}}s:11:\"description\";s:148:\"Provides a list of the top donors during a time period you define. You can include as many donors as you want (for example, top 100 of your donors).\";s:13:\"email_subject\";s:0:\"\";s:8:\"email_to\";s:0:\"\";s:8:\"email_cc\";s:0:\"\";s:9:\"row_count\";s:0:\"\";s:9:\"view_mode\";s:4:\"view\";s:13:\"cache_minutes\";s:2:\"60\";s:10:\"permission\";s:17:\"access CiviReport\";s:9:\"parent_id\";s:0:\"\";s:12:\"drilldown_id\";s:0:\"\";s:8:\"radio_ts\";s:0:\"\";s:6:\"groups\";s:0:\"\";s:11:\"instance_id\";N;}",
    ),
  ),
);
