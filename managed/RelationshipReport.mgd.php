<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Relationship Report',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'contribute/detail',
      'title' => ts('Relationship Report'),
      'name' => 'Relationship Report',
      "description" => "Contact A to Contact B Relationship Report",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => "a:46:{s:8:\"entryURL\";s:77:\"https://test2-dms.canadahelps.org/civicrm/report/contact/relationship?reset=1\";s:6:\"fields\";a:7:{s:11:\"sort_name_a\";s:1:\"1\";s:14:\"contact_type_a\";s:1:\"1\";s:11:\"sort_name_b\";s:1:\"1\";s:14:\"contact_type_b\";s:1:\"1\";s:9:\"label_a_b\";s:1:\"1\";s:9:\"label_b_a\";s:1:\"1\";s:15:\"relationship_id\";s:1:\"1\";}s:14:\"sort_name_a_op\";s:3:\"has\";s:17:\"sort_name_a_value\";s:0:\"\";s:17:\"contact_type_a_op\";s:2:\"in\";s:20:\"contact_type_a_value\";a:0:{}s:14:\"sort_name_b_op\";s:3:\"has\";s:17:\"sort_name_b_value\";s:0:\"\";s:17:\"contact_type_b_op\";s:2:\"in\";s:20:\"contact_type_b_value\";a:0:{}s:12:\"is_active_op\";s:2:\"eq\";s:15:\"is_active_value\";s:1:\"1\";s:11:\"is_valid_op\";s:2:\"eq\";s:14:\"is_valid_value\";s:0:\"\";s:23:\"relationship_type_id_op\";s:2:\"in\";s:26:\"relationship_type_id_value\";a:0:{}s:19:\"start_date_relative\";s:0:\"\";s:15:\"start_date_from\";s:0:\"\";s:13:\"start_date_to\";s:0:\"\";s:17:\"end_date_relative\";s:0:\"\";s:13:\"end_date_from\";s:0:\"\";s:11:\"end_date_to\";s:0:\"\";s:27:\"active_period_date_relative\";s:0:\"\";s:23:\"active_period_date_from\";s:0:\"\";s:21:\"active_period_date_to\";s:0:\"\";s:13:\"country_id_op\";s:2:\"in\";s:16:\"country_id_value\";a:0:{}s:20:\"state_province_id_op\";s:2:\"in\";s:23:\"state_province_id_value\";a:0:{}s:8:\"tagid_op\";s:2:\"in\";s:11:\"tagid_value\";a:0:{}s:6:\"gid_op\";s:2:\"in\";s:9:\"gid_value\";a:0:{}s:9:\"order_bys\";a:2:{i:1;a:2:{s:6:\"column\";s:11:\"sort_name_a\";s:5:\"order\";s:3:\"ASC\";}i:2;a:2:{s:6:\"column\";s:11:\"sort_name_b\";s:5:\"order\";s:3:\"ASC\";}}s:11:\"description\";s:42:\"Contact A to Contact B Relationship Report\";s:13:\"email_subject\";s:0:\"\";s:8:\"email_to\";s:0:\"\";s:8:\"email_cc\";s:0:\"\";s:9:\"row_count\";s:0:\"\";s:9:\"view_mode\";s:8:\"criteria\";s:13:\"cache_minutes\";s:2:\"60\";s:10:\"permission\";s:17:\"access CiviReport\";s:9:\"parent_id\";s:0:\"\";s:8:\"radio_ts\";s:0:\"\";s:6:\"groups\";s:0:\"\";s:11:\"instance_id\";s:2:\"33\";}",
      'is_reserved' =>  0,
    ),
  ),
);
