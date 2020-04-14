<?php
use CRM_Chreports_ExtensionUtil as E;

class CRM_Chreports_Form_Report_GLSummaryReport extends CRM_Report_Form {

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = ['Contribute'];
  protected $_customGroupGroupBy = FALSE; function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'sort_name' => array(
            'title' => E::ts('Contact Name'),
            'no_repeat' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' => array(
          'id' => array(
            'no_display' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_BAO_Contribution',
        'fields' => [
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'gl_account' => [
            'title' => E::ts('Financial Account'),
            'required' => TRUE,
            'dbAlias' => 'temp.civicrm_contact_financial_account',
          ],
          'gl_account_code' => [
            'title' => E::ts('Financial Account Code'),
            'required' => TRUE,
            'dbAlias' => 'temp.gl_account_code',
          ],
          'count' => [
            'title' => E::ts('Number of Contributions'),
            'type' => CRM_Utils_TYPE::T_INT,
            'dbAlias' => 'COUNT(DISTINCT contribution_civireport.id)',
          ],
          'gl_amount' => [
            'title' => E::ts('Total Amount'),
            'default' => TRUE,
            'type' => CRM_Utils_TYPE::T_MONEY,
            'dbAlias' => 'SUM(temp.civicrm_contribution_amount_sum)'
          ],
        ],
        'filters' => [
          'receive_date' => [
            'title' => E::ts('Receive Date'),
            'operatorType' => CRM_Report_form::OP_DATETIME,
            'type' => CRM_Utils_TYPE::T_DATE + CRM_Utils_Type::T_TIME,
          ],
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
            'default' => [1],
            'type' => CRM_Utils_Type::T_INT,
          ],
          'gl_account' => [
            'title' => ts('Financial Account'),
            'type' => CRM_Utils_Type::T_STRING,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialAccount(),
            'dbAlias' => 'temp.civicrm_contact_financial_account_id',
          ],
          'financial_type_id' => [
            'title' => ts('Fund'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes(),
            'type' => CRM_Utils_Type::T_INT,
          ],
          'payment_instrument_id' => [
            'title' => ts('Payment Method'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::paymentInstrument(),
            'type' => CRM_Utils_Type::T_INT,
          ],
        ],
        'grouping' => 'contribute-fields',
      ],
    );
    parent::__construct();
  }

  function select() {
    $select = $this->_columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {

    $this->_from = "
         FROM  civicrm_contact {$this->_aliases['civicrm_contact']}
               INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                          ON {$this->_aliases['civicrm_contact']}.id =
                             {$this->_aliases['civicrm_contribution']}.contact_id
               INNER JOIN (
                  SELECT fa.name as civicrm_contact_financial_account,
                    fa.id as civicrm_contact_financial_account_id,
                    cc.id as contribution_id,
                    cc.currency as civicrm_contribution_currency,
                    SUM(fi.amount) as civicrm_contribution_amount_sum,
                    COUNT(DISTINCT fi.id) as fi_count,
                    ft.payment_instrument_id,
                    fa.accounting_code as gl_account_code
                  FROM civicrm_contribution cc
                  INNER JOIN civicrm_entity_financial_trxn eft_c ON cc.id = eft_c.entity_id AND eft_c.entity_table = 'civicrm_contribution' AND cc.is_test  = 0
                  INNER JOIN civicrm_financial_trxn ft ON eft_c.financial_trxn_id = ft.id
                  INNER JOIN civicrm_entity_financial_trxn eft_fi ON ft.id = eft_fi.financial_trxn_id AND eft_fi.entity_table = 'civicrm_financial_item'
                  INNER JOIN civicrm_financial_item fi ON eft_fi.entity_id = fi.id AND fi.entity_table = 'civicrm_line_item'
                  INNER JOIN civicrm_financial_account fa ON fi.financial_account_id = fa.id
                  GROUP BY fa.name, cc.id
                  HAVING SUM(fi.amount) <> 0
               ) temp  ON {$this->_aliases['civicrm_contribution']}.id = temp.contribution_id
     ";

  }

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY temp.civicrm_contact_financial_account";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY temp.civicrm_contact_financial_account ASC";
  }

  public function statistics(&$rows) {
      $statistics = parent::statistics($rows);
      $sql = "
      SELECT
      COUNT(DISTINCT {$this->_aliases['civicrm_contribution']}.id) as total_count,
      SUM({$this->_aliases['civicrm_contribution']}.total_amount) as amount,
      {$this->_aliases['civicrm_contribution']}.currency
       {$this->_from} {$this->_where} GROUP BY {$this->_aliases['civicrm_contribution']}.currency
      ";
      $dao = CRM_Core_DAO::executeQuery($sql);
      $amount = [];
      $count = 0;
      while ($dao->fetch()) {
       $amount[$dao->currency] = CRM_Utils_Money::format($dao->amount, $dao->currency) . " ($dao->total_count)";
       $count += $dao->total_count;
     }

     $statistics['counts']['count'] = [
       'value' => $count,
       'title' => ts('Total Contributions'),
       'type' => CRM_Utils_Type::T_STRING,
     ];
     $statistics['counts']['amount'] = [
       'value' => implode(', ', $amount),
       'title' => ts('Total Amount'),
       'type' => CRM_Utils_Type::T_STRING,
     ];

     return $statistics;
  }

  function alterDisplay(&$rows) {
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
