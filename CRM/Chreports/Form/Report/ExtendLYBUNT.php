<?php

class CRM_Chreports_Form_Report_ExtendLYBUNT extends CRM_Report_Form_Contribute_Lybunt {
  public function __construct() {
    parent::__construct();
    $this->_columns['civicrm_contribution']['fields']['last_four_year_total_amount'] = [
      'title' => ts('Last 4rth Year Total'),
      'default' => TRUE,
      'statistics' => ['sum' => ts('Last 4 Years total')],
      'type' => CRM_Utils_Type::T_MONEY,
    ];
    $this->_columns['civicrm_contribution']['fields']['last_three_year_total_amount'] = [
      'title' => ts('Last 3rd Year Total'),
      'default' => TRUE,
      'statistics' => ['sum' => ts('Last 3 Years total')],
      'type' => CRM_Utils_Type::T_MONEY,
    ];
    $this->_columns['civicrm_contribution']['fields']['last_two_year_total_amount'] = [
      'title' => ts('Last 2nd Year Total'),
      'default' => TRUE,
      'statistics' => ['sum' => ts('Last 2 Years total')],
      'type' => CRM_Utils_Type::T_MONEY,
    ];
    $previousYearField = $this->_columns['civicrm_contribution']['fields']['last_year_total_amount'];
    unset($this->_columns['civicrm_contribution']['fields']['last_year_total_amount']);
    $this->_columns['civicrm_contribution']['fields']['last_year_total_amount'] = $previousYearField;
    $this->_columns['civicrm_contact']['fields'] = array('exposed_id' => $this->_columns['civicrm_contact']['fields']['exposed_id']) + $this->_columns['civicrm_contact']['fields'];
    $this->_columns['civicrm_contact']['fields']['exposed_id']['default'] = TRUE;
  }

  /**
   * Build select clause for a single field.
   *
   * @param string $tableName
   * @param string $tableKey
   * @param string $fieldName
   * @param string $field
   *
   * @return string
   */
  public function selectClause(&$tableName, $tableKey, &$fieldName, &$field) {
    if ($fieldName == 'last_year_total_amount') {
      $this->_columnHeaders["{$tableName}_{$fieldName}"] = $field;
      $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $this->getLastYearColumnTitle();
      $this->_statFields[$this->getLastYearColumnTitle()] = "{$tableName}_{$fieldName}";
      return "SUM(IF(" . $this->whereClauseLastYear('contribution_civireport.receive_date') . ", contribution_civireport.total_amount, 0)) as {$tableName}_{$fieldName}";
    }
    if ($fieldName == 'last_four_year_total_amount') {
      $this->_columnHeaders["{$tableName}_{$fieldName}"] = $field;
      $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $this->getLastNYearColumnTitle(4);
      $this->_statFields[$this->getLastNYearColumnTitle(4)] = "{$tableName}_{$fieldName}";
      return "SUM(IF(" . $this->whereClauseLastNYears('contribution_civireport.receive_date', 4) . ", contribution_civireport.total_amount, 0)) as {$tableName}_{$fieldName}";
    }
    if ($fieldName == 'last_three_year_total_amount') {
      $this->_columnHeaders["{$tableName}_{$fieldName}"] = $field;
      $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $this->getLastNYearColumnTitle(3);
      $this->_statFields[$this->getLastNYearColumnTitle(3)] = "{$tableName}_{$fieldName}";
      return "SUM(IF(" . $this->whereClauseLastNYears('contribution_civireport.receive_date', 3) . ", contribution_civireport.total_amount, 0)) as {$tableName}_{$fieldName}";
    }
    if ($fieldName == 'last_two_year_total_amount') {
      $this->_columnHeaders["{$tableName}_{$fieldName}"] = $field;
      $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $this->getLastNYearColumnTitle(2);
      $this->_statFields[$this->getLastNYearColumnTitle(2)] = "{$tableName}_{$fieldName}";
      return "SUM(IF(" . $this->whereClauseLastNYears('contribution_civireport.receive_date', 2) . ", contribution_civireport.total_amount, 0)) as {$tableName}_{$fieldName}";
    }

    if ($fieldName == 'civicrm_life_time_total') {
      $this->_columnHeaders["{$tableName}_{$fieldName}"] = $field;
      $this->_statFields[$field['title']] = "{$tableName}_{$fieldName}";
      return "SUM({$this->_aliases[$tableName]}.total_amount) as {$tableName}_{$fieldName}";
    }
    if ($fieldName == 'receive_date') {
      return self::fiscalYearOffset($field['dbAlias']) .
        " as {$tableName}_{$fieldName} ";
    }
    return FALSE;
  }

  /**
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {

    $statistics = parent::statistics($rows);
    // The parent class does something odd where it adds an extra row to the count for the grand total.
    // Perhaps that works on some other report? But here it just seems odd.
    $this->countStat($statistics, count($rows));
    if (!empty($rows)) {
      if (!empty($this->rollupRow) && !empty($this->rollupRow['civicrm_contribution_last_year_total_amount'])) {
        $statistics['counts']['civicrm_contribution_last_year_total_amount'] = [
          'value' => $this->rollupRow['civicrm_contribution_last_year_total_amount'],
          'title' => $this->getLastYearColumnTitle(),
          'type' => CRM_Utils_Type::T_MONEY,
        ];

      }
      if (!empty($this->rollupRow) && !empty($this->rollupRow['civicrm_contribution_civicrm_life_time_total'])) {
        $statistics['counts']['civicrm_contribution_civicrm_life_time_total'] = [
          'value' => $this->rollupRow['civicrm_contribution_civicrm_life_time_total'],
          'title' => ts('Total LifeTime'),
          'type' => CRM_Utils_Type::T_MONEY,
        ];
      }
      else {
        $select = "SELECT SUM({$this->_aliases['civicrm_contribution']}.total_amount) as amount,
          SUM(IF( " . $this->whereClauseLastYear('contribution_civireport.receive_date') . ", contribution_civireport.total_amount, 0)) as last_year,
          SUM(IF( " . $this->whereClauseLastNYears('contribution_civireport.receive_date', 2) . ", contribution_civireport.total_amount, 0)) as last_2_year,
          SUM(IF( " . $this->whereClauseLastNYears('contribution_civireport.receive_date', 3) . ", contribution_civireport.total_amount, 0)) as last_3_year,
          SUM(IF( " . $this->whereClauseLastNYears('contribution_civireport.receive_date', 4) . ", contribution_civireport.total_amount, 0)) as last_4_year
         ";

        $sql = "{$select} {$this->_from} {$this->_where}";
        $dao = CRM_Core_DAO::executeQuery($sql);
        if ($dao->fetch()) {
          $statistics['counts']['amount'] = [
            'value' => $dao->amount,
            'title' => ts('Total LifeTime'),
            'type' => CRM_Utils_Type::T_MONEY,
          ];
          $statistics['counts']['last_4_year'] = [
            'value' => $dao->last_4_year,
            'title' => $this->getLastNYearColumnTitle(4),
            'type' => CRM_Utils_Type::T_MONEY,
          ];
          $statistics['counts']['last_3_year'] = [
            'value' => $dao->last_3_year,
            'title' => $this->getLastNYearColumnTitle(3),
            'type' => CRM_Utils_Type::T_MONEY,
          ];
          $statistics['counts']['last_2_year'] = [
            'value' => $dao->last_2_year,
            'title' => $this->getLastNYearColumnTitle(2),
            'type' => CRM_Utils_Type::T_MONEY,
          ];
          $statistics['counts']['last_year'] = [
            'value' => $dao->last_year,
            'title' => $this->getLastYearColumnTitle(),
            'type' => CRM_Utils_Type::T_MONEY,
          ];
        }
      }
    }

    return $statistics;
  }

  public function from() {
    if (!empty($this->contactTempTable)) {
      $this->_from = "
        FROM  civicrm_contribution {$this->_aliases['civicrm_contribution']}
        INNER JOIN $this->contactTempTable restricted_contacts
          ON restricted_contacts.cid = {$this->_aliases['civicrm_contribution']}.contact_id
          AND {$this->_aliases['civicrm_contribution']}.is_test = 0
        INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
          ON restricted_contacts.cid = {$this->_aliases['civicrm_contact']}.id";

      $this->joinAddressFromContact();
      $this->joinPhoneFromContact();
      $this->joinEmailFromContact();
    }
    else {
      $this->setFromBase('civicrm_contact');

      $this->_from .= " INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']} ";
      if (!$this->groupTempTable) {
        // The received_date index is better than the contribution_status_id index (fairly substantially).
        // But if we have already pre-filtered down to a group of contacts then we want that to be the
        // primary filter and the index hint will block that.
        $this->_from .= "USE index (received_date)";
      }
      $this->_from .= " ON {$this->_aliases['civicrm_contribution']}.contact_id = {$this->_aliases['civicrm_contact']}.id
         AND {$this->_aliases['civicrm_contribution']}.is_test = 0
         AND " . $this->whereClauseLast4Year("{$this->_aliases['civicrm_contribution']}.receive_date") . "
       {$this->_aclFrom} ";
      $this->selectivelyAddLocationTablesJoinsToFilterQuery();
    }

    // for credit card type
    $this->addFinancialTrxnFromClause();
  }

  public function whereClauseLast4Year($fieldName) {
    return "$fieldName BETWEEN '" . $this->getFirstDateOfPriorRangeNYears(4) . "' AND '" . $this->getLastDateOfPriorRange() . "'";
  }


  public function whereClause(&$field, $op, $value, $min, $max) {
    if ($field['name'] == 'receive_date') {
      $clause = 1;
      if (empty($this->contactTempTable)) {
        $clause = "{$this->_aliases['civicrm_contribution']}.contact_id NOT IN (
          SELECT cont_exclude.contact_id
          FROM civicrm_contribution cont_exclude
          WHERE " . $this->whereClauseThisYear('cont_exclude.receive_date')
        . ")";
      }
    }
    // Group filtering is already done so skip.
    elseif (!empty($field['group']) && $this->contactTempTable) {
      return 1;
    }
    else {
      $clause = parent::whereClause($field, $op, $value, $min, $max);
    }
    return $clause;
  }

  /**
   * Get the title for the last year column.
   */
  public function getLastNYearColumnTitle($year) {
    if ($this->getYearFilterType() == 'calendar') {
      return ts('Total for ') . ($this->getCurrentYear() - $year);
    }
    return ts('Total for Fiscal Year ') . ($this->getCurrentYear() - $year) . '-' . ($this->getCurrentYear());
  }

  /**
   * Generate where clause for last calendar year or fiscal year.
   *
   * @todo must be possible to re-use relative dates stuff.
   *
   * @param string $fieldName
   *
   * @return string
   */
  public function whereClauseLastNYears($fieldName, $count) {
    return "$fieldName BETWEEN '" . $this->getFirstDateOfPriorRangeNYears($count) . "' AND '" . $this->getLastDateOfPriorRangeNYears($count) . "'";
  }

  /**
   * Get the date time of the first date in the 'last year' range.
   *
   * @return string
   */
  public function getFirstDateOfPriorRangeNYears($count) {
    return date('YmdHis', strtotime("- $count years", strtotime($this->getFirstDateOfCurrentRange())));
  }

  /**
   * Get the date time of the last date in the 'last year' range.
   *
   * @return string
   */
  public function getLastDateOfPriorRangeNYears($count) {
    return date('YmdHis', strtotime("+ 1 years - 1 second", strtotime($this->getFirstDateOfPriorRangeNYears($count))));
  }

  /**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {
    $entryFound = FALSE;

    foreach ($rows as $rowNum => $row) {
      //Convert Display name into link
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contribution_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contribution_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View contact");
        $entryFound = TRUE;
      }

      // convert campaign_id to campaign title
      if (array_key_exists('civicrm_contribution_campaign_id', $row)) {
        if ($value = $row['civicrm_contribution_campaign_id']) {
          $rows[$rowNum]['civicrm_contribution_campaign_id'] = $this->campaigns[$value];
          $entryFound = TRUE;
        }
      }
      // Display 'Yes' if the email is on hold (leave blank for no so it stands out better).
      if (array_key_exists('civicrm_email_on_hold', $row)) {
        $rows[$rowNum]['civicrm_email_on_hold'] = $row['civicrm_email_on_hold'] ? ts('Yes') : '';
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, NULL, 'List all contribution(s)') ? TRUE : $entryFound;
      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, NULL, 'List all contribution(s)') ? TRUE : $entryFound;

      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = $this->getLabels($row['civicrm_financial_trxn_card_type_id'], 'CRM_Financial_DAO_FinancialTrxn', 'card_type_id');
        $entryFound = TRUE;
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }


}
