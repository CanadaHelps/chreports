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
  if (in_array($formName, [
    'CRM_Report_Form_Contact_Summary',
    'CRM_Chreports_Form_Report_ExtendSummary',
    'CRM_Chreports_Form_Report_GLSummaryReport',
    'CRM_Chreports_Form_Report_ExtendedDetail'
  ])) {
    CRM_Core_Resources::singleton()->addScript(
      "CRM.$(function($) {
        $('.report-layout.display').wrap('<div class=\"new\" style=\"overflow:scroll; width:100%;\"></div>');
        $( '.report-layout.display tr.crm-report-sectionHeader' ).each(function() {
          if( $( this ).has('.crm-report-sectionHeader')){
            if ($( this ).find('th').children().text().indexOf('none') > -1) {
              $( this ).find('th').children().text($( this ).find('th').children().text().replace('none', 'Unassigned'))
            }
          }
        });
      });"
    );
  }
  if ($formName == 'CRM_Chreports_Form_Report_ExtendSummary' || $formName == 'CRM_Chreports_Form_Report_GLSummaryReport' || $formName == 'CRM_Chreports_Form_Report_ExtendedDetail' || $formName == "CRM_Chreports_Form_Report_ExtendLYBUNT" || $formName == "CRM_Chreports_Form_Report_ExtendMonthlyYearly") {
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
    }

  }
  if ($formName == 'CRM_Chreports_Form_Report_ExtendSummary' || $formName == 'CRM_Report_Form_Contact_Summary'|| $formName == 'CRM_Chreports_Form_Report_GLSummaryReport' || $formName == "CRM_Chreports_Form_Report_ExtendLYBUNT" || $formName == "CRM_Chreports_Form_Report_ExtendMonthlyYearly") {
   
    CRM_Core_Resources::singleton()->addScript(
      "CRM.$(function($) {
        $('#fields_total_amount').parent().hide();
        $('.crm-report-criteria-field input:checkbox').click(function() {
          $('#group_bys_' + this.id.substr(7)).prop('checked', this.checked);
        });
      });");
   }
   if ($formName == 'CRM_Chreports_Form_Report_RetentionRate') {
     CRM_Core_Resources::singleton()->addScript(
       "CRM.$(function($) {
         $('ul.ui-tabs-nav li:nth-child(1), ul.ui-tabs-nav li:nth-child(3), ul.ui-tabs-nav li:nth-child(5)').hide();
         $('#report-tab-col-groups').hide();
         $('.count-link').parent().css('text-align', 'right');
         $('.reports-header').css('text-align', 'right');
         $('.crm-report-instanceForm-form-block-is_navigation, .crm-report-instanceForm-form-block-permission, .crm-report-instanceForm-form-block-role, .crm-report-instanceForm-form-block-isReserved, .crm-report-instanceForm-form-block-report_header, .crm-report-instanceForm-form-block-report_footer').hide();
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

  if ($object instanceof CRM_Chreports_Form_Report_ExtendSummary || $object instanceof CRM_Chreports_Form_Report_ExtendedDetail || $object instanceof CRM_Chreports_Form_Report_ExtendMonthlyYearly) {
   
      $reportInstance = $object->getReportInstance();

      if ($varType == 'columns') {
        //manage columns, group bys, sorts, filters based on json config
        $reportInstance->setFormOptions($var);
        return;
      }

      if ($varType == 'sql') {
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
  if ($object instanceof CRM_Chreports_Form_Report_GLSummaryReport) {
    return;
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
  if (($object instanceof CRM_Chreports_Form_Report_ExtendedDetail ||
    $object instanceof CRM_Chreports_Form_Report_ExtendSummary ||
    $object instanceof CRM_Chreports_Form_Report_GLSummaryReport ||
    $object instanceof CRM_Report_Form_Contact_Summary ||
    $object instanceof CRM_Report_Form_Activity ||
    $object instanceof CRM_Report_Form_Grant_Detail ||
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
    if ($object instanceof CRM_Chreports_Form_Report_ExtendSummary) {
      $fieldsToHide['civicrm_contact'] = array_merge($fieldsToHide['civicrm_contact'], [
        'sort_name',
        'contact_type',
        'contact_sub_type',
        'organization_name', // not sure
        'is_deceased', // not sure
      ]);
      if ($isCharityAdmin) {
        $fieldsToHide['civicrm_contact'][] = 'exposed_id';
      }
      $fieldsToHide['civicrm_phone'] = ['phone'];
      $fieldsToHide['civicrm_email'] = ['email'];
      $fieldsToHide['civicrm_contribution'] = array_merge($fieldsToHide['civicrm_contribution'], [
        'thankyou_date',
        'non_deductible_amount',
        'card_type_id', // not sure
        'payment_instrument_id',
      ]);
      $fieldsToHide['civicrm_financial_trxn'] = [
        'card_type_id',
      ];
      $fieldsToHide['civicrm_address'] = [
        'address_name',
        'street_number',
        'street_name',
        'street_address',
        'supplemental_address_1',
        'supplemental_address_2',
        'supplemental_address_3',
        'city',
        'street_unit',
        'postal_code',
        'state_province_id',
        'postal_code_suffix',
        'county_id',
        'country_id',
        'location_type_id',
        'address_id',
        'is_primary',
      ];
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
        'civicrm_contact' => [
          'exposed_id',
        ],
      ];
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
    if ($object instanceof CRM_Report_Form_Grant_Detail) {
      $fieldsToHide = [
        'civicrm_contact' => [
          'sort_name',
          'email_greeting_display',
          'do_not_mail',
          'middle_name',
          'suffix_id',
          'job_title',
          'preferred_language',
          'preferred_communication_method',
          'addressee_display',
          'do_not_sms',
          'last_name',
          'gender_id',
          'employer_id',
          'external_identifier',
          'do_not_email',
          'is_opt_out',
          'nick_name',
          'birth_date',
          'postal_greeting_display',
          'do_not_phone',
          'first_name',
          'prefix_id',
          'age',
          'expposed_id'
        ],
        'civicrm_grant' => [
          'rationale',
          'money_transfer_date',
          'grant_report_received'
        ],
        'civicrm_address' => [
          'address_name',
          'street_number',
          'street_name',
          'street_address',
          'supplemental_address_1',
          'supplemental_address_2',
          'supplemental_address_3',
          'city',
          'street_unit',
          'postal_code',
          'state_province_id',
          'postal_code_suffix',
          'county_id',
          'country_id',
        ],
        'civicrm_value_mailchimp_details' => ['delete'],
        'civicrm_value_summary_field_7' => ['delete'],
        'civicrm_value_email_consent_5' => ['delete'],
      ];
      unset($var['civicrm_address']['filters']);
      unset($var['civicrm_contact']['filters']);
      unset($var['civicrm_grant']['filters']['money_transfer_date']);
      unset($var['civicrm_grant']['group_bys']['money_transfer_date']);
      unset($var['civicrm_grant']['order_bys']['money_transfer_date']);

      if($varType == 'columns') {
        $labels = [
          'civicrm_contact' => [
            'display_name' => 'Prospect',
          ],
          'civicrm_grant' => [
            'amount_total' => 'Opportunity Amount',
            'application_received_date' => 'Application Deadline',
            'decision_date' => 'Decision Date',
            'grant_due_date' => 'Report Due',
            'amount_granted' => 'Amount Received',
          ]
        ];
        foreach($labels as $table => $label) {
          foreach ($label as $elementName => $title) {
            if (array_key_exists($elementName, $var[$table]['fields'])) {
              $var[$table]['fields'][$elementName]['title'] = ts($title);
            }
            if (is_array($var[$table]['filters']) && array_key_exists($elementName, $var[$table]['filters'])) {
              $var[$table]['filters'][$elementName]['title'] = ts($title);
            }
          }
        }
      }
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
  if ($object instanceof CRM_Report_Form_Contribute_Lybunt) {
    $object->setVar('_charts', []);
    if ($varType == 'rows') {
      foreach ($var as $rowNum => $row) {
        //Convert Display name into link
        if (array_key_exists('civicrm_contact_sort_name', $row) &&
          array_key_exists('civicrm_contribution_contact_id', $row)
        ) {
          $url = CRM_Utils_System::url("civicrm/contact/view",
            'reset=1&cid=' . $row['civicrm_contribution_contact_id'],
            $object->getVar('_absoluteUrl')
          );
          $var[$rowNum]['civicrm_contact_sort_name_link'] = $url;
          $var[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View contact");
        }
      }
    }
  }
  if ($object instanceof CRM_Report_Form_Contribute_Summary || $object instanceof CRM_Chreports_Form_Report_ExtendSummary || $object instanceof CRM_Chreports_Form_Report_ExtendMonthlyYearly) {
    //die( 'tttttt');
    $tablename = E::getTableNameByName('Campaign_Information');
    if ($varType == 'columns') {
      if ($object instanceof CRM_Chreports_Form_Report_ExtendSummary) {
        unset($var['civicrm_contribution']['fields']['total_amount']['statistics']['avg']);
      }
      $var['civicrm_contribution']['fields']['total_amount']['statistics'] =  ['count' => ts('Number of Contributions'), 'sum' => ts('Total Amount')];

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
  elseif ($object instanceof CRM_Report_Form_Contact_Summary && $varType == 'rows') {
    foreach ($var as $rowNum => $row) {
      if (!empty($var[$rowNum]['civicrm_contact_sort_name'])) {
        $url = CRM_Utils_System::url('dms/contact/view', 'reset=1&cid=' . $row['civicrm_contact_id']);
        $var[$rowNum]['civicrm_contact_sort_name'] = sprintf('<a href="%s" target="_blank">%s</a>', $url, $var[$rowNum]['civicrm_contact_sort_name']);
      }
    }
  }
  elseif ($object instanceof CRM_Report_Form_Grant_Detail && $varType == 'rows') {
    foreach ($var as $rowNum => $row) {
      // Add link to Prospect name
      $var[$rowNum]['civicrm_contact_display_name_link'] = $var[$rowNum]['civicrm_contact_id_link'];
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
  if($formName == "CRM_Chreports_Form_Report_ExtendSummary" || $formName == "CRM_Chreports_Form_Report_GLSummaryReport" || $formName == "CRM_Chreports_Form_Report_ExtendedDetail" || $formName == "CRM_Chreports_Form_Report_ExtendLYBUNT")
  {
    //hide empty custom fields based filter sections on filter tab
    $reportInstance = $form->getReportInstance();
    $filters = $form->getVar('_filters');
    $filters = $reportInstance->unsetEmptyFilterEntity($filters);
    $form->setVar('_filters', $filters);
    //make the default field selected for sort by option
    $defaults = $form->getVar('_defaults');
    $defaults = $reportInstance->setDefaultOptionSortBy($defaults);

    // if there are any Preselect Filters in Json, prepopulare on form load
    if($reportInstance->getPreSetFilterValues()) {
      $filterParams = $reportInstance->createCustomFilterParams();
      foreach($filterParams as $filterKey => $filterValue) {
        $defaultSelectedFilter[$filterKey] = $filterValue;
      }
      //$defaults[$filterKey] = $filterValue;
      $form->setVar('_formValues', $defaultSelectedFilter);
    }

    $form->setVar('_defaults', $defaults);

    //CRM-2097: For Save/Copy bypass the post Process
    if($form->getVar('_submitValues')['task'] == 'report_instance.copy') {
      $reportInstance->setAction('copy');
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
