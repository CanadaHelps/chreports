<?php
use CRM_Chreports_ExtensionUtil as E;

class CRM_Chreports_Form_Report_RecurSummary extends CRM_Report_Form {

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_customGroupExtends = ['Contribute'];
  protected $_customGroupGroupBy = FALSE;
  public function __construct() {
    $this->_rollup = 'WITH ROLLUP';
    $thisMonthFirstDay = date('Ymd000000', strtotime("first day of this month"));
    $thisMonthLastDay = date('Ymd235959', strtotime("last day of this month"));
    $lastMonthFirstDay = date('Ymd000000', strtotime("first day of last month"));
    $lastMonthLastDay = date('Ymd235959', strtotime("last day of last month"));
    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'id' => [
            'title' => E::ts('Contact ID'),
            'required' => TRUE,
          ],
          'sort_name' => [
            'title' => E::ts('Contact Name'),
            'required' => TRUE,
            'default' => TRUE,
            'no_repeat' => TRUE,
          ],
          'first_name' => [
            'title' => E::ts('First Name'),
            'no_repeat' => TRUE,
          ],
          'last_name' => [
            'title' => E::ts('Last Name'),
            'no_repeat' => TRUE,
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
          'street_address' => ['title' => E::ts('Address - Primary')],
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' => ['title' => E::ts('State/Province')],
          'country_id' => ['title' => E::ts('Country')],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => ['phone' => NULL],
        'grouping' => 'contact-fields',
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => ['email' => NULL],
        'grouping' => 'contact-fields',
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_BAO_Contribution',
        'fields' => [
          'total_amount' => [
            'title' => E::ts('This Month Amount'),
            'dbAlias' => "SUM(IF(contribution_civireport.receive_date >= '$thisMonthFirstDay' AND contribution_civireport.receive_date <= '$thisMonthLastDay', contribution_civireport.total_amount, 0))",
          ],
          'source' => ['title' => E::ts('Contribution Source')],
          'completed_contributions' => [
            'title' => E::ts('Completed Contributions'),
            'dbAlias' => 'COUNT(DISTINCT contribution_civireport.id)',
          ],
          'start_date' => [
            'title' => E::ts('Start Date/First Contribution'),
            'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
            'dbAlias' => 'MIN(contribution_civireport.receive_date)',
          ],
          'last_month_amount' => [
            'title' => E::ts('Last Month Amount'),
            'type' => CRM_Utils_TYPE::T_MONEY,
            'dbAlias' => "SUM(IF(contribution_civireport.receive_date >= '$lastMonthFirstDay' AND contribution_civireport.receive_date <= '$lastMonthLastDay', contribution_civireport.total_amount, 0))",
          ],
        ],
        'filters' => [
          'receive_date' => [
            'title' => E::ts('Receive Date'),
            'default' => 'this.month',
            'operatorType' => CRM_Report_form::OP_DATETIME,
            'type' => CRM_Utils_TYPE::T_DATE + CRM_Utils_Type::T_TIME,
          ],
        ],
        'grouping' => 'contribute-fields',
      ],
    ];
    $this->_statFields = ['civicrm_contribution_total_amount', 'civicrm_contribution_last_month_amount'];
    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    $this->addCampaignFields('civicrm_contribution', FALSE, TRUE);
    $this->_groupByArray = ['contact_civireport.id'];
    parent::__construct();
  }

  function from() {
    $tablename = E::getTableNameByName('Contribution_Details');

    $this->_from = "
         FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
               INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                          ON {$this->_aliases['civicrm_contact']}.id =
                             {$this->_aliases['civicrm_contribution']}.contact_id AND {$this->_aliases['civicrm_contribution']}.is_test = 0
               LEFT JOIN {$tablename} cd ON cd.entity_id = {$this->_aliases['civicrm_contribution']}.id
      ";

    $this->joinAddressFromContact();
    $this->joinEmailFromContact();
    $this->joinPhoneFromContact();
  }


  /**
   * Build where clause.
   */
  public function where() {
    $this->storeWhereHavingClauseArray();
    $columnName = E::getColumnNameByName('SG_Flag');

    $this->_whereClauses[] = "( contribution_civireport.contribution_recur_id <> 0 OR cd.$columnName <> 0 )";

    $this->_where = "WHERE " . implode(' AND ', $this->_whereClauses);

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }

    if (!empty($this->_havingClauses)) {
      // use this clause to construct group by clause.
      $this->_having = "HAVING " . implode(' AND ', $this->_havingClauses);
    }
  }

  public function groupBy() {
    parent::groupBy();
    $this->_groupBy .= ' ' . $this->_rollup;
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
