<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'Activity Report',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'activity',
      'title' => ts('Activity Report'),
      'name' => 'Activity Report',
      "description" => "Report of all Non-Contribution Activities by Activity Type, Date & Status",
      'permission' => 'access CiviReport',
      'is_active' => 1,
      "form_values" => "a:50:{s:8:\"entryURL\";s:65:\"https://test2-dms.canadahelps.org/civicrm/report/activity?reset=1\";s:6:\"fields\";a:7:{s:16:\"contact_assignee\";s:1:\"1\";s:14:\"contact_target\";s:1:\"1\";s:16:\"activity_type_id\";s:1:\"1\";s:16:\"activity_subject\";s:1:\"1\";s:18:\"activity_date_time\";s:1:\"1\";s:9:\"status_id\";s:1:\"1\";s:11:\"priority_id\";s:1:\"1\";}s:17:\"contact_source_op\";s:3:\"has\";s:20:\"contact_source_value\";s:0:\"\";s:19:\"contact_assignee_op\";s:3:\"has\";s:22:\"contact_assignee_value\";s:0:\"\";s:17:\"contact_target_op\";s:3:\"has\";s:20:\"contact_target_value\";s:0:\"\";s:15:\"current_user_op\";s:2:\"eq\";s:18:\"current_user_value\";s:1:\"0\";s:27:\"activity_date_time_relative\";s:0:\"\";s:23:\"activity_date_time_from\";s:0:\"\";s:21:\"activity_date_time_to\";s:0:\"\";s:19:\"activity_subject_op\";s:3:\"has\";s:22:\"activity_subject_value\";s:0:\"\";s:19:\"activity_type_id_op\";s:5:\"notin\";s:22:\"activity_type_id_value\";a:3:{i:0;s:2:\"52\";i:1;s:2:\"51\";i:2;s:1:\"6\";}s:12:\"status_id_op\";s:2:\"in\";s:15:\"status_id_value\";a:0:{}s:11:\"location_op\";s:3:\"has\";s:14:\"location_value\";s:0:\"\";s:10:\"details_op\";s:3:\"has\";s:13:\"details_value\";s:0:\"\";s:14:\"priority_id_op\";s:2:\"in\";s:17:\"priority_id_value\";a:0:{}s:17:\"street_address_op\";s:3:\"has\";s:20:\"street_address_value\";s:0:\"\";s:14:\"postal_code_op\";s:3:\"has\";s:17:\"postal_code_value\";s:0:\"\";s:7:\"city_op\";s:3:\"has\";s:10:\"city_value\";s:0:\"\";s:13:\"country_id_op\";s:2:\"in\";s:16:\"country_id_value\";a:0:{}s:20:\"state_province_id_op\";s:2:\"in\";s:23:\"state_province_id_value\";a:0:{}s:6:\"gid_op\";s:2:\"in\";s:9:\"gid_value\";a:0:{}s:9:\"order_bys\";a:2:{i:1;a:3:{s:6:\"column\";s:16:\"activity_type_id\";s:5:\"order\";s:3:\"ASC\";s:7:\"section\";s:1:\"1\";}i:2;a:2:{s:6:\"column\";s:16:\"activity_type_id\";s:5:\"order\";s:3:\"ASC\";}}s:11:\"description\";s:73:\"Report of all Non-Contribution Activities by Activity Type, Date & Status\";s:13:\"email_subject\";s:0:\"\";s:8:\"email_to\";s:0:\"\";s:8:\"email_cc\";s:0:\"\";s:9:\"row_count\";s:0:\"\";s:9:\"view_mode\";s:8:\"criteria\";s:13:\"cache_minutes\";s:2:\"60\";s:10:\"permission\";s:17:\"access CiviReport\";s:9:\"parent_id\";s:0:\"\";s:8:\"radio_ts\";s:0:\"\";s:6:\"groups\";s:0:\"\";s:11:\"instance_id\";s:2:\"35\";}",
      'is_reserved' =>  0,
    ),
  ),
);
