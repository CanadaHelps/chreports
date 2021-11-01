<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Retention Rate Report (Dashlet)',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'biz.jmaconsulting.chreports/retentionrate',
      'title' => ts('Retention Rate Report (Dashlet)'),
      'name' => 'Retention Rate Report (Dashlet)',
      "description" => "Retention Rate Report",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => "a:43:{s:8:\"entryURL\";s:64:\"https://test1-dms.canadahelps.org/dms/report/instance/51?reset=1\";s:6:\"fields\";a:6:{s:14:\"retention_rate\";s:1:\"1\";i:2017;s:1:\"1\";i:2018;s:1:\"1\";i:2019;s:1:\"1\";i:2020;s:1:\"1\";i:2021;s:1:\"1\";}s:15:\"contact_type_op\";s:2:\"in\";s:18:\"contact_type_value\";a:0:{}s:19:\"contact_sub_type_op\";s:2:\"in\";s:22:\"contact_sub_type_value\";a:0:{}s:14:\"postal_code_op\";s:3:\"has\";s:17:\"postal_code_value\";s:0:\"\";s:7:\"city_op\";s:3:\"has\";s:10:\"city_value\";s:0:\"\";s:13:\"country_id_op\";s:2:\"in\";s:16:\"country_id_value\";a:0:{}s:20:\"state_province_id_op\";s:2:\"in\";s:23:\"state_province_id_value\";a:0:{}s:12:\"county_id_op\";s:2:\"in\";s:15:\"county_id_value\";a:0:{}s:12:\"base_year_op\";s:2:\"eq\";s:15:\"base_year_value\";s:4:\"2017\";s:20:\"financial_type_id_op\";s:2:\"in\";s:23:\"financial_type_id_value\";a:0:{}s:25:\"contribution_status_id_op\";s:2:\"in\";s:28:\"contribution_status_id_value\";a:1:{i:0;s:1:\"1\";}s:9:\"source_op\";s:3:\"has\";s:12:\"source_value\";s:0:\"\";s:23:\"contribution_page_id_op\";s:2:\"in\";s:26:\"contribution_page_id_value\";a:0:{}s:8:\"tagid_op\";s:2:\"in\";s:11:\"tagid_value\";a:0:{}s:6:\"gid_op\";s:2:\"in\";s:9:\"gid_value\";a:0:{}s:11:\"description\";s:21:\"Retention Rate Report\";s:13:\"email_subject\";s:0:\"\";s:8:\"email_to\";s:0:\"\";s:8:\"email_cc\";s:0:\"\";s:9:\"row_count\";s:0:\"\";s:9:\"view_mode\";s:4:\"view\";s:14:\"addToDashboard\";s:1:\"1\";s:13:\"cache_minutes\";s:2:\"60\";s:10:\"permission\";s:17:\"access CiviReport\";s:9:\"parent_id\";s:0:\"\";s:8:\"radio_ts\";s:0:\"\";s:6:\"groups\";s:0:\"\";s:11:\"instance_id\";s:2:\"51\";}",
      'is_reserved' =>  0,
    ),
  ),
);
