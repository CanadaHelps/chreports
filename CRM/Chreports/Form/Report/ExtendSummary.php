<?php
use CRM_Chreports_ExtensionUtil as E;

class CRM_Chreports_Form_Report_ExtendSummary extends CRM_Report_Form_Contribute_Summary {

  public function from($entity = NULL) {
    parent::from($entity);
    $this->_from .= "
    LEFT JOIN civicrm_line_item li ON li.contribution_id = contribution_civireport.id
    LEFT JOIN civicrm_financial_item fi ON fi.entity_id = li.id AND fi.entity_table = 'civicrm_line_item'
    LEFT JOIN civicrm_financial_account fa ON fa.id = fi.financial_account_id
    ";
  }

}
