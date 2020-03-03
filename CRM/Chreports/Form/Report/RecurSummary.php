<?php
use CRM_Chreports_ExtensionUtil as E;

class CRM_Chreports_Form_Report_RecurSummary extends CRM_Report_Form {

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_customGroupExtends = ['Contribute'];
  protected $_customGroupGroupBy = FALSE; 
  public function __construct() {
    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'sort_name' => [
            'title' => E::ts('Contact Name'),
            'required' => TRUE,
            'default' => TRUE,
            'no_repeat' => TRUE,
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'first_name' => [
            'title' => E::ts('First Name'),
            'no_repeat' => TRUE,
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'last_name' => [
            'title' => E::ts('Last Name'),
            'no_repeat' => TRUE,
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'filters' => [
          'sort_name' => [
            'title' => E::ts('Contact Name'),
            'operator' => 'like',
          ],
          'id' => [
            'no_display' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_address' => [
        'dao' => 'CRM_Core_DAO_Address',
        'fields' => [
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' => ['title' => E::ts('State/Province')],
          'country_id' => ['title' => E::ts('Country')],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_BAO_Contribution',
        'fields' => [
          'total_amount' => ['title' => E::ts('This Month Amount')],
          'source' => ['title' => E::ts('Contribution Source')],
          'completed_contributions' => [
            'title' => E::ts('Completed Contributions'),
            'dbAlias' => 'COUNT(DISTINCT contribution_summary.id)',
          ],
          'start_date' => [
            'title' => E::ts('Start Date/First Contribution'),
            'dbAlias' => 'MIN(contribution_summary.receive_date)',
          ],
          'last_month_amount' => [
            'title' => E::ts('Last Month Amount'),
            'dbAlias' => 'contribution_last_month.total_amount',
          ],
        ],
        'filters' => [
          'receive_date' => [
            'title' => E::ts('Receive Date'),
            'operatorType' => CRM_Report_form::OP_DATETIME,
            'type' => CRM_Utils_TYPE::T_DATE + CRM_Utils_Type::T_TIME,
          ],
        ],
        'grouping' => 'contribute-fields',
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => ['email' => NULL],
        'grouping' => 'contact-fields',
      ],
    ];
    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    $this->addCampaignFields('civicrm_contribution', FALSE, TRUE);
    $this->_groupByArray = ['contact_civireport.id'];
    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', E::ts('Contribution Recur Summary'));
    parent::preProcess();
  }

  function from() {
    $this->_from = NULL;

    $this->_from = "
         FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
               INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                          ON {$this->_aliases['civicrm_contact']}.id =
                             {$this->_aliases['civicrm_contribution']}.contact_id AND {$this->_aliases['civicrm_contribution']}.is_test = 0 
               LEFT JOIN civicrm_value_contribution__15 cd ON cd.entity_id = {$this->_aliases['civicrm_contribution']}.id
               INNER JOIN civicrm_contribution contribution_summary ON contribution_summary.contact_id = {$this->_aliases['civicrm_contribution']}.contact_id
                 AND contribution_summary.is_test = 0
               LEFT JOIN civicrm_value_contribution__15 csummary ON csummary.entity_id = {$this->_aliases['civicrm_contribution']}.id
               INNER JOIN civicrm_contribution contribution_last_month ON contribution_last_month.contact_id = {$this->_aliases['civicrm_contribution']}.contact_id
                 AND contribution_last_month.is_test = 0
               LEFT JOIN civicrm_value_contribution__15 clastMonth ON clastMonth.entity_id = {$this->_aliases['civicrm_contribution']}.id";


    $this->joinAddressFromContact();
    $this->joinEmailFromContact();
  }

  /**
   * Add field specific select alterations.
   *
   * @param string $tableName
   * @param string $tableKey
   * @param string $fieldName
   * @param array $field
   *
   * @return string
   */
  function selectClause(&$tableName, $tableKey, &$fieldName, &$field) {
    return parent::selectClause($tableName, $tableKey, $fieldName, $field);
  }

  /**
   * Add field specific where alterations.
   *
   * This can be overridden in reports for special treatment of a field
   *
   * @param array $field Field specifications
   * @param string $op Query operator (not an exact match to sql)
   * @param mixed $value
   * @param float $min
   * @param float $max
   *
   * @return null|string
   */
  public function whereClause(&$field, $op, $value, $min, $max) {
    return parent::whereClause($field, $op, $value, $min, $max);
  }

  /**
   * Build where clause.
   */
  public function where() {
    $this->storeWhereHavingClauseArray();

    $this->_whereClauses[] = $this->dateClause('contribution_last_month.receive_date', 'previous.month', NULL, NULL, CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME, NULL, NULL);
    $this->_whereClauses[] = '( contribution_civireport.contribution_recur_id <> 0 OR cd.sg_flag_38 <> 0 )';
    $this->_whereClauses[] = '( contribution_last_month.contribution_recur_id <> 0 OR csummary.sg_flag_38 <> 0 )';
    $this->_whereClauses[] = '( contribution_summary.contribution_recur_id <> 0 OR clastMonth.sg_flag_38 <> 0 )';
    if (empty($this->_whereClauses)) {
      $this->_where = "WHERE ( 1 ) ";
      $this->_having = "";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $this->_whereClauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }

    if (!empty($this->_havingClauses)) {
      // use this clause to construct group by clause.
      $this->_having = "HAVING " . implode(' AND ', $this->_havingClauses);
    }
  }

  public function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    $checkList = array();
    foreach ($rows as $rowNum => $row) {

      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // not repeat contact display names if it matches with the one
        // in previous row
        $repeatFound = FALSE;
        foreach ($row as $colName => $colVal) {
          if (CRM_Utils_Array::value($colName, $checkList) &&
            is_array($checkList[$colName]) &&
            in_array($colVal, $checkList[$colName])
          ) {
            $rows[$rowNum][$colName] = "";
            $repeatFound = TRUE;
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }

      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = E::ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }

      if (!$entryFound) {
        break;
      }
    }
  }

}
