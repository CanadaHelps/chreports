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
  if ($object instanceof CRM_Report_Form_Contribute_Detail) {
    if ($varType == 'columns') {
      $var['civicrm_contribution']['group_bys']['contribution_page_id'] = ['title' => ts('Contribution Page')];
      $var['civicrm_contribution']['order_bys']['contribution_page_id'] = ['title' => ts('Contribution Page'), 'dbAlias' => 'cp.title'];

      $var['civicrm_contribution']['fields']['campaign_id'] = ['title' => ts('Campaign')];
      $var['civicrm_contribution']['group_bys']['campaign_id'] = ['title' => ts('Campaign')];
      $var['civicrm_contribution']['order_bys']['campaign_name'] = ['title' => ts('Campaign'), 'dbAlias' => 'campaign.title'];
    }
    if ($varType == 'sql') {
      $from = $var->getVar('_from');
      $from .= "
      LEFT JOIN civicrm_contribution_page cp ON cp.id = contribution_civireport.contribution_page_id
      LEFT JOIN civicrm_campaign campaign ON campaign.id = contribution_civireport.campaign_id

       ";
      $var->setVar('_from', $from);
    }
  }
  elseif ($object instanceof CRM_Report_Form_Contribute_Summary) {
    if ($varType == 'columns') {
      $var['civicrm_contact']['fields']['financial_account'] = ['title' => ts('Financial Account'), 'dbAlias' => 'fa.name'];
      $var['civicrm_contact']['group_bys']['financial_account'] = ['title' => ts('Financial Account'), 'dbAlias' => 'fa.id'];
      $var['civicrm_contribution']['group_bys']['campaign_id'] = ['title' => ts('Campaign')];
      $var['civicrm_contribution']['fields']['campaign_id'] = ['title' => ts('Campaign')];
    }
    if ($varType == 'sql') {
      $from = $var->getVar('_from');
      $from .= "
      LEFT JOIN civicrm_line_item li ON li.contribution_id = contribution_civireport.id
      LEFT JOIN civicrm_financial_item fi ON fi.entity_id = li.id AND fi.entity_table = 'civicrm_line_item'
      LEFT JOIN civicrm_financial_account fa ON fa.id = fi.financial_account_id
       ";
       $var->setVar('_from', $from);
    }
    if ($varType == 'rows') {
      foreach (['civicrm_financial_type_financial_type', 'civicrm_contribution_campaign_id', 'civicrm_contribution_contribution_page_id'] as $column) {
        if (!empty($var[0]) && array_key_exists($column, $var[0])) {
          $missingTypes = [];
          if ($column == 'civicrm_financial_type_financial_type') {
            $entityTypes = CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes();
          }
          elseif ($column == 'civicrm_contribution_campaign_id') {
            $entityTypes = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns(NULL, NULL, FALSE, FALSE, TRUE)['campaigns'];
          }
          elseif ($column == 'civicrm_contribution_contribution_page_id') {
            $entityTypes = CRM_Contribute_PseudoConstant::contributionPage();
            foreach ($var as $rowNum => $row) {
              $var[$rowNum]['civicrm_contribution_contribution_page_id'] = CRM_Utils_Array::value($row['civicrm_contribution_contribution_page_id'], $entityTypes);
            }
          }
          $missingTypes = array_diff($entityTypes,
            array_flip(array_flip(array_filter(CRM_Utils_Array::collect($column, $var))))
          );
          $keys = array_keys($var[0]);
          foreach ($missingTypes as $missingType) {
            $row = [];
            foreach ($keys as $key) {
              $row[$key] = NULL;
              if (in_array($key, ['civicrm_contribution_total_amount_count', 'civicrm_contribution_total_amount_sum', 'civicrm_contribution_total_amount_avg'])) {
                $row[$key] = 0.00;
              }
              $row[$column] = $missingType;
              $row['civicrm_contribution_currency'] = $var[0]['civicrm_contribution_currency'];
            }
            $var[] = $row;
          }
        }
      }
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
