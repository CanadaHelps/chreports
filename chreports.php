<?php

require_once 'chreports.civix.php';
use CRM_Chreports_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function chreports_civicrm_config(&$config) {
  _chreports_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function chreports_civicrm_xmlMenu(&$files) {
  _chreports_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function chreports_civicrm_install() {
  _chreports_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function chreports_civicrm_postInstall() {
  _chreports_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function chreports_civicrm_uninstall() {
  _chreports_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function chreports_civicrm_enable() {
  _chreports_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function chreports_civicrm_disable() {
  _chreports_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function chreports_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _chreports_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function chreports_civicrm_managed(&$entities) {
  _chreports_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function chreports_civicrm_caseTypes(&$caseTypes) {
  _chreports_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function chreports_civicrm_angularModules(&$angularModules) {
  _chreports_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function chreports_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _chreports_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function chreports_civicrm_entityTypes(&$entityTypes) {
  _chreports_civix_civicrm_entityTypes($entityTypes);
}

function chreports_civicrm_alterReportVar($varType, &$var, &$object) {
  if (($object instanceof CRM_Chreports_Form_Report_ExtendedDetail ||
    $object instanceof CRM_Chreports_Form_Report_ExtendSummary ||
    $object instanceof CRM_Chreports_Form_Report_GLSummaryReport
    ) && $varType == 'columns') {
    $fieldsToHide = [
      'civicrm_contact' => [
        'sort_name',
        'nick_name',
        'display_name',
        'external_identifier',
        'preferred_language',
        'preferred_communication_method',
        'postal_greeting_display',
        'email_greeting_display',
        'addressee_display',
        'do_not_email',
        'do_not_phone',
        'do_not_mail',
        'do_not_sms',
        'is_opt_out',
        'first_name',
        'last_name',
        'middle_name',
        'prefix_id',
        'suffix_id',
        'gender_id',
        'birth_date',
        'age',
        'job_title',
        'employer_id',
      ],
      'civicrm_phone' => [
        'phone',
      ],
      'civicrm_email' => [
        'email',
      ],
      'civicrm_contribution' => [
        'contribution_status_id',
        'thankyou_date',
        'contribution_or_soft',
        'soft_credits',
        'soft_credit_for',
      ],
      'civicrm_contribution_soft' => [
        'all',
      ],
      'civicrm_value_contribution__15' => [
        'custom_37',
      ],
      'civicrm_value_contribution__19' => [
        'custom_35',
      ],
      'civicrm_value_mailchimp_details' => ['delete'],
      'civicrm_value_summary_field_7' => ['delete'],
    ];
    if ($object instanceof CRM_Chreports_Form_Report_ExtendSummary) {
      $fieldsToHide['civicrm_contact'] = array_merge($fieldsToHide['civicrm_contact'], ['contact_type', 'contact_sub_type']);
      $fieldsToHide['civicrm_batch'] = ['batch_id'];
      $fieldsToHide['civicrm_value_contribution__15'] = ['delete'];
      $fieldsToHide['civicrm_value_contribution__19'] = ['delete'];
    }
    if ($object instanceof CRM_Chreports_Form_Report_GLSummaryReport) {
      $fieldsToHide = [
        'civicrm_contribution' => [
          'sort_name',
          'receive_date',
          'credit_card_type_id',
          'trxn_date',
          'id',
        ],
      ];
    }
    foreach ($fieldsToHide as $table => $fields) {
      foreach ($fields as $field) {
        if ($field == 'delete') {
          unset($var[$table]);
        }
        elseif ($field == 'all') {
          foreach (array_keys($var[$table]['fields']) as $name) {
            unset($var[$table]['fields'][$name]);
            unset($var[$table]['metadata'][$name]);
          }
        }
        elseif (!empty($var[$table]['metadata'][$field]) || !empty($var[$table]['fields'][$field])) {
          unset($var[$table]['metadata'][$field]);
          unset($var[$table]['fields'][$field]);
        }
      }
    }
  }

  if ($object instanceof CRM_Report_Form_Contribute_Lybunt) {
    $object->setVar('_charts', []);
  }
  if ($object instanceof CRM_Report_Form_Contribute_Summary || $object instanceof CRM_Chreports_Form_Report_ExtendSummary) {
    $tablename = E::getTableNameByName('Campaign_Information');
    if ($varType == 'columns') {
      if ($object instanceof CRM_Chreports_Form_Report_ExtendSummary) {
        unset($var['civicrm_contribution']['fields']['total_amount']['statistics']['avg']);
        // Add GL Account columns, groupBy and filter to only Extended Contribution Summary Report template
        $var['civicrm_contact']['fields']['financial_account'] = ['title' => ts('Financial Account'), 'dbAlias' => 'fa.name'];
        $var['civicrm_contact']['group_bys']['financial_account'] = ['title' => ts('Financial Account'), 'dbAlias' => 'fa.name'];
        $var['civicrm_contact']['filters']['financial_account'] = [
          'title' => ts('GL Account'),
          'type' => CRM_Utils_Type::T_STRING,
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => CRM_Contribute_PseudoConstant::financialAccount(),
          'dbAlias' => 'fa.id',
        ];
      }
      $var['civicrm_contribution']['fields']['total_amount']['statistics'] =  ['count' => ts('Number of Contributions'), 'sum' => ts('Total Amount')];
      $var['civicrm_contribution']['fields']['payment_instrument_id'] = ['title' => 'Payment Method'];

      $var['civicrm_contribution']['filters']['payment_instrument_id'] = [
        'title' => ts('Payment Method'),
        'type' => CRM_Utils_Type::T_INT,
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => CRM_Core_OptionGroup::values('payment_instrument'),
      ];
      if (!empty($tablename) && ($object instanceof CRM_Chreports_Form_Report_ExtendSummary)) {
        if ($columnName = E::getColumnNameByName('Campaign_Type')) {
          $optionGroupName = E::getOptionGroupNameByColumnName($columnName);
          $var['civicrm_contribution']['filters']['campaign_type'] = [
            'title' => ts('Contribution Page Type'),
            'type' => CRM_Utils_Type::T_STRING,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values($optionGroupName),
            'dbAlias' => "ct.{$columnName}",
          ];
        }
      }
      $var['civicrm_contribution']['group_bys']['campaign_id'] = ['title' => ts('Campaign')];
      $var['civicrm_contribution']['order_bys']['campaign_id'] = ['title' => ts('Campaign'), 'dbAlias' => 'campaign.title'];
      $var['civicrm_contribution']['fields']['campaign_id'] = ['title' => ts('Campaign')];
      $var['civicrm_contribution']['group_bys']['payment_instrument_id'] = ['title' => ts('Payment Method')];
      $var['civicrm_contribution']['fields']['contribution_page_id']['type'] = CRM_Utils_Type::T_STRING;
      $object->campaigns = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns(NULL, NULL, FALSE, FALSE)['campaigns'];
      $var['civicrm_contribution']['filters']['contribution_page_id']['options'] = CRM_Contribute_PseudoConstant::contributionPage(NULL, TRUE);
      $var['civicrm_contribution']['order_bys']['contribution_page_id'] = ['title' => ts('Contribution Page')];
    }
    if ($varType == 'sql' && !($object instanceof CRM_Chreports_Form_Report_ExtendSummary)) {
      $from = $var->getVar('_from');
      $tablename = E::getTableNameByName('Campaign_Information');
      if (!empty($tableName)) {
        $from .= "
        LEFT JOIN $tableName ct ON ct.entity_id = contribution_civireport.contribution_page_id
        ";
      }
      $var->setVar('_from', $from);
    }
    if ($varType == 'rows') {
      foreach (['civicrm_contribution_contribution_page_id', 'civicrm_contribution_campaign_id', 'civicrm_financial_type_financial_type'] as $column) {
        if (!empty($var[0]) && array_key_exists($column, $var[0])) {
            foreach ($var as $rowNum => $row) {
            if (empty($var[$rowNum]['civicrm_contribution_currency'])) {
              $var[$rowNum]['civicrm_contribution_total_amount_count'] = 0;
            }
          }
        }
      }
      // reorder column headers for summary report
      $columnHeaders = [];
      foreach ([
        'civicrm_contribution_campaign_id',
        'civicrm_contribution_financial_type_id',
        'civicrm_contribution_campaign_type',
        'civicrm_contribution_source',
        'civicrm_contribution_payment_instrument_id',
      ] as $name) {
        if (array_key_exists($name, $object->_columnHeaders)) {
          $columnHeaders[$name] = $object->_columnHeaders[$name];
          unset($object->_columnHeaders[$name]);
        }
      }
      $object->_columnHeaders = array_merge($columnHeaders, $object->_columnHeaders);
    }
  }
  elseif ($object instanceof CRM_Report_Form_Contribute_Bookkeeping) {
    if ($varType == 'columns') {
      $var['civicrm_financial_account']['order_bys']['credit_name'] = [
        'title' => ts('Financial Account Name - Credit'),
        'name' => 'name',
        'alias' => 'financial_account_civireport_credit',
        'dbAlias' => 'civicrm_financial_account_credit_name',
      ];
    }
  }
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function chreports_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function chreports_civicrm_navigationMenu(&$menu) {
  _chreports_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _chreports_civix_navigationMenu($menu);
} // */
