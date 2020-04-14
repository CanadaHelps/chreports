<?php
use CRM_Chreports_ExtensionUtil as E;

class CRM_Chreports_Form_Report_GLAccountDetail extends CRM_Report_Form {

  public function __construct() {
    parent::__construct();

    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'id' => array(
            'title' => E::ts('Donor ID'),
            'default' => TRUE,
          ),
          'sort_name' => array(
            'title' => E::ts('Donor Name'),
            'default' => TRUE,
            'no_repeat' => TRUE,
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
          'total_contact' => [
            'title' => ts('Total Contacts'),
            'no_display' => TRUE,
            'required' => TRUE,
            'dbAlias' => 'COUNT(DISTINCT contribution_civireport.contact_id)',
          ],
          'source' => [
            'title' => ts('Source'),
            'default' => TRUE,
          ],
          'receive_date' => [
            'title' => ts('Donation Date'),
            'default' => TRUE,
            'type' => CRM_Utils_TYPE::T_DATE + CRM_Utils_Type::T_TIME,
          ],
          'gl_account' => [
            'title' => E::ts('Financial Account'),
            'default' => TRUE,
            'required' => TRUE,
            'dbAlias' => 'temp.civicrm_contact_financial_account',
          ],
          'count' => [
            'title' => E::ts('Number of Contributions'),
            'type' => CRM_Utils_TYPE::T_INT,
            'no_display' => TRUE,
            'required' => TRUE,
            'dbAlias' => 'COUNT(DISTINCT contribution_civireport.id)',
          ],
          'gl_amount' => [
            'title' => E::ts('Donation Amount'),
            'default' => TRUE,
            'type' => CRM_Utils_TYPE::T_MONEY,
            'dbAlias' => 'SUM(temp.civicrm_contribution_amount_sum)',
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
        ],
        'grouping' => 'contribute-fields',
      ],
      'civicrm_financial_trxn' => [
        'dao' => 'CRM_Core_BAO_FinancialTrxn',
        'fields' => [
          'payment_instrument_id' => [
            'title' => E::ts('Payment Method'),
            'default' => TRUE,
          ],
          'check_number' => [
            'title' => E::ts('Cheque #'),
            'default' => TRUE,
          ],
          'trxn_id' => [
            'title' => E::ts('Transaction #'),
            'default' => TRUE,
          ],
          'card_type_id' => [
            'title' => E::ts('Credit Card Type'),
            'default' => TRUE,
          ],
        ],
      ],
    );
  }

  function from() {

    $this->_from = "
         FROM  civicrm_contact {$this->_aliases['civicrm_contact']}
               INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                          ON {$this->_aliases['civicrm_contact']}.id =
                             {$this->_aliases['civicrm_contribution']}.contact_id
               INNER JOIN (
                  SELECT fa.name as civicrm_contact_financial_account,
                    cc.id as contribution_id,
                    cc.currency as civicrm_contribution_currency,
                    SUM(fi.amount) as civicrm_contribution_amount_sum,
                    COUNT(DISTINCT fi.id) as fi_count,
                    ft.id
                  FROM civicrm_contribution cc
                  INNER JOIN civicrm_entity_financial_trxn eft_c ON cc.id = eft_c.entity_id AND eft_c.entity_table = 'civicrm_contribution' AND cc.is_test  = 0
                  INNER JOIN civicrm_financial_trxn ft ON eft_c.financial_trxn_id = ft.id
                  INNER JOIN civicrm_entity_financial_trxn eft_fi ON ft.id = eft_fi.financial_trxn_id AND eft_fi.entity_table = 'civicrm_financial_item'
                  INNER JOIN civicrm_financial_item fi ON eft_fi.entity_id = fi.id AND fi.entity_table = 'civicrm_line_item'
                  INNER JOIN civicrm_financial_account fa ON fi.financial_account_id = fa.id
                  GROUP BY fa.name, cc.id
                  HAVING SUM(fi.amount) <> 0
               ) temp  ON {$this->_aliases['civicrm_contribution']}.id = temp.contribution_id
             INNER JOIN civicrm_financial_trxn {$this->_aliases['civicrm_financial_trxn']} ON {$this->_aliases['civicrm_financial_trxn']}.id = temp.id
     ";

  }

  public function groupBy() {
    $this->_rollUp = " WITH ROLLUP";
    $groupBy = [
      "temp.civicrm_contact_financial_account",
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
    $statistics = parent::statistics($rows);
    $sql = "
      SELECT
      ov.label as payment_instrument,
      SUM(temp.civicrm_contribution_amount_sum) as amount,
      {$this->_aliases['civicrm_contribution']}.currency
       {$this->_from}
       INNER JOIN civicrm_option_value ov ON ov.value = {$this->_aliases['civicrm_financial_trxn']}.payment_instrument_id
       INNER JOIN civicrm_option_group og ON og.id = ov.option_group_id AND og.name = 'payment_instrument'
        {$this->_where} GROUP BY ov.label
      ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $amount = [];
    $totalAmount = 0;
    while ($dao->fetch()) {
      if (!array_key_exists($dao->payment_instrument, $amount)) {
        $amount[$dao->payment_instrument] = [];
      }
      $amount[$dao->payment_instrument][$dao->currency] = CRM_Utils_Money::format($dao->amount, $dao->currency);
      $totalAmount += $dao->amount ?: 0;
    }

    foreach ($amount as $paymentInstrument => $amounts) {
      $statistics['counts'][$paymentInstrument] = [
        'value' => implode(', ', $amounts),
        'title' => ts('%1 Total', [1 => $paymentInstrument]),
        'type' => CRM_Utils_Type::T_STRING,
      ];
    }

    $statistics['counts']['SEPARATOR'] = [
      'value' => "",
      'title' => "<br/>",
      'type' => CRM_Utils_Type::T_STRING,
    ];
    $statistics['counts']['amount'] = [
      'value' => CRM_Utils_Money::format($totalAmount, 'CAD'),
      'title' => ts('All Total'),
      'type' => CRM_Utils_Type::T_STRING,
    ];

    $columnHeaders = [];
    foreach ([
      'civicrm_contribution_gl_account',
      'civicrm_financial_trxn_payment_instrument_id',
      'civicrm_contact_id',
      'civicrm_contact_sort_name',
      'civicrm_contribution_receive_date',
      'civicrm_contribution_gl_amount',
      'civicrm_financial_trxn_check_number',
      'civicrm_financial_trxn_trxn_id',
      'civicrm_financial_trxn_card_type_id',
      'civicrm_contribution_source',
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
      }

      if (empty($row['civicrm_contribution_id'])) {
        if (empty($row['civicrm_contribution_gl_account'])) {
          $rows[$rowNum]['civicrm_financial_trxn_payment_instrument_id'] = sprintf("<strong>%s</strong>", ts("All Total"));
        }
        elseif (empty($row['civicrm_financial_trxn_payment_instrument_id'])) {
          $rows[$rowNum]['civicrm_financial_trxn_payment_instrument_id'] = sprintf("<strong>%s</strong>", ts("All Payment Methods Total"));
        }
        else {
          $rows[$rowNum]['civicrm_financial_trxn_payment_instrument_id'] = sprintf("<strong>%s total </strong>", $rows[$rowNum]['civicrm_financial_trxn_payment_instrument_id']);
        }
        foreach ($row as $key => $value) {
          if ($key == 'civicrm_contact_sort_name') {
            $rows[$rowNum][$key] = sprintf("<strong>%s</strong>", $row['civicrm_contribution_total_contact']);
            $rows[$rowNum]['civicrm_contact_sort_name_link'] = $rows[$rowNum]['civicrm_contact_sort_name_hover'] = NULL;
          }
          elseif($key == 'civicrm_contribution_gl_amount') {
            $rows[$rowNum][$key] = sprintf("<strong>%s</strong>", CRM_Utils_Money::format($row['civicrm_contribution_gl_amount'], NULL, '%a'));
          }
          elseif (!in_array($key, ['civicrm_contribution_gl_amount', 'civicrm_financial_trxn_currency', 'civicrm_financial_trxn_payment_instrument_id'])) {
            if ($key == 'civicrm_contribution_gl_account' && !empty($row['civicrm_financial_trxn_payment_instrument_id'])) {
              continue;
            }
            $rows[$rowNum][$key] = NULL;
          }
        }
      }
    }
  }

}
