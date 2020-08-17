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

}
