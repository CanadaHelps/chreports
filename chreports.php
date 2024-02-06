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

function chreports_civicrm_buildForm($formName, &$form) {
  $isRefactored = $formName == "CRM_Chreports_Form_Report_ExtendSummary" || $formName == "CRM_Chreports_Form_Report_ExtendedDetail";
  
  if ($isRefactored || in_array($formName, [
    'CRM_Report_Form_Contact_Summary'
  ])) {
    //CRM-799 To solve the issue of report columns being cut-off, limit the "Print report" function 
    //to only be available when the selected report has 10 columns or less. If more than 10 columns are selected, hide "Print report" function
    // @TODO this doesn't work for all scenarios, and is more a temp hack rather than a fix
    $columnFields = isset($form->getVar('_submitValues')['fields']) ? $form->getVar('_submitValues')['fields'] : 
      (isset($form->getVar('_params')['fields']) ? $form->getVar('_params')['fields'] : []);
    $selectedColumnFields = count($columnFields);
    if(isset($selectedColumnFields) && $selectedColumnFields > 10) {
      if (array_key_exists('task', $form->_elementIndex)) {
        //CRM-2199 Hiding "Print Report" option rather than unsetting, unsetting that option is impacting "Export as CSV" option as well
        CRM_Core_Resources::singleton()->addScript(
          "CRM.$(function($) {
            $('#task-section option').each(function() {
              var actionOptions = $(this).val();
              if(actionOptions === 'report_instance.print'){
                $(this).closest('option').remove();
              }
            });
          });"
        );
      }
    }
  }

  if ($isRefactored) {
    //default pre-select the column and group by
    if (array_key_exists('fields', $form->_elementIndex)) {
      $reportInstance = $form->getReportInstance();
      foreach( ['fields','group_bys'] as $entity) {
        $elementField = $form->getElement($entity)->_elements;
        $reportInstance->setPreSelectField($elementField);
      }
      //For monthly and yearly report only one column should be checked at a time
      if($reportInstance->isPeriodicSummary()){
        CRM_Core_Resources::singleton()->addScript(
          "CRM.$(function($) {
            $('.crm-report-criteria-field input:checkbox').on('change',function() {
              $('.crm-form-checkbox').not(this).prop('checked', false);
            });
          });"
        );
      }
      CRM_Core_Resources::singleton()->addScript(
        "CRM.$(function($) {
          $('#mainTabContainer').tabs('option', 'active', 0);
        });"
      );
    }

  }

  if ($formName == 'CRM_Chreports_Form_Report_ExtendSummary' || $formName == 'CRM_Report_Form_Contact_Summary') {
   
    CRM_Core_Resources::singleton()->addScript(
      "CRM.$(function($) {
        $( document ).ready(function() {
          $('#fields_total_amount').parent().hide();
          $('.crm-report-criteria-field input:checkbox').click(function() {
            $('#group_bys_' + this.id.substr(7)).prop('checked', this.checked);
          });
        });
      });");
  }

  if ($formName == 'CRM_Report_Form_Contact_Summary') {
    if (!empty($_GET['id_value'])) {
      $var['civicrm_contact']['filters']['id']['options'] = explode(',', $_GET['id_value']);
      $form->setVar('_formValues', array_merge($form->getVar('_formValues'), ['id_value' => explode(',', $_GET['id_value'])]));
      $form->setVar('_params', array_merge($form->getVar('_params'), ['id_value' => explode(',', $_GET['id_value'])]));
      $form->setDefaults(['id_value' => explode(',', $_GET['id_value'])]);
    }
  }
}

function chreports_civicrm_alterReportVar($varType, &$var, &$object) {
  $isRefactored = $object instanceof CRM_Chreports_Form_Report_ExtendSummary || $object instanceof CRM_Chreports_Form_Report_ExtendedDetail;

  if ($isRefactored) {
   
      $reportInstance = $object->getReportInstance();

      // Fix missing args
      if ($varType == 'outputhandlers') {
        $columns = $object->getVar('_columns');
        $customGroups = \Civi\Api4\CustomGroup::get()
          ->addSelect('name', 'extends', 'title', 'table_name')
          ->execute();
          
        foreach ($customGroups as $customGroup) {
          if ( isset($columns[$customGroup['table_name']]) ) {
            $columns[$customGroup['table_name']]['extends'] = '';//$customGroup['extends'];
            //$columns[$customGroup['table_name']]['group_title'] = $customGroup['title'];
          }
        }
       $object->setVar('_columns', $columns);
      }

      if ($varType == 'columns') {
        //manage columns, group bys, sorts, filters based on json config
        $reportInstance->setFormOptions($var);

        //make the default field selected for sort by option
        $defaults = $object->getVar('_defaults') ? $object->getVar('_defaults') : array();
        $defaults = $reportInstance->setDefaultOptionSortBy($defaults);
        $object->setVar('_defaults', $defaults);
       
        return;
      }

      if ($varType == 'sql') {
        //For empty fields or in case when fields are not default to get proper filters value need to reassign params and formvalues.
        if(empty($var->getVar('_params')['fields'])) {
          $intermediateParamsValue = $object->controller->exportValues($var->getVar('_name'));
          $var->setVar('_params', $intermediateParamsValue);
          $var->setVar('_formValues', $intermediateParamsValue);
        }
        //build main sql query to display result
        $object->buildSQLQuery($var);
        return;
      }

      if ($varType == 'rows') {

         // remove unwanted columns from display
         $reportInstance->alterColumnHeadersForDisplay($var,$object->_columnHeaders);
        //manage display of result
        $reportInstance->alterDisplayRows($var);
        return;
      }
  }

  // anything BELOW, we should exclude
  if ($object instanceof CRM_Report_Form_Contact_Summary && $varType == 'columns') {
    $var['civicrm_contact']['filters']['id'] = [
      'title' => 'Contact ID(s)',
      'type' => CRM_Utils_Type::T_STRING,
      'operatorType' => CRM_Report_Form::OP_MULTISELECT,
      'options' => [CRM_Core_Session::getLoggedInContactID()],
    ];
  }
  if (($object instanceof CRM_Report_Form_Contact_Summary ||
    $object instanceof CRM_Report_Form_Activity ||
    $object instanceof CRM_Report_Form_Contact_Relationship
    ) && $varType == 'columns') {

    // show columns tab for 'Charity Admins' role
    $isCharityAdmin = FALSE;
    if (module_exists('user')) {
      global $user;
      if (in_array('client administrator', $user->roles)) {
        $isCharityAdmin = TRUE;
        CRM_Core_Resources::singleton()->addScript(
          "CRM.$(function($) {
            $('#ui-id-2').parent().show();
            $('#ui-id-2').show();
          });"
        );
      }
    }

    $fieldsToHide = [
      'civicrm_contact' => [
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
      'civicrm_financial_trxn' => [
        'card_type_id',
        'trxn_id',
      ],
      'civicrm_address' => [
        'address_name',
        'address_street_number',
        'address_street_name',
        'address_supplemental_address_3',
        'address_street_unit',
        'address_postal_code_suffix',
        'address_county_id',
        'address_location_type_id',
        'address_id',
        'address_is_primary',
      ],
      'civicrm_pledge_payment' => [
        'pledge_id',
      ],
      'civicrm_contribution' => [
        'contribution_status_id',
        'contribution_or_soft',
        'soft_credits',
        'soft_credit_for',
      ],
      'civicrm_note' => [
        'contribution_note',
      ],
      'civicrm_contribution_soft' => [
        'all',
      ],
      'civicrm_value_contribution__15' => [
        // 'custom_37',
        'custom_24',
      ],
      'civicrm_value_contribution__19' => [
        'custom_35',
      ],
      'civicrm_value_email_consent_5' => ['delete'],
      'civicrm_value_mailchimp_details' => ['delete'],
      'civicrm_value_summary_field_7' => ['delete'],
    ];
    if ($object instanceof CRM_Report_Form_Contact_Summary) {
      $fieldsToHide = [
        'civicrm_contact' => [
          'suffix_id',
          'addressee_display',
          'age',
        ],
        'civicrm_address' => [
          'address_name',
          'address_street_number',
          'address_street_name',
          'address_supplemental_address_3',
          'address_street_unit',
          'address_postal_code_suffix',
          'address_county_id',
          'address_location_type_id',
          'address_id',
          'address_is_primary',
        ],
        'civicrm_value_mailchimp_details' => ['delete'],
      ];
      if ($isCharityAdmin) {
        $fieldsToHide['civicrm_contact'][] = 'exposed_id';
      }
    }
    if ($object instanceof CRM_Report_Form_Activity) {
      $fieldsToHide = [
        'civicrm_activity' => [
          'result',
          'campaign_id',
          'engagement_level',
        ],
        'civicrm_address' => [
          'address_name',
          'supplemental_address_3',
          'street_number',
          'street_name',
          'street_unit',
          'postal_code_suffix',
          'county_id',
        ],
      ];
      $filtersToHide = [
        'civicrm_activity' => [
          'result',
          'campaign_id',
          'engagement_level'
        ],
        'civicrm_address' => [
          'county_id'
        ]
      ];
      unset($var['civicrm_address']['order_bys']['street_name']);
      unset($var['civicrm_address']['order_bys']['street_number']);
    }
    if($object instanceof CRM_Report_Form_Contact_Relationship) {
      $fieldsToHide = [
        'civicrm_relationship' => [
          'is_permission_a_b',
          'is_permission_b_a'
        ]
      ];
      $filtersToHide = [
        'civicrm_relationship' => [
          'is_permission_a_b',
          'is_permission_b_a'
        ]
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
        elseif (!empty($var[$table]['metadata'][$field]) || !empty($var[$table]['fields'][$field]) || array_key_exists($field, (array) $var[$table]['fields'])) {
          unset($var[$table]['metadata'][$field]);
          unset($var[$table]['fields'][$field]);
        }
      }
    }
    if (isset($filtersToHide)) {
      foreach ($filtersToHide as $table => $filters) {
        foreach ($filters as $filter) {
          if (array_key_exists($filter, $var[$table]['filters'])) {
            unset($var[$table]['filters'][$filter]);
          }
        }
      }
    }
  }
  if ($object instanceof CRM_Report_Form_Contact_Summary && $varType == 'rows') {
    foreach ($var as $rowNum => $row) {
      if (!empty($var[$rowNum]['civicrm_contact_sort_name'])) {
        $url = CRM_Utils_System::url('dms/contact/view', 'reset=1&cid=' . $row['civicrm_contact_id']);
        $var[$rowNum]['civicrm_contact_sort_name'] = sprintf('<a href="%s" target="_blank">%s</a>', $url, $var[$rowNum]['civicrm_contact_sort_name']);
      }
    }
  }
  elseif ($object instanceof CRM_Report_Form_Contact_Relationship && $varType == 'rows') {
    foreach ($var as $rowNum => $row) {
      if (!empty($var[$rowNum]['civicrm_contact_sort_name_a']) && !empty($var[$rowNum]['civicrm_contact_b_sort_name_b'])) {
        $var[$rowNum]['civicrm_contact_sort_name_a_link'] = CRM_Utils_System::url('dms/contact/view', 'reset=1&cid=' . $row['civicrm_contact_id']);
        $var[$rowNum]['civicrm_contact_b_sort_name_b_link'] = CRM_Utils_System::url('dms/contact/view', 'reset=1&cid=' . $row['civicrm_contact_b_id']);
        $var[$rowNum]['civicrm_relationship_relationship_id'] = sprintf('<a href="%s" target="_blank">%s</a>', $var[$rowNum]['civicrm_relationship_relationship_id_link'], $var[$rowNum]['civicrm_relationship_relationship_id']);
      }
    }
  }
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 */
function chreports_civicrm_preProcess($formName, &$form) {

  
  $isRefactored = $formName == "CRM_Chreports_Form_Report_ExtendSummary" || $formName == "CRM_Chreports_Form_Report_ExtendedDetail";
  
  if ($isRefactored) {
    //hide empty custom fields based filter sections on filter tab
    $reportInstance = $form->getReportInstance();
    $filters = $form->getVar('_filters');
    $filters = $reportInstance->unsetEmptyFilterEntity($filters);
    $form->setVar('_filters', $filters);

    // if there are any Preselect Filters in Json, prepopulare on form load
    if($reportInstance->getPreSetFilterValues()) {
      $filterParams = $reportInstance->createCustomFilterParams();
      foreach($filterParams as $filterKey => $filterValue) {
        $defaultSelectedFilter[$filterKey] = $filterValue;
      }
      //$defaults[$filterKey] = $filterValue;
      $form->setVar('_formValues', $defaultSelectedFilter);
    }

    //CRM-2097: For Save/Copy bypass the post Process
    $taskAction = [];
    if(isset($form->getVar('_submitValues')['task'])) {
      $taskAction = strtolower(str_replace('report_instance.', '', $form->getVar('_submitValues')['task']));
    }
    if(in_array($taskAction, ['save', 'copy'])) {
      $reportInstance->setAction($taskAction);
      // Get all Submit Values
      $params = $form->getVar('_submitValues');
      $reportInstance->setFormParams($params);

      // Set Columns
      if($params['fields'])
        $reportInstance->setColumns($params['fields']);

      // Build the Json File Config
      $reportInstance->buildJsonConfigSettings();

      // Save and create the JSON File
      // Redirect is set to TRUE by default
      $reportInstance->writeJsonConfigFile();
    }
  }
} // */

/**
 * Implements hook_civicrm_post().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 */
function chreports_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($objectName == "ReportInstance" && $op == "delete") {
    $filePath = CRM_Chreports_Reports_ReportConfiguration::getFilePath((array) $objectRef);
    if (is_file($filePath['source'])) {
      unlink($filePath['source']);
    }
  }
}


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
