<?php
use CRM_Chreports_ExtensionUtil as E;

class CRM_Chreports_Form_Report_GLAccountDetail extends CRM_Report_Form_Contribute_Bookkeeping {

  public function __construct() {
    parent::__construct();
    $this->_columns['civicrm_financial_account']['fields']['financial_account'] = [
      'title' => ts('Financial Account'),
      'dbAlias' => 'financial_account_civireport_credit_2.name',
    ];
    $this->_columns['civicrm_contribution']['fields']['contact_count'] = [
      'title' => ts('Total Contacts'),
      'no_display' => TRUE,
      'required' => TRUE,
      'dbAlias' => 'COUNT(DISTINCT contribution_civireport.contact_id)',
    ];
    $this->_columns['civicrm_contribution']['fields']['contri_count'] = [
        'title' => ts('Total Contributions'),
        'no_display' => TRUE,
        'required' => TRUE,
        'dbAlias' => 'COUNT(DISTINCT contribution_civireport.id)',

    ];
  }

  public function select() {
    $select = [];

    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            switch ($fieldName) {
              case 'amount':
                $select[] = "SUM({$this->_aliases['civicrm_entity_financial_trxn']}.amount) AS civicrm_entity_financial_trxn_amount";
                break;

              default:
                $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
                break;
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }
    $this->_selectClauses = $select;

    $this->_select = 'SELECT ' . implode(', ', $select) . ' ';
  }

  public function groupBy() {
    $this->_rollUp = " WITH ROLLUP";
    $groupBy = [
      "financial_account_civireport_credit_2.name",
      "{$this->_aliases['civicrm_financial_trxn']}.payment_instrument_id",
      "{$this->_aliases['civicrm_contribution']}.id",
    ];
    $this->_groupBy = "GROUP BY " . implode(", ", $groupBy) . $this->_rollUp;
  }

  public function orderBy() {}

  /**
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $financialSelect = "CASE WHEN {$this->_aliases['civicrm_entity_financial_trxn']}_item.entity_id IS NOT NULL
            THEN {$this->_aliases['civicrm_entity_financial_trxn']}_item.amount
            ELSE {$this->_aliases['civicrm_entity_financial_trxn']}.amount
            END as amount";

    $this->_selectClauses = [
      "{$this->_aliases['civicrm_contribution']}.id",
      "{$this->_aliases['civicrm_entity_financial_trxn']}.id as trxnID",
      "{$this->_aliases['civicrm_contribution']}.currency",
      "{$this->_aliases['civicrm_financial_trxn']}.payment_instrument_id",
      $financialSelect,
    ];
    $select = "SELECT " . implode(', ', $this->_selectClauses);

    $this->groupBy();
    $this->_groupBy = str_replace('WITH ROLLUP', '', $this->_groupBy);
    $tempTableName = $this->createTemporaryTable('tempTable', "
                  {$select} {$this->_from} {$this->_where} {$this->_groupBy} ");

    $sql = "SELECT SUM(amount) as amount, currency, ov.label as payment_instrument
            FROM {$tempTableName} temp
            INNER JOIN civicrm_option_value ov ON ov.value = temp.payment_instrument_id
            INNER JOIN civicrm_option_group og ON og.id = ov.option_group_id AND og.name = 'payment_instrument'
            GROUP BY ov.label";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $amount = [];
    while ($dao->fetch()) {
      if (!array_key_exists($dao->payment_instrument, $amount)) {
        $amount[$dao->payment_instrument] = [];
      }
      $amount[$dao->payment_instrument][$dao->currency] = CRM_Utils_Money::format($dao->amount, $dao->currency);
    }

    foreach ($amount as $paymentInstrument => $amounts) {
      $statistics['counts'][$paymentInstrument] = [
        'value' => implode(', ', $amounts),
        'title' => ts('%1 Total', [1 => $paymentInstrument]),
        'type' => CRM_Utils_Type::T_STRING,
      ];
    }

    $sql = "SELECT COUNT(trxnID) as count, SUM(amount) as amount, currency
            FROM {$tempTableName}
            GROUP BY currency";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $amount = $avg = [];
    while ($dao->fetch()) {
      $amount[] = CRM_Utils_Money::format($dao->amount, $dao->currency);
      $avg[] = CRM_Utils_Money::format(round(($dao->amount /
        $dao->count), 2), $dao->currency);
    }

    $statistics['counts']['SEPARATOR'] = [
      'value' => "",
      'title' => "<br/>",
      'type' => CRM_Utils_Type::T_STRING,
    ];
    $statistics['counts']['amount'] = [
      'value' => implode(', ', $amount),
      'title' => ts('All Total'),
      'type' => CRM_Utils_Type::T_STRING,
    ];


    $columnHeaders = [];
    foreach ([
      'civicrm_financial_account_financial_account',
      'civicrm_financial_trxn_payment_instrument_id',
      'civicrm_contact_exposed_id',
      'civicrm_contact_sort_name',
      'civicrm_contribution_receive_date',
      'civicrm_entity_financial_trxn_amount',
      'civicrm_financial_trxn_check_number',
      'civicrm_financial_trxn_trxn_id',
      'civicrm_financial_trxn_card_type_id',
      'civicrm_contribution_contribution_source',
    ] as $name) {
      if (array_key_exists($name, $this->_columnHeaders)) {
        $columnHeaders[$name] = $this->_columnHeaders[$name];
        unset($this->_columnHeaders[$name]);
      }
    }
    $this->_columnHeaders = array_merge($columnHeaders, $this->_columnHeaders);

    return $statistics;
  }

  public function alterDisplay(&$rows) {
    $contributionTypes = CRM_Contribute_PseudoConstant::financialType();
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'label');
    $creditCardTypes = CRM_Financial_DAO_FinancialTrxn::buildOptions('card_type_id');
    foreach ($rows as $rowNum => $row) {
      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        !empty($rows[$rowNum]['civicrm_contact_sort_name']) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $contactID = $row['civicrm_contact_id'] ?: $row['civicrm_contact_exposed_id'];
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $contactID,
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts('View Contact Summary for this Contact.');
      }

      // handle contribution status id
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = $contributionStatus[$value];
      }

      // handle payment instrument id
      if ($value = CRM_Utils_Array::value('civicrm_financial_trxn_payment_instrument_id', $row)) {
        $rows[$rowNum]['civicrm_financial_trxn_payment_instrument_id'] = $paymentInstruments[$value];
      }

      // handle financial type id
      if ($value = CRM_Utils_Array::value('civicrm_line_item_financial_type_id', $row)) {
        $rows[$rowNum]['civicrm_line_item_financial_type_id'] = $contributionTypes[$value];
      }
      if ($value = CRM_Utils_Array::value('civicrm_entity_financial_trxn_amount', $row)) {
        $rows[$rowNum]['civicrm_entity_financial_trxn_amount'] = CRM_Utils_Money::format($rows[$rowNum]['civicrm_entity_financial_trxn_amount'], $rows[$rowNum]['civicrm_financial_trxn_currency']);
      }

      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = CRM_Utils_Array::value($row['civicrm_financial_trxn_card_type_id'], $creditCardTypes);
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, NULL, NULL) ? TRUE : $entryFound;

      if (empty($row['civicrm_financial_trxn_payment_instrument_id'])) {
        if (empty($row['civicrm_financial_account_financial_account'])) {
          $rows[$rowNum]['civicrm_financial_trxn_payment_instrument_id'] = sprintf("<strong>%s</strong>", ts("All Total"));
        }
        else {
          $rows[$rowNum]['civicrm_financial_trxn_payment_instrument_id'] = sprintf("<strong>%s</strong>", ts("All Payment Methods Total"));
        }
        foreach ($row as $key => $value) {
          if ($key == 'civicrm_contact_exposed_id') {
            $rows[$rowNum][$key] = $row['civicrm_contribution_contact_count'];
          }
          elseif (!in_array($key, ['civicrm_entity_financial_trxn_amount', 'civicrm_financial_trxn_currency', 'civicrm_contribution_contri_count', 'civicrm_financial_trxn_payment_instrument_id'])) {
            $rows[$rowNum][$key] = NULL;
          }
        }
      }

    }
  }

}
