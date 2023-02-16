<?php

class CRM_Chreports_Form_Report_RevisedLYBUNT extends CRM_Report_Form_Contribute_Lybunt {
  public function __construct() {
    parent::__construct();
    $previousYearField = $this->_columns['civicrm_contribution']['fields']['last_year_total_amount'];
    unset($this->_columns['civicrm_contribution']['fields']['last_year_total_amount']);
    $this->_columns['civicrm_contribution']['fields']['last_year_total_amount'] = $previousYearField;
    // CRM-515 Remove Email, phone and country columns from showing by default
    $this->_columns['civicrm_email']['fields']['email']['default'] = FALSE;
    $this->_columns['civicrm_phone']['fields']['phone']['default'] = FALSE;
    $this->_columns['civicrm_address']['fields']['country_id']['default'] = FALSE;
    $this->_columns['civicrm_contact']['fields'] = array('exposed_id' => $this->_columns['civicrm_contact']['fields']['exposed_id']) + $this->_columns['civicrm_contact']['fields'];
    $this->_columns['civicrm_contact']['fields']['exposed_id']['default'] = TRUE;
  }

  public function beginPostProcessCommon() {
    $this->buildQuery();
    // @todo this acl has no test coverage and is very hard to test manually so could be fragile.
    $this->resetFormSqlAndWhereHavingClauses();

    $this->contactTempTable = $this->createTemporaryTable('rptlybunt', "
      SELECT SQL_CALC_FOUND_ROWS {$this->_aliases['civicrm_contact']}.id as cid,
      MAX(contribution_civireport.receive_date) as lastContributionTime
      {$this->_from}
      {$this->_where}
      GROUP BY {$this->_aliases['civicrm_contact']}.id
      HAVING " . $this->whereClauseLastYear("lastContributionTime")
    );
    $this->limit();
    if (empty($this->_params['charts'])) {
      $this->setPager();
    }

    // Reset where clauses to be regenerated in postProcess.
    $this->_whereClauses = [];
  }

  public function whereClause(&$field, $op, $value, $min, $max) {
    if ($field['name'] == 'receive_date') {
      $clause = 1;
      $completedStatus = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
      if (empty($this->contactTempTable)) {
        $clause = "{$this->_aliases['civicrm_contribution']}.contact_id NOT IN (
          SELECT cont_exclude.contact_id
          FROM civicrm_contribution cont_exclude
          WHERE " . $this->whereClauseThisYear('cont_exclude.receive_date')
          . " AND cont_exclude.contribution_status_id IN (" . $completedStatus . ") and cont_exclude.is_test = 0
           )";
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
   * Construct from clause.
   *
   * On the first run we are creating a table of contacts to include in the report.
   *
   * Once contactTempTable is populated we should avoid using any further filters that affect
   * the contacts that should be visible.
   */
  public function from() {
    if (!empty($this->contactTempTable)) {
      $this->_from = "
        FROM  civicrm_contribution {$this->_aliases['civicrm_contribution']}
        INNER JOIN $this->contactTempTable restricted_contacts
          ON restricted_contacts.cid = {$this->_aliases['civicrm_contribution']}.contact_id
          AND {$this->_aliases['civicrm_contribution']}.is_test = 0
          AND {$this->_aliases['civicrm_contribution']}.is_template = 0
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
         AND {$this->_aliases['civicrm_contribution']}.is_template = 0
       {$this->_aclFrom} ";
      $this->selectivelyAddLocationTablesJoinsToFilterQuery();
    }

    // for credit card type
    $this->addFinancialTrxnFromClause();
  }
}
