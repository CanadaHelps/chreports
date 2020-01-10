<?php
use CRM_Chreports_ExtensionUtil as E;

class CRM_Chreports_Form_Report_ExtendSummary extends CRM_Report_Form_Contribute_Summary {

  public function from($entity = NULL) {
    parent::from($entity);
    if (!strstr($this->_from, 'civicrm_line_item li') && array_key_exists('financial_account', $this->_params['group_bys'])) {
      $this->_from .= "
      LEFT JOIN (SELECT MAX(fi.financial_account_id) as fa_id, fa.name as `financial_account_name`, li.contribution_id

      FROM civicrm_line_item li
      LEFT JOIN civicrm_financial_item fi ON fi.entity_id = li.id AND fi.entity_table = 'civicrm_line_item'
      LEFT JOIN civicrm_financial_account fa ON fa.id = fi.financial_account_id
      WHERE fi.financial_account_id IS NOT NULL AND fa.name IS NOT NULL
      GROUP BY li.id
      ) temp ON temp.contribution_id = contribution_civireport.id
      ";
    }
  }

}
