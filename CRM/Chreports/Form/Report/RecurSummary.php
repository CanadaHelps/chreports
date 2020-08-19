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
          'id' => [
            'title' => E::ts('Contact ID'),
            'required' => TRUE,
          ],
          'exposed_id' => [
            'title' => E::ts('Contact ID'),
            'default' => TRUE,
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
            'required' => TRUE,
            'dbAlias' => "temp.this_month_amount",
          ],
          'source' => [
            'title' => E::ts('Contribution Source'),
            'dbAlias' => 'GROUP_CONCAT(DISTINCT contribution_civireport.source)',
          ],
          'completed_contributions' => [
            'title' => E::ts('Completed Contributions'),
            'dbAlias' => 'temp.completed_contributions',
          ],
          'start_date' => [
            'title' => E::ts('Start Date/First Contribution'),
            'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
            'dbAlias' => 'temp.first_date',
          ],
          'last_month_amount' => [
            'title' => E::ts('Last Month Amount'),
            'type' => CRM_Utils_TYPE::T_MONEY,
            'required' => TRUE,
            'dbAlias' => "temp.last_month_amount",
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
    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    $this->addCampaignFields('civicrm_contribution', FALSE, TRUE);
    $this->_groupByArray = ['contact_civireport.id'];
    parent::__construct();
  }

  function from() {
    $thisMonthFirstDay = date('Ymd000000', strtotime("first day of this month"));
    $thisMonthLastDay = date('Ymd235959', strtotime("last day of this month"));
    $lastMonthFirstDay = date('Ymd000000', strtotime("first day of last month"));
    $lastMonthLastDay = date('Ymd235959', strtotime("last day of last month"));
    $tablename = E::getTableNameByName('Contribution_Details');

    $this->_from = "
         FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
               INNER JOIN
               (
	       SELECT contact_id, SUM(this_month_amount) as this_month_amount, SUM(last_month_amount) as last_month_amount, MIN(first_date) as first_date, SUM(completed_contributions) as completed_contributions
	        FROM (
               (SELECT cc.contact_id, SUM(cc.total_amount) as this_month_amount, 0 as last_month_amount, MIN(cc.receive_date) as first_date, 0 as completed_contributions
                 FROM civicrm_contribution cc
                      LEFT JOIN {$tablename} cd ON cd.entity_id = cc.id
                  WHERE cc.contribution_status_id = 1 AND cc.receive_date >= {$thisMonthFirstDay} AND cc.receive_date <= {$thisMonthLastDay} AND (cc.contribution_recur_id <> 0 OR cd.sg_flag_38 <> 0)
               GROUP BY cc.contact_id)
                UNION
               (SELECT cc.contact_id, 0 as this_month_amount, SUM(cc.total_amount) as last_month_amount, MIN(cc.receive_date) as first_date, 0 as completed_contributions
                  FROM civicrm_contribution cc
                    LEFT JOIN {$tablename} cd ON cd.entity_id = cc.id
                  WHERE cc.contribution_status_id = 1 AND cc.receive_date >= {$lastMonthFirstDay} AND cc.receive_date <= {$lastMonthLastDay} AND (cc.contribution_recur_id <> 0 OR cd.sg_flag_38 <> 0)

                  GROUP BY cc.contact_id)
                  UNION
                 (SELECT cc.contact_id, 0 as this_month_amount, 0 as last_month_amount, MIN(cc.receive_date) as first_date, COUNT(DISTINCT cc.id)
                    FROM civicrm_contribution cc
                      LEFT JOIN {$tablename} cd ON cd.entity_id = cc.id
                    WHERE cc.contribution_status_id = 1 AND (cc.contribution_recur_id <> 0 OR cd.sg_flag_38 <> 0)

                    GROUP BY cc.contact_id)
               ) temp1 GROUP BY contact_id
               ) temp ON temp.contact_id = {$this->_aliases['civicrm_contact']}.id
               LEFT JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                          ON {$this->_aliases['civicrm_contact']}.id =
                             {$this->_aliases['civicrm_contribution']}.contact_id AND {$this->_aliases['civicrm_contribution']}.is_test = 0
               LEFT JOIN {$tablename} cd ON cd.entity_id = {$this->_aliases['civicrm_contribution']}.id
      ";

    $this->joinAddressFromContact();
    $this->joinEmailFromContact();
    $this->joinPhoneFromContact();
  }

  public function statistics(&$rows) {
    $selectColumns = [
      $this->_columns['civicrm_contribution']['fields']['total_amount']['dbAlias'] . " as `civicrm_contribution_total_amount`",
      $this->_columns['civicrm_contribution']['fields']['last_month_amount']['dbAlias'] . " as `civicrm_contribution_last_month_amount`",
    ];

    $sql = "SELECT SUM(stat.civicrm_contribution_total_amount) AS civicrm_contribution_total_amount,
            SUM(stat.civicrm_contribution_last_month_amount) AS civicrm_contribution_last_month_amount
            FROM (SELECT " . implode(", ", $selectColumns) . " $this->_from $this->_where $this->_groupBy) AS stat";
    $this->addToDeveloperTab($sql);
    $results = CRM_Core_DAO::executeQuery($sql)->fetchAll();

    $statistics = [
      'counts' => [
        'civicrm_contribution_total_amount' => [
          'title' => ts('This Month Total Amount'),
          'value' => $results[0]['civicrm_contribution_total_amount'],
          'type' => CRM_Utils_Type::T_MONEY,
        ],
        'civicrm_contribution_last_month_amount' => [
          'title' => ts('Last Month Total Amount'),
          'value' => $results[0]['civicrm_contribution_last_month_amount'],
          'type' => CRM_Utils_Type::T_MONEY,
        ],
      ],
    ];

    $columnHeaders = [];
    foreach ([
      'civicrm_contact_exposed_id',
      'civicrm_contact_sort_name',
      'civicrm_address_street_address',
      'civicrm_address_city',
      'civicrm_address_state_province_id',
      'civicrm_address_postal_code',
      'civicrm_address_country_id',
      'civicrm_phone_phone',
      'civicrm_email_email',
      'civicrm_contribution_source',
      'civicrm_contribution_last_month_amount',
      'civicrm_contribution_total_amount',
      'civicrm_contribution_start_date',
      'civicrm_contribution_completed_contributions',
    ] as $name) {
      if (array_key_exists($name, $this->_columnHeaders)) {
        $columnHeaders[$name] = $this->_columnHeaders[$name];
        unset($this->_columnHeaders[$name]);
      }
    }
    $this->_columnHeaders = array_merge($columnHeaders, $this->_columnHeaders);

    return $statistics;
  }


  /**
   * Build where clause.
   */
  public function where() {
    $this->storeWhereHavingClauseArray();
    $columnName = E::getColumnNameByName('SG_Flag');

    $this->_whereClauses[] = "( contribution_civireport.contribution_recur_id <> 0 OR cd.$columnName <> 0 )";
    $this->_whereClauses[] = "( contribution_civireport.contribution_status_id = 1 )";

    $this->_where = "WHERE " . implode(' AND ', $this->_whereClauses);

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
