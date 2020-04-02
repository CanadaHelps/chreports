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
  if ($object instanceof CRM_Report_Form_Contribute_Lybunt) {
    $object->setVar('_charts', []);
  }
  if ($object instanceof CRM_Report_Form_Contribute_Detail) {
    $tablename = E::getTableNameByName('Contribution_Details');
    $cpTableName = E::getTableNameByName('Campaign_Information');
    if ($varType == 'columns') {
      $var['civicrm_contribution']['group_bys']['contribution_page_id'] = ['title' => ts('Contribution Page')];
      $var['civicrm_contribution']['order_bys']['contribution_page_id'] = ['title' => ts('Contribution Page'), 'dbAlias' => 'cp.title'];
      $var['civicrm_contribution']['order_bys']['source'] = ['title' => ts('Source')];
      $var['civicrm_contact']['fields']['exposed_id']['title'] = ts('Donor ID');

      $var['civicrm_contribution']['fields']['campaign_id'] = ['title' => ts('Campaign')];
      $var['civicrm_contribution']['order_bys']['campaign_id'] = ['title' => ts('Campaign'), 'dbAlias' => 'campaign.title'];

      if ($tableName) {
        if ($columnName = E::getColumnNameByName('Is_Receipted_')) {
          $var[$tableName]['fields']['receipt_type'] = [
            'title' => ts('Receipt Type'),
            'type' => CRM_Utils_Type::T_STRING,
            'dbAlias' => "
              CASE
                WHEN {$tablename}.{$columnName} = 1 AND contribution_civireport.source LIKE \'%CanadaHelps%\' THEN \'CanadaHelps\'
                WHEN {$tablename}.{$columnName} = 1 AND contribution_civireport.source NOT LIKE \'%CanadaHelps%\'  THEN \'Charity Issued\'
                ELSE NULL
              END
            ",
          ];
        }
      }
      if ($cpTableName) {
        $columnName = E::getColumnNameByName('Campaign_Type');
        $var['civicrm_contribution']['fields']['campaign_type'] = [
          'title' => ts('Contribution Page Type'),
          'type' => CRM_Utils_Type::T_STRING,
          'dbAlias' => "(SELECT $columnName FROM $cpTableName WHERE entity_id = contribution_civireport.contribution_page_id)",
        ];
        $var['civicrm_contribution']['filters']['campaign_type'] = [
          'title' => ts('Contribution Page Type'),
          'type' => CRM_Utils_Type::T_STRING,
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => CRM_Core_OptionGroup::values(E::getOptionGroupNameByColumnName($columnName)),
          'pseudofield' => TRUE,
          'dbAlias' => "(1)",
        ];
      }
    }
    if ($varType == 'sql') {
      $from = $var->getVar('_from');
      $from .= "
      LEFT JOIN civicrm_contribution_page cp ON cp.id = contribution_civireport.contribution_page_id
      LEFT JOIN civicrm_campaign campaign ON campaign.id = contribution_civireport.campaign_id
       ";
     if (!empty($cpTableName)) {
       $filter = '';
       $join = 'LEFT';
       $params = $var->getVar('_params');
       if (!empty($params['campaign_type_value']) || in_array($params['campaign_type_op'], ['nll', 'nnll'])) {
         $join = 'INNER';
         $field = [
           'dbAlias' => 'ct.' . E::getColumnNameByName('Campaign_Type'),
           'name' => 'campaign_type',
         ];
         $filter = "AND " . $var->whereClause($field, $params['campaign_type_op'], $params['campaign_type_value'], NULL, NULL);
       }

       $from .= "
       $join JOIN $cpTableName ct ON ct.entity_id = contribution_civireport.contribution_page_id $filter
       ";
     }
     $var->setVar('_from', $from);
    }
    if ($varType == 'rows') {
      $key = $tableName . 'custom_' . CRM_Utils_Array::value('id', civicrm_api3('CustomField', 'get', ['sequential' => 1, 'name' => 'Receipt_Number'])['values'][0], '');
      if (!empty($object->_columnHeaders[$key])) {
        $column = [$key => $object->_columnHeaders[$key]];
        $object->_columnHeaders = $column + $object->_columnHeaders;
      }

      // reorder the columns
      $columnHeaders = [];
      foreach ([
        'civicrm_contribution_campaign_id',
        'civicrm_contact_exposed_id',
        'civicrm_contact_sort_name',
        'civicrm_contribution_receive_date',
        'civicrm_contribution_total_amount',
        'civicrm_contribution_financial_type_id',
        'civicrm_contribution_contribution_page_id',
        'civicrm_contribution_campaign_type',
        'civicrm_contribution_source',
        'civicrm_contribution_payment_instrument_id',
      ] as $name) {
        if (array_key_exists($name, $object->_columnHeaders)) {
          $columnHeaders[$name] = $object->_columnHeaders[$name];
          unset($object->_columnHeaders[$name]);
        }
      }
      $object->_columnHeaders = array_merge($object->_columnHeaders, $columnHeaders);

      if (!empty($object->_columnHeaders['civicrm_contribution_campaign_type'])) {
        $optionValues = CRM_Core_OptionGroup::values(E::getOptionGroupNameByColumnName(E::getColumnNameByName('Campaign_Type')));
        foreach ($var as $rowNum => $row) {
          $var[$rowNum]['civicrm_contribution_campaign_type'] = CRM_Utils_Array::value($row['civicrm_contribution_campaign_type'], $optionValues);
        }
      }
    }
  }
  elseif ($object instanceof CRM_Report_Form_Contribute_Summary || $object instanceof CRM_Chreports_Form_Report_ExtendSummary) {
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
      if (!empty($tablename)) {
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
      $var['civicrm_contribution']['fields']['campaign_id'] = ['title' => ts('Campaign')];
      $var['civicrm_contribution']['group_bys']['payment_instrument_id'] = ['title' => ts('Payment Method')];
      $var['civicrm_contribution']['fields']['contribution_page_id']['type'] = CRM_Utils_Type::T_STRING;
      $object->campaigns = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns(NULL, NULL, FALSE, FALSE)['campaigns'];
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

      $grandTotalKey = count($var) - 1;
      // if financial account is chosen in column then don't show contribution avg.
      if (!empty($object->_columnHeaders['civicrm_contact_financial_account'])) {
        unset($object->_columnHeaders['civicrm_contribution_total_amount_avg']);
      }
      if (!empty($object->_columnHeaders['civicrm_contribution_payment_instrument_id'])) {
        $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
        foreach ($var as $rowNum => $row) {
          $var[$rowNum]['civicrm_contribution_payment_instrument_id'] = CRM_Utils_Array::value($row['civicrm_contribution_payment_instrument_id'], $paymentInstruments);
        }
      }

      foreach (['civicrm_financial_type_financial_type', 'civicrm_contribution_campaign_id', 'civicrm_contribution_contribution_page_id'] as $column) {
        $params = $object->getVar('_params');
        if (!empty($var[0]) && array_key_exists($column, $var[0])) {
          $missingTypes = [];
          $entity = NULL;
          if ($column == 'civicrm_financial_type_financial_type') {
            $entityTypes = CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes();
            $entity = 'financial_type_id';
          }
          elseif ($column == 'civicrm_contribution_campaign_id') {
            $entityTypes = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns(NULL, NULL, FALSE, FALSE)['campaigns'];
            $entity = 'campaign_id';
          }
          elseif ($column == 'civicrm_contribution_contribution_page_id') {
            $entityTypes = array_keys(CRM_Contribute_PseudoConstant::contributionPage());
            $entity = 'contribution_page_id';
          }

          $allTypes = array_flip(array_flip(array_filter(CRM_Utils_Array::collect($column, $var))));

          if (!empty($params["{$entity}_value"]) && !in_array($params["{$entity}_op"] , ['nll', 'nnll'])) {
            foreach ($entityTypes as $id => $dontCare) {
              if ($params["{$entity}_op"] == 'in') {
                if (!in_array($id, $params["{$entity}_value"])) {
                  unset($entityTypes[$id]);
                }
              }
              elseif ($params["{$entity}_op"] == 'notin') {
                if (in_array($id, $params["{$entity}_value"])) {
                  unset($entityTypes[$id]);
                }
              }
            }
          }
          elseif (CRM_Utils_Array::value("{$entity}_op", $params) == 'nnll') {
            $entityTypes = [];
          }

          $missingTypes = array_diff($entityTypes, $allTypes);
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

          if ($column == 'civicrm_contribution_contribution_page_id') {
            $contributionPages = CRM_Contribute_PseudoConstant::contributionPage();
            foreach ($var as $rowNum => $row) {
              $var[$rowNum]['civicrm_contribution_contribution_page_id'] = CRM_Utils_Array::value($row['civicrm_contribution_contribution_page_id'], $contributionPages, $row['civicrm_contribution_contribution_page_id']);
            }
          }
        }
        if (array_key_exists($grandTotalKey, $var)) {
          $var['grandtotal'] = $var[$grandTotalKey];
          unset($var[$grandTotalKey]);
        }

        if (end($var) != 'grandtotal') {
          $lastArray = $var['grandtotal'];
          unset($var['grandtotal']);
          if (!empty($var)) {
            $var['grandtotal'] = $lastArray;
          }
        }
      }
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
