<?php
use CRM_Chreports_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Chreports_Upgrader extends CRM_Chreports_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   *
  public function install() {
    $this->executeSqlFile('sql/myinstall.sql');
  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   *
  public function postInstall() {
    $customFieldId = civicrm_api3('CustomField', 'getvalue', array(
      'return' => array("id"),
      'name' => "customFieldCreatedViaManagedHook",
    ));
    civicrm_api3('Setting', 'create', array(
      'myWeirdFieldSetting' => array('id' => $customFieldId, 'weirdness' => 1),
    ));
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   *
  public function uninstall() {
   $this->executeSqlFile('sql/myuninstall.sql');
  }

  /**
   * Example: Run a simple query when a module is enabled.
   *
  public function enable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a simple query when a module is disabled.
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1100() {
    $this->ctx->log->info('Applying update 1100: Update reports to used the extendedDetail report');
    $reportNames = [
      'Contribution History by Campaign Group (Detailed)',
      'Contribution History by Campaign (Detailed)',
      'Contribution History by Fund (Detailed)',
      'Receipts',
      'In Memory of',
    ];
    foreach ($reportNames as $name) {
      $report = civicrm_api3('ReportInstance', 'get', ['name' => $name]);
      if (!empty($report['values'])) {
        foreach ($report['values'] as $r) {
          $formValues = unserialize($r['form_values']);
          $formValues['entryURL'] = str_replace('contribute/detail', 'biz.jmaconsulting.chreports/extendeddetail', $formValues['entryURL']);
          civicrm_api3('ReportInstance', 'create', [
           'report_id' => 'biz.jmaconsulting.chreports/extendeddetail',
           'form_values' => serialize($formValues),
           'id' => $r['id'],
          ]);
        }
      }
    }
    return TRUE;
  } // */

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1300() {
    $this->ctx->log->info('Applying update 1300: Update reports to used correct formvalues');
    $report = civicrm_api3('ReportInstance', 'get', ['name' => 'LYBNT']);
    if (!empty($report['values'])) {
      foreach ($report['values'] as $r) {
        civicrm_api3('ReportInstance', 'create', [
          'report_id' => 'biz.jmaconsulting.chreports/revisedlybunt',
          'form_values' => "a:98:{s:8:\"entryURL\";s:91:\"https://tcr-test-dms.canadahelps.org/civicrm/report/instance/19?reset=1&output=criteria\";s:6:\"fields\";a:4:{s:10:\"exposed_id\";s:1:\"1\";s:9:\"sort_name\";s:1:\"1\";s:23:\"civicrm_life_time_total\";s:1:\"1\";s:22:\"last_year_total_amount\";s:1:\"1\";}s:12:\"sort_name_op\";s:3:\"has\";s:15:\"sort_name_value\";s:0:\"\";s:6:\"id_min\";s:0:\"\";s:6:\"id_max\";s:0:\"\";s:5:\"id_op\";s:3:\"lte\";s:8:\"id_value\";s:0:\"\";s:12:\"gender_id_op\";s:2:\"in\";s:15:\"gender_id_value\";a:0:{}s:19:\"birth_date_relative\";s:0:\"\";s:15:\"birth_date_from\";s:0:\"\";s:13:\"birth_date_to\";s:0:\"\";s:15:\"contact_type_op\";s:2:\"in\";s:18:\"contact_type_value\";a:0:{}s:19:\"contact_sub_type_op\";s:2:\"in\";s:22:\"contact_sub_type_value\";a:0:{}s:14:\"is_deceased_op\";s:2:\"eq\";s:17:\"is_deceased_value\";s:0:\"\";s:15:\"do_not_phone_op\";s:2:\"eq\";s:18:\"do_not_phone_value\";s:0:\"\";s:15:\"do_not_email_op\";s:2:\"eq\";s:18:\"do_not_email_value\";s:0:\"\";s:13:\"do_not_sms_op\";s:2:\"eq\";s:16:\"do_not_sms_value\";s:0:\"\";s:14:\"do_not_mail_op\";s:2:\"eq\";s:17:\"do_not_mail_value\";s:0:\"\";s:13:\"is_opt_out_op\";s:2:\"eq\";s:16:\"is_opt_out_value\";s:0:\"\";s:17:\"street_address_op\";s:3:\"has\";s:20:\"street_address_value\";s:0:\"\";s:14:\"postal_code_op\";s:3:\"has\";s:17:\"postal_code_value\";s:0:\"\";s:7:\"city_op\";s:3:\"has\";s:10:\"city_value\";s:0:\"\";s:13:\"country_id_op\";s:2:\"in\";s:16:\"country_id_value\";a:0:{}s:20:\"state_province_id_op\";s:2:\"in\";s:23:\"state_province_id_value\";a:0:{}s:12:\"county_id_op\";s:2:\"in\";s:15:\"county_id_value\";a:0:{}s:6:\"yid_op\";s:8:\"calendar\";s:9:\"yid_value\";s:4:\"2020\";s:20:\"financial_type_id_op\";s:2:\"in\";s:23:\"financial_type_id_value\";a:0:{}s:25:\"contribution_status_id_op\";s:2:\"in\";s:28:\"contribution_status_id_value\";a:1:{i:0;s:1:\"1\";}s:14:\"campaign_id_op\";s:2:\"in\";s:17:\"campaign_id_value\";a:0:{}s:15:\"card_type_id_op\";s:2:\"in\";s:18:\"card_type_id_value\";a:0:{}s:8:\"tagid_op\";s:2:\"in\";s:11:\"tagid_value\";a:0:{}s:6:\"gid_op\";s:2:\"in\";s:9:\"gid_value\";a:0:{}s:12:\"custom_25_op\";s:2:\"in\";s:15:\"custom_25_value\";a:0:{}s:18:\"custom_26_relative\";s:0:\"\";s:14:\"custom_26_from\";s:0:\"\";s:12:\"custom_26_to\";s:0:\"\";s:13:\"custom_14_min\";s:0:\"\";s:13:\"custom_14_max\";s:0:\"\";s:12:\"custom_14_op\";s:3:\"lte\";s:15:\"custom_14_value\";s:0:\"\";s:13:\"custom_15_min\";s:0:\"\";s:13:\"custom_15_max\";s:0:\"\";s:12:\"custom_15_op\";s:3:\"lte\";s:15:\"custom_15_value\";s:0:\"\";s:18:\"custom_16_relative\";s:0:\"\";s:14:\"custom_16_from\";s:0:\"\";s:12:\"custom_16_to\";s:0:\"\";s:18:\"custom_17_relative\";s:0:\"\";s:14:\"custom_17_from\";s:0:\"\";s:12:\"custom_17_to\";s:0:\"\";s:13:\"custom_18_min\";s:0:\"\";s:13:\"custom_18_max\";s:0:\"\";s:12:\"custom_18_op\";s:3:\"lte\";s:15:\"custom_18_value\";s:0:\"\";s:13:\"custom_19_min\";s:0:\"\";s:13:\"custom_19_max\";s:0:\"\";s:12:\"custom_19_op\";s:3:\"lte\";s:15:\"custom_19_value\";s:0:\"\";s:12:\"custom_39_op\";s:3:\"has\";s:15:\"custom_39_value\";s:0:\"\";s:9:\"order_bys\";a:2:{i:1;a:2:{s:6:\"column\";s:22:\"last_year_total_amount\";s:5:\"order\";s:4:\"DESC\";}i:2;a:2:{s:6:\"column\";s:9:\"sort_name\";s:5:\"order\";s:3:\"ASC\";}}s:11:\"description\";s:47:\"Contributors who gave 'Last Year, But Not This'\";s:13:\"email_subject\";s:0:\"\";s:8:\"email_to\";s:0:\"\";s:8:\"email_cc\";s:0:\"\";s:9:\"row_count\";s:0:\"\";s:9:\"view_mode\";s:4:\"view\";s:13:\"cache_minutes\";s:2:\"60\";s:10:\"permission\";s:18:\"administer Reports\";s:9:\"parent_id\";s:0:\"\";s:12:\"drilldown_id\";s:0:\"\";s:8:\"radio_ts\";s:0:\"\";s:6:\"groups\";s:0:\"\";s:11:\"instance_id\";s:2:\"19\";}",
          'id' => $r['id'],
          'title' => $r['title'],
        ]);
      }
    }
    $report = civicrm_api3('ReportInstance', 'get', ['name' => 'SYBNT']);
    if (!empty($report['values'])) {
      foreach ($report['values'] as $r) {
        civicrm_api3('ReportInstance', 'create', [
          'report_id' => 'biz.jmaconsulting.chreports/extendlybunt',
          'form_values' => "a:44:{s:8:\"entryURL\";s:91:\"https://tcr-test-dms.canadahelps.org/civicrm/report/instance/20?reset=1&output=criteria\";s:6:\"fields\";a:7:{s:10:\"exposed_id\";s:1:\"1\";s:9:\"sort_name\";s:1:\"1\";s:23:\"civicrm_life_time_total\";s:1:\"1\";s:27:\"last_four_year_total_amount\";s:1:\"1\";s:28:\"last_three_year_total_amount\";s:1:\"1\";s:26:\"last_two_year_total_amount\";s:1:\"1\";s:22:\"last_year_total_amount\";s:1:\"1\";}s:12:\"sort_name_op\";s:3:\"has\";s:15:\"sort_name_value\";s:0:\"\";s:6:\"id_min\";s:0:\"\";s:6:\"id_max\";s:0:\"\";s:5:\"id_op\";s:3:\"lte\";s:8:\"id_value\";s:0:\"\";s:15:\"contact_type_op\";s:2:\"in\";s:18:\"contact_type_value\";a:0:{}s:15:\"do_not_phone_op\";s:2:\"eq\";s:18:\"do_not_phone_value\";s:0:\"\";s:15:\"do_not_email_op\";s:2:\"eq\";s:18:\"do_not_email_value\";s:0:\"\";s:13:\"do_not_sms_op\";s:2:\"eq\";s:16:\"do_not_sms_value\";s:0:\"\";s:14:\"do_not_mail_op\";s:2:\"eq\";s:17:\"do_not_mail_value\";s:0:\"\";s:13:\"is_opt_out_op\";s:2:\"eq\";s:16:\"is_opt_out_value\";s:0:\"\";s:6:\"yid_op\";s:8:\"calendar\";s:9:\"yid_value\";s:4:\"2020\";s:20:\"financial_type_id_op\";s:2:\"in\";s:23:\"financial_type_id_value\";a:0:{}s:25:\"contribution_status_id_op\";s:2:\"in\";s:28:\"contribution_status_id_value\";a:1:{i:0;s:1:\"1\";}s:15:\"card_type_id_op\";s:2:\"in\";s:18:\"card_type_id_value\";a:0:{}s:12:\"custom_39_op\";s:3:\"has\";s:15:\"custom_39_value\";s:0:\"\";s:9:\"order_bys\";a:2:{i:1;a:2:{s:6:\"column\";s:22:\"last_year_total_amount\";s:5:\"order\";s:4:\"DESC\";}i:2;a:2:{s:6:\"column\";s:9:\"sort_name\";s:5:\"order\";s:3:\"ASC\";}}s:11:\"description\";s:47:\"Contributors who gave 'Some Year, But Not This'\";s:13:\"email_subject\";s:0:\"\";s:8:\"email_to\";s:0:\"\";s:8:\"email_cc\";s:0:\"\";s:9:\"row_count\";s:0:\"\";s:9:\"view_mode\";s:8:\"criteria\";s:13:\"cache_minutes\";s:2:\"60\";s:10:\"permission\";s:18:\"administer Reports\";s:9:\"parent_id\";s:0:\"\";s:12:\"drilldown_id\";s:0:\"\";s:8:\"radio_ts\";s:0:\"\";s:6:\"groups\";s:0:\"\";s:11:\"instance_id\";N;}",
          'id' => $r['id'],
          'title' => $r['title'],
        ]);
      }
    }
    return TRUE;
  }

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1400() {
    $this->ctx->log->info('Applying update 1400: Update LYBNT report to used correct instance');
    $report = civicrm_api3('ReportInstance', 'get', ['name' => 'LYBNT']);
    if (!empty($report['values'])) {
      foreach ($report['values'] as $r) {
        civicrm_api3('ReportInstance', 'create', [
          'report_id' => 'biz.jmaconsulting.chreports/revisedlybunt',
          'id' => $r['id'],
          'title' => $r['title'],
          'form_values' => "a:98:{s:8:\"entryURL\";s:91:\"https://tcr-test-dms.canadahelps.org/civicrm/report/instance/19?reset=1&output=criteria\";s:6:\"fields\";a:4:{s:10:\"exposed_id\";s:1:\"1\";s:9:\"sort_name\";s:1:\"1\";s:23:\"civicrm_life_time_total\";s:1:\"1\";s:22:\"last_year_total_amount\";s:1:\"1\";}s:12:\"sort_name_op\";s:3:\"has\";s:15:\"sort_name_value\";s:0:\"\";s:6:\"id_min\";s:0:\"\";s:6:\"id_max\";s:0:\"\";s:5:\"id_op\";s:3:\"lte\";s:8:\"id_value\";s:0:\"\";s:12:\"gender_id_op\";s:2:\"in\";s:15:\"gender_id_value\";a:0:{}s:19:\"birth_date_relative\";s:0:\"\";s:15:\"birth_date_from\";s:0:\"\";s:13:\"birth_date_to\";s:0:\"\";s:15:\"contact_type_op\";s:2:\"in\";s:18:\"contact_type_value\";a:0:{}s:19:\"contact_sub_type_op\";s:2:\"in\";s:22:\"contact_sub_type_value\";a:0:{}s:14:\"is_deceased_op\";s:2:\"eq\";s:17:\"is_deceased_value\";s:0:\"\";s:15:\"do_not_phone_op\";s:2:\"eq\";s:18:\"do_not_phone_value\";s:0:\"\";s:15:\"do_not_email_op\";s:2:\"eq\";s:18:\"do_not_email_value\";s:0:\"\";s:13:\"do_not_sms_op\";s:2:\"eq\";s:16:\"do_not_sms_value\";s:0:\"\";s:14:\"do_not_mail_op\";s:2:\"eq\";s:17:\"do_not_mail_value\";s:0:\"\";s:13:\"is_opt_out_op\";s:2:\"eq\";s:16:\"is_opt_out_value\";s:0:\"\";s:17:\"street_address_op\";s:3:\"has\";s:20:\"street_address_value\";s:0:\"\";s:14:\"postal_code_op\";s:3:\"has\";s:17:\"postal_code_value\";s:0:\"\";s:7:\"city_op\";s:3:\"has\";s:10:\"city_value\";s:0:\"\";s:13:\"country_id_op\";s:2:\"in\";s:16:\"country_id_value\";a:0:{}s:20:\"state_province_id_op\";s:2:\"in\";s:23:\"state_province_id_value\";a:0:{}s:12:\"county_id_op\";s:2:\"in\";s:15:\"county_id_value\";a:0:{}s:6:\"yid_op\";s:8:\"calendar\";s:9:\"yid_value\";s:4:\"2020\";s:20:\"financial_type_id_op\";s:2:\"in\";s:23:\"financial_type_id_value\";a:0:{}s:25:\"contribution_status_id_op\";s:2:\"in\";s:28:\"contribution_status_id_value\";a:1:{i:0;s:1:\"1\";}s:14:\"campaign_id_op\";s:2:\"in\";s:17:\"campaign_id_value\";a:0:{}s:15:\"card_type_id_op\";s:2:\"in\";s:18:\"card_type_id_value\";a:0:{}s:8:\"tagid_op\";s:2:\"in\";s:11:\"tagid_value\";a:0:{}s:6:\"gid_op\";s:2:\"in\";s:9:\"gid_value\";a:0:{}s:12:\"custom_25_op\";s:2:\"in\";s:15:\"custom_25_value\";a:0:{}s:18:\"custom_26_relative\";s:0:\"\";s:14:\"custom_26_from\";s:0:\"\";s:12:\"custom_26_to\";s:0:\"\";s:13:\"custom_14_min\";s:0:\"\";s:13:\"custom_14_max\";s:0:\"\";s:12:\"custom_14_op\";s:3:\"lte\";s:15:\"custom_14_value\";s:0:\"\";s:13:\"custom_15_min\";s:0:\"\";s:13:\"custom_15_max\";s:0:\"\";s:12:\"custom_15_op\";s:3:\"lte\";s:15:\"custom_15_value\";s:0:\"\";s:18:\"custom_16_relative\";s:0:\"\";s:14:\"custom_16_from\";s:0:\"\";s:12:\"custom_16_to\";s:0:\"\";s:18:\"custom_17_relative\";s:0:\"\";s:14:\"custom_17_from\";s:0:\"\";s:12:\"custom_17_to\";s:0:\"\";s:13:\"custom_18_min\";s:0:\"\";s:13:\"custom_18_max\";s:0:\"\";s:12:\"custom_18_op\";s:3:\"lte\";s:15:\"custom_18_value\";s:0:\"\";s:13:\"custom_19_min\";s:0:\"\";s:13:\"custom_19_max\";s:0:\"\";s:12:\"custom_19_op\";s:3:\"lte\";s:15:\"custom_19_value\";s:0:\"\";s:12:\"custom_39_op\";s:3:\"has\";s:15:\"custom_39_value\";s:0:\"\";s:9:\"order_bys\";a:2:{i:1;a:2:{s:6:\"column\";s:22:\"last_year_total_amount\";s:5:\"order\";s:4:\"DESC\";}i:2;a:2:{s:6:\"column\";s:9:\"sort_name\";s:5:\"order\";s:3:\"ASC\";}}s:11:\"description\";s:47:\"Contributors who gave 'Last Year, But Not This'\";s:13:\"email_subject\";s:0:\"\";s:8:\"email_to\";s:0:\"\";s:8:\"email_cc\";s:0:\"\";s:9:\"row_count\";s:0:\"\";s:9:\"view_mode\";s:4:\"view\";s:13:\"cache_minutes\";s:2:\"60\";s:10:\"permission\";s:18:\"administer Reports\";s:9:\"parent_id\";s:0:\"\";s:12:\"drilldown_id\";s:0:\"\";s:8:\"radio_ts\";s:0:\"\";s:6:\"groups\";s:0:\"\";s:11:\"instance_id\";s:2:\"19\";}",
        ]);
      }
    }
    return TRUE;
  }

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1500() {
    $this->ctx->log->info('Applying update 1500: Update formvaluies for recurring summary report');
    $report = civicrm_api3('ReportInstance', 'get', ['name' => 'Recurring Contributions (Summary)']);
    if (!empty($report['values'])) {
      foreach ($report['values'] as $r) {
        civicrm_api3('ReportInstance', 'create', [
          'report_id' => 'biz.jmaconsulting.chreports/recursummary',
          'id' => $r['id'],
          'title' => $r['title'],
          'form_values' => "a:29:{s:6:\"fields\";a:15:{s:2:\"id\";s:1:\"1\";s:10:\"exposed_id\";s:1:\"1\";s:9:\"sort_name\";s:1:\"1\";s:14:\"street_address\";s:1:\"1\";s:4:\"city\";s:1:\"1\";s:11:\"postal_code\";s:1:\"1\";s:17:\"state_province_id\";s:1:\"1\";s:10:\"country_id\";s:1:\"1\";s:5:\"phone\";s:1:\"1\";s:5:\"email\";s:1:\"1\";s:12:\"total_amount\";s:1:\"1\";s:6:\"source\";s:1:\"1\";s:23:\"completed_contributions\";s:1:\"1\";s:10:\"start_date\";s:1:\"1\";s:17:\"last_month_amount\";s:1:\"1\";}s:12:\"sort_name_op\";s:3:\"has\";s:15:\"sort_name_value\";s:0:\"\";s:6:\"id_min\";s:0:\"\";s:6:\"id_max\";s:0:\"\";s:5:\"id_op\";s:3:\"lte\";s:8:\"id_value\";s:0:\"\";s:21:\"receive_date_relative\";s:4:\"nnll\";s:17:\"receive_date_from\";s:0:\"\";s:15:\"receive_date_to\";s:0:\"\";s:14:\"campaign_id_op\";s:2:\"in\";s:17:\"campaign_id_value\";a:0:{}s:8:\"tagid_op\";s:2:\"in\";s:11:\"tagid_value\";a:0:{}s:6:\"gid_op\";s:2:\"in\";s:9:\"gid_value\";a:0:{}s:9:\"order_bys\";a:1:{i:1;a:1:{s:6:\"column\";s:1:\"-\";}}s:11:\"description\";s:35:\"Overview of Recurring Contributions\";s:13:\"email_subject\";s:0:\"\";s:8:\"email_to\";s:0:\"\";s:8:\"email_cc\";s:0:\"\";s:9:\"row_count\";s:0:\"\";s:9:\"view_mode\";s:4:\"view\";s:13:\"cache_minutes\";s:2:\"60\";s:10:\"permission\";s:17:\"access CiviReport\";s:9:\"parent_id\";s:0:\"\";s:8:\"radio_ts\";s:0:\"\";s:6:\"groups\";s:0:\"\";s:11:\"instance_id\";N;}",
        ]);
      }
    }
    return TRUE;
  }

  public function upgrade_1700() {
    $this->ctx->log->info('Applying update 1700: CRM-768 Update Report templates name and description');
    $result = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'option_group_id' => "report_template",
      'options' => ['limit' => 0],
    ]);
    $reports = array_column($result['values'], NULL, 'name');
    $reports['CRM_Cdntaxreceipts_Form_Report_ReceiptsIssued']['description'] = 'Tax Receipts - Receipts Issued';
    $reports['CRM_Cdntaxreceipts_Form_Report_ReceiptsNotIssued']['description'] = 'Tax Receipts - Receipts Not Issued';
    $reports['CRM_Extendedreport_Form_Report_Contribute_DetailExtended']['description'] = 'Extended Report - Contributions Detail with extra fields';
    $reports['CRM_Chreports_Form_Report_ExtendLYBUNT']['description'] = 'Extended LYBNT';
    $reports['CRM_Chreports_Form_Report_ExtendLYBUNT']['label'] = 'Extended LYBNT';
    $reports['CRM_Chreports_Form_Report_ExtendedDetail']['description'] = 'Extended Contribution Detail';
    $reports['CRM_Chreports_Form_Report_RecurSummary']['label'] = 'Recurring Contributions (Summary)';
    $reports['CRM_Chreports_Form_Report_RevisedLYBUNT']['label'] = 'Revised LYBNT';
    $reports['CRM_Chreports_Form_Report_RevisedLYBUNT']['description'] = 'Revised LYBNT';
    $reports['CRM_Chreports_Form_Report_GLSummaryReport']['label'] = 'Contribution History by GL Account (Summary)';
    $reports['CRM_Chreports_Form_Report_GLSummaryReport']['description'] = 'Overview of contributions by GL Account';
    $reports['CRM_Report_Form_Contribute_Lybunt']['label'] = 'LYBNT Report';
    $reports['CRM_Report_Form_Contribute_Lybunt']['description'] = 'LYBNT means last year but not this year. Provides a list of constituents who donated last year but did not donate during the time period you specify as the current year.';
    $reports['CRM_Chreports_Form_Report_GLAccountDetail']['description'] = 'GL Account Detail';
    $reports['CRM_Chreports_Form_Report_GLAccountDetail']['label'] = 'GL Account Detail';

    foreach($reports as $reportTemplate) {
      $reportTemplate['description'] = str_replace(' (biz.jmaconsulting.chreports)', '', $reportTemplate['description']);
      civicrm_api3('ReportTemplate', 'create', $reportTemplate);
    }
    return TRUE;
  }

  public function upgrade_1800() {
    $this->ctx->log->info('Applying update 1800: CRM-896: Adding 2 new summary reports for dashboard purpose');
    $report = civicrm_api3('ReportInstance', 'get', ['name' => 'Fiscal Year to Date']);
    if(empty($report['values'])) {
      $report_id = civicrm_api3('ReportInstance', 'create', [
        'version' => 3,
        'report_id' => 'contribute/summary',
        'title' => ts('Fiscal Year to Date'),
        'name' => 'Fiscal Year to Date',
        "description" => "Total amounts raised this fiscal year by month",
        'permission' => 'access CiviReport',
        'is_active' => 1,
        'is_reserved' => 1,
        "form_values" => "a:76:{s:8:\"entryURL\";s:73:\"https://success-dms.canadahelps.org/dms/report/contribute/summary?reset=1\";s:6:\"fields\";a:1:{s:12:\"total_amount\";s:1:\"1\";}s:21:\"receive_date_relative\";s:16:\"this.fiscal_year\";s:17:\"receive_date_from\";s:0:\"\";s:15:\"receive_date_to\";s:0:\"\";s:21:\"receipt_date_relative\";s:0:\"\";s:17:\"receipt_date_from\";s:0:\"\";s:15:\"receipt_date_to\";s:0:\"\";s:25:\"contribution_status_id_op\";s:2:\"in\";s:28:\"contribution_status_id_value\";a:1:{i:0;s:1:\"1\";}s:23:\"contribution_page_id_op\";s:2:\"in\";s:26:\"contribution_page_id_value\";a:0:{}s:20:\"financial_type_id_op\";s:2:\"in\";s:23:\"financial_type_id_value\";a:0:{}s:16:\"total_amount_min\";s:0:\"\";s:16:\"total_amount_max\";s:0:\"\";s:15:\"total_amount_op\";s:3:\"lte\";s:18:\"total_amount_value\";s:0:\"\";s:25:\"non_deductible_amount_min\";s:0:\"\";s:25:\"non_deductible_amount_max\";s:0:\"\";s:24:\"non_deductible_amount_op\";s:3:\"lte\";s:27:\"non_deductible_amount_value\";s:0:\"\";s:13:\"total_sum_min\";s:0:\"\";s:13:\"total_sum_max\";s:0:\"\";s:12:\"total_sum_op\";s:3:\"lte\";s:15:\"total_sum_value\";s:0:\"\";s:15:\"total_count_min\";s:0:\"\";s:15:\"total_count_max\";s:0:\"\";s:14:\"total_count_op\";s:3:\"lte\";s:17:\"total_count_value\";s:0:\"\";s:14:\"campaign_id_op\";s:2:\"in\";s:17:\"campaign_id_value\";a:0:{}s:22:\"contribution_source_op\";s:2:\"in\";s:25:\"contribution_source_value\";a:0:{}s:24:\"payment_instrument_id_op\";s:2:\"in\";s:27:\"payment_instrument_id_value\";a:0:{}s:20:\"financial_account_op\";s:3:\"has\";s:23:\"financial_account_value\";s:0:\"\";s:13:\"is_deleted_op\";s:2:\"eq\";s:16:\"is_deleted_value\";s:1:\"0\";s:15:\"card_type_id_op\";s:2:\"in\";s:18:\"card_type_id_value\";a:0:{}s:11:\"batch_id_op\";s:2:\"in\";s:14:\"batch_id_value\";a:0:{}s:8:\"tagid_op\";s:2:\"in\";s:11:\"tagid_value\";a:0:{}s:6:\"gid_op\";s:2:\"in\";s:9:\"gid_value\";a:0:{}s:12:\"custom_24_op\";s:2:\"eq\";s:15:\"custom_24_value\";s:0:\"\";s:12:\"custom_36_op\";s:3:\"has\";s:15:\"custom_36_value\";s:0:\"\";s:12:\"custom_37_op\";s:3:\"has\";s:15:\"custom_37_value\";s:0:\"\";s:13:\"custom_38_min\";s:0:\"\";s:13:\"custom_38_max\";s:0:\"\";s:12:\"custom_38_op\";s:3:\"lte\";s:15:\"custom_38_value\";s:0:\"\";s:9:\"group_bys\";a:1:{s:12:\"receive_date\";s:1:\"1\";}s:14:\"group_bys_freq\";a:1:{s:12:\"receive_date\";s:5:\"MONTH\";}s:9:\"order_bys\";a:1:{i:1;a:2:{s:6:\"column\";s:20:\"contribution_page_id\";s:5:\"order\";s:3:\"ASC\";}}s:11:\"description\";s:0:\"\";s:13:\"email_subject\";s:0:\"\";s:8:\"email_to\";s:0:\"\";s:8:\"email_cc\";s:0:\"\";s:9:\"row_count\";s:1:\"5\";s:9:\"view_mode\";s:4:\"view\";s:14:\"addToDashboard\";s:1:\"1\";s:13:\"cache_minutes\";s:2:\"60\";s:11:\"is_reserved\";s:1:\"1\";s:10:\"permission\";s:17:\"access CiviReport\";s:9:\"parent_id\";s:0:\"\";s:8:\"radio_ts\";s:0:\"\";s:6:\"groups\";s:0:\"\";s:6:\"charts\";s:8:\"pieChart\";s:11:\"instance_id\";s:2:\"79\";}",
      ]);
      $dashlet = civicrm_api3('Dashboard', 'get', [
        'sequential' => 1,
        'label' => "Fiscal Year to Date",
      ]);
      if(empty($dashlet['values'])) {
        civicrm_api3('Dashboard', 'create', [
          'domain_id' => 1,
          'name' => "report/".$report_id['id'],
          'label' => "Fiscal Year to Date",
          'url' => "civicrm/report/instance/".$report_id['id']."?reset=1&section=1&charts=pieChart&context=dashlet&rowCount=5",
          'fullscreen_url' => "civicrm/report/instance/".$report_id['id']."?reset=1&section=1&charts=pieChart&context=dashletFullscreen&rowCount=5",
          'is_active' => 1,
          'is_reserved' => 1,
          'cache_minutes' => 60,
          'permission' => "access CiviReport",
        ]);
      }
    }

    $report = civicrm_api3('ReportInstance', 'get', ['name' => 'Last Year inc Today']);
    if(empty($report['values'])) {
      $report_id = civicrm_api3('ReportInstance', 'create', [
        'version' => 3,
        'report_id' => 'contribute/summary',
        'title' => ts('Last Year inc. Today'),
        'name' => 'Last Year inc. Today',
        "description" => "Total amounts raised last calendar year by quarters",
        'permission' => 'access CiviReport',
        'is_active' => 1,
        'is_reserved' => 1,
        "form_values" => "a:76:{s:8:\"entryURL\";s:73:\"https://success-dms.canadahelps.org/dms/report/contribute/summary?reset=1\";s:6:\"fields\";a:1:{s:12:\"total_amount\";s:1:\"1\";}s:21:\"receive_date_relative\";s:11:\"ending.year\";s:17:\"receive_date_from\";s:0:\"\";s:15:\"receive_date_to\";s:0:\"\";s:21:\"receipt_date_relative\";s:0:\"\";s:17:\"receipt_date_from\";s:0:\"\";s:15:\"receipt_date_to\";s:0:\"\";s:25:\"contribution_status_id_op\";s:2:\"in\";s:28:\"contribution_status_id_value\";a:1:{i:0;s:1:\"1\";}s:23:\"contribution_page_id_op\";s:2:\"in\";s:26:\"contribution_page_id_value\";a:0:{}s:20:\"financial_type_id_op\";s:2:\"in\";s:23:\"financial_type_id_value\";a:0:{}s:16:\"total_amount_min\";s:0:\"\";s:16:\"total_amount_max\";s:0:\"\";s:15:\"total_amount_op\";s:3:\"lte\";s:18:\"total_amount_value\";s:0:\"\";s:25:\"non_deductible_amount_min\";s:0:\"\";s:25:\"non_deductible_amount_max\";s:0:\"\";s:24:\"non_deductible_amount_op\";s:3:\"lte\";s:27:\"non_deductible_amount_value\";s:0:\"\";s:13:\"total_sum_min\";s:0:\"\";s:13:\"total_sum_max\";s:0:\"\";s:12:\"total_sum_op\";s:3:\"lte\";s:15:\"total_sum_value\";s:0:\"\";s:15:\"total_count_min\";s:0:\"\";s:15:\"total_count_max\";s:0:\"\";s:14:\"total_count_op\";s:3:\"lte\";s:17:\"total_count_value\";s:0:\"\";s:14:\"campaign_id_op\";s:2:\"in\";s:17:\"campaign_id_value\";a:0:{}s:22:\"contribution_source_op\";s:2:\"in\";s:25:\"contribution_source_value\";a:0:{}s:24:\"payment_instrument_id_op\";s:2:\"in\";s:27:\"payment_instrument_id_value\";a:0:{}s:20:\"financial_account_op\";s:3:\"has\";s:23:\"financial_account_value\";s:0:\"\";s:13:\"is_deleted_op\";s:2:\"eq\";s:16:\"is_deleted_value\";s:1:\"0\";s:15:\"card_type_id_op\";s:2:\"in\";s:18:\"card_type_id_value\";a:0:{}s:11:\"batch_id_op\";s:2:\"in\";s:14:\"batch_id_value\";a:0:{}s:8:\"tagid_op\";s:2:\"in\";s:11:\"tagid_value\";a:0:{}s:6:\"gid_op\";s:2:\"in\";s:9:\"gid_value\";a:0:{}s:12:\"custom_24_op\";s:2:\"eq\";s:15:\"custom_24_value\";s:0:\"\";s:12:\"custom_36_op\";s:3:\"has\";s:15:\"custom_36_value\";s:0:\"\";s:12:\"custom_37_op\";s:3:\"has\";s:15:\"custom_37_value\";s:0:\"\";s:13:\"custom_38_min\";s:0:\"\";s:13:\"custom_38_max\";s:0:\"\";s:12:\"custom_38_op\";s:3:\"lte\";s:15:\"custom_38_value\";s:0:\"\";s:9:\"group_bys\";a:1:{s:12:\"receive_date\";s:1:\"1\";}s:14:\"group_bys_freq\";a:1:{s:12:\"receive_date\";s:7:\"QUARTER\";}s:9:\"order_bys\";a:1:{i:1;a:2:{s:6:\"column\";s:20:\"contribution_page_id\";s:5:\"order\";s:3:\"ASC\";}}s:11:\"description\";s:0:\"\";s:13:\"email_subject\";s:0:\"\";s:8:\"email_to\";s:0:\"\";s:8:\"email_cc\";s:0:\"\";s:9:\"row_count\";s:1:\"5\";s:9:\"view_mode\";s:4:\"view\";s:14:\"addToDashboard\";s:1:\"1\";s:13:\"cache_minutes\";s:2:\"60\";s:11:\"is_reserved\";s:1:\"1\";s:10:\"permission\";s:17:\"access CiviReport\";s:9:\"parent_id\";s:0:\"\";s:8:\"radio_ts\";s:0:\"\";s:6:\"groups\";s:0:\"\";s:6:\"charts\";s:8:\"barChart\";s:11:\"instance_id\";s:2:\"83\";}",
      ]);
      $dashlet = civicrm_api3('Dashboard', 'get', [
        'sequential' => 1,
        'label' => "Last Year inc. Today",
      ]);
      if(empty($dashlet['values'])) {
        civicrm_api3('Dashboard', 'create', [
          'domain_id' => 1,
          'name' => "report/".$report_id['id'],
          'label' => "Last Year inc. Today",
          'url' => "civicrm/report/instance/".$report_id['id']."?reset=1&section=1&charts=barChart&context=dashlet&rowCount=5",
          'fullscreen_url' => "civicrm/report/instance/".$report_id['id']."?reset=1&section=1&charts=barChart&context=dashletFullscreen&rowCount=5",
          'is_active' => 1,
          'is_reserved' => 1,
          'cache_minutes' => 60,
          'permission' => "access CiviReport",
        ]);
      }
    }
    return TRUE;
  }

  public function upgrade_1900() {
    $this->ctx->log->info('Applying update 1900: CRM-896: Rearrange Dashboard');
    $users = civicrm_api3('UFMatch', 'get', [
      'sequential' => 1,
      'return' => ["contact_id"],
    ]);
    if($users['values']) {
      // Get all dashboards
      $dashboards = civicrm_api3('Dashboard', 'get', [
        'sequential' => 1,
      ]);
      // Replace key index value
      $dashboardOrder = [];
      foreach($dashboards['values'] as $dashboard) {
        $dashboardOrder[$dashboard['name']] = $dashboard;
      }
      //Get Dashlet Report Instances
      $reportInstances = civicrm_api3('ReportInstance', 'get', [
        'sequential' => 1,
        'return' => ["id", "title", "name"],
        'name' => ['IN' => ["Latest Contributions (Dashlet)", "New Email Replies", "Fiscal Year to Date", "Last Year inc. Today"]],
        'options' => ['limit' => 0],
      ]);
      if($reportInstances['values']) {
        $reportInstanceDashlet = [];
        foreach($reportInstances['values'] as $report) {
          $report['id'] = 'report/'.$report['id'];
          $reportInstanceDashlet[$report['name']] = $report;
        }
        $reportInstanceDashlet['activity'] = ['id' => 'activity', 'title' => 'Activities', 'name' => 'activity'];
      }
      // Arrange Reserved Dashboards in Particular order
      $dashletNewOrder = [
        [
          'dashboard_id' => $dashboardOrder[$reportInstanceDashlet['Fiscal Year to Date']['id']]['id'],
          'column_no' => 0,
          'is_active' => 1,
          'weight' => 0,
        ],
        [
          'dashboard_id' => $dashboardOrder[$reportInstanceDashlet['Last Year inc. Today']['id']]['id'],
          'column_no' => 0,
          'is_active' => 1,
          'weight' => 1,
        ],
        [
          'dashboard_id' => $dashboardOrder[$reportInstanceDashlet['New Email Replies']['id']]['id'],
          'column_no' => 1,
          'is_active' => 1,
          'weight' => 0,
        ],
        [
          'dashboard_id' => $dashboardOrder[$reportInstanceDashlet['Latest Contributions (Dashlet)']['id']]['id'],
          'column_no' => 1,
          'is_active' => 1,
          'weight' => 1,
        ],
        [
          'dashboard_id' => $dashboardOrder[$reportInstanceDashlet['activity']['id']]['id'],
          'column_no' => 1,
          'is_active' => 1,
          'weight' => 2,
        ],
      ];
      foreach($users['values'] as $user) {
        // Delete all exisiting arrangement
        $existingDashlets = civicrm_api3('DashboardContact', 'get', [
          'sequential' => 1,
          'return' => ["id"],
          'contact_id' => $user['contact_id'],
        ]);
        if($existingDashlets['values']) {
          foreach($existingDashlets['values'] as $exisitngDashlet) {
            civicrm_api3('DashboardContact', 'delete', [
              'id' => $exisitngDashlet['id'],
            ]);
          }
        }
        // Set new order for Contacts
        foreach($dashletNewOrder as $newOrder) {
          $newOrder['contact_id'] = $user['contact_id'];
          $result = civicrm_api3('DashboardContact', 'create', $newOrder);
        }
      }
    }
    
    return TRUE;
  }

  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(E::ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(E::ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(E::ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = E::ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */

}
