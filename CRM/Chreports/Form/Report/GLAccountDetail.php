<?php
use CRM_Chreports_ExtensionUtil as E;

class CRM_Chreports_Form_Report_GLAccountDetail extends CRM_Report_Form_Contribute_Bookkeeping {

  protected $_groupByDateFreq = [
    'MONTH' => 'Month',
    'YEARWEEK' => 'Week',
    'DATE' => 'Day',
    'QUARTER' => 'Quarter',
    'YEAR' => 'Year',
  ];

  public function __construct() {
    parent::__construct();
    $this->_columns['civicrm_contact']['fields'] = [
      'id' => [
        'no_display' => TRUE,
        'required' => TRUE,
      ],
    ];
    $this->_columns['civicrm_contact']['filters'] = [];
    $this->_columns['civicrm_contact']['order_bys'] = [];
    $this->_columns['civicrm_contact']['group_bys'] = [];
    unset($this->_columns['civicrm_membership']);
    unset($this->_columns['civicrm_batch']);
    $this->_columns['civicrm_financial_account']['group_bys'] = [
      'debit_name' => [
        'title' => ts('Financial Account Name - Debit'),
        'name' => 'name',
        'dbAlias' => 'financial_account_civireport_debit.name',
      ],
      'credit_name' => [
        'title' => ts('Financial Account Name - Credit'),
        'name' => 'name',
        'dbAlias' => 'civicrm_financial_account_credit_name',
      ],
    ];
    $this->_columns['civicrm_financial_trxn']['group_bys'] = [
      'trxn_date' => [
        'title' => ts('Transaction Date'),
        'frequency' => TRUE,
      ],
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
            if ($fieldName == 'trxn_date' && !empty($this->_params['group_bys'][$fieldName])) {
              switch ($this->_params['group_bys_freq'][$fieldName]) {
                case 'YEAR':
                  $field['dbAlias'] = "YEAR({$field['dbAlias']})";
                  $field['title'] = ts('Year Beginning');
                  break;

                case 'QUARTER':
                  $field['dbAlias'] = "YEAR({$field['dbAlias']}), QUARTER({$field['dbAlias']})";
                  $field['title'] = ts('Quarter');
                  break;

                case 'YEARWEEK':
                  $field['dbAlias'] = "DATE_SUB({$field['dbAlias']}, INTERVAL WEEKDAY({$field['dbAlias']}) DAY)";
                  $field['title'] = ts('Week Beginning');
                  break;

                case 'MONTH':
                  $field['dbAlias'] = "EXTRACT(YEAR_MONTH FROM {$field['dbAlias']})";
                  $field['title'] = ts('Month Beginning');
                  break;

                case 'DATE':
                  $field['dbAlias'] = "{$field['dbAlias']}";
                  break;
              }
              $select[] = $this->_params['group_bys_freq'][$fieldName] == 'YEARWEEK' ? "WEEK({$field['dbAlias']}) as {$tableName}_{$fieldName}_raw" : "{$fieldName} as {$tableName}_{$fieldName}_raw";
              $this->_columnHeaders["{$tableName}_{$fieldName}_raw"] = ['no_display' => TRUE];
            }
            switch ($fieldName) {
              case 'credit_accounting_code':
              case 'credit_name':
                $select[] = " CASE
                            WHEN {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id IS NOT NULL
                            THEN  {$this->_aliases['civicrm_financial_account']}_credit_1.{$field['name']}
                            ELSE  {$this->_aliases['civicrm_financial_account']}_credit_2.{$field['name']}
                            END AS civicrm_financial_account_{$fieldName} ";
                break;

              case 'amount':
                $select[] = " CASE
                            WHEN  {$this->_aliases['civicrm_entity_financial_trxn']}_item.entity_id IS NOT NULL
                            THEN {$this->_aliases['civicrm_entity_financial_trxn']}_item.amount
                            ELSE {$this->_aliases['civicrm_entity_financial_trxn']}.amount
                            END AS civicrm_entity_financial_trxn_amount ";
                break;

              case 'credit_contact_id':
                $select[] = " CASE
                            WHEN {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id IS NOT NULL
                            THEN  credit_contact_1.{$field['name']}
                            ELSE  credit_contact_2.{$field['name']}
                            END AS civicrm_financial_account_{$fieldName} ";
                break;

              default:
                $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
                break;
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
          }
        }
      }
    }
    $this->_selectClauses = $select;

    $this->_select = 'SELECT ' . implode(', ', $select) . ' ';
  }

  public function from() {
    $columnHeaders = [];
    foreach ([
      'civicrm_financial_account_debit_name',
      'civicrm_financial_account_debit_accounting_code',
      'civicrm_financial_account_credit_name',
      'civicrm_financial_account_credit_accounting_code',
    ] as $name) {
      if (array_key_exists($name, $this->_columnHeaders)) {
        $columnHeaders[$name] = $this->_columnHeaders[$name];
        unset($this->_columnHeaders[$name]);
      }
    }
    $this->_columnHeaders = array_merge($columnHeaders, $this->_columnHeaders);

    $this->_from = "FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
              INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                    ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND
                         {$this->_aliases['civicrm_contribution']}.is_test = 0
              LEFT JOIN civicrm_membership_payment payment
                    ON ( {$this->_aliases['civicrm_contribution']}.id = payment.contribution_id )
              LEFT JOIN civicrm_entity_financial_trxn {$this->_aliases['civicrm_entity_financial_trxn']}
                    ON ({$this->_aliases['civicrm_contribution']}.id = {$this->_aliases['civicrm_entity_financial_trxn']}.entity_id AND
                        {$this->_aliases['civicrm_entity_financial_trxn']}.entity_table = 'civicrm_contribution')
              LEFT JOIN civicrm_financial_trxn {$this->_aliases['civicrm_financial_trxn']}
                    ON {$this->_aliases['civicrm_financial_trxn']}.id = {$this->_aliases['civicrm_entity_financial_trxn']}.financial_trxn_id
              LEFT JOIN civicrm_financial_account {$this->_aliases['civicrm_financial_account']}_debit
                    ON {$this->_aliases['civicrm_financial_trxn']}.to_financial_account_id = {$this->_aliases['civicrm_financial_account']}_debit.id
              LEFT JOIN civicrm_contact debit_contact ON {$this->_aliases['civicrm_financial_account']}_debit.contact_id = debit_contact.id
              LEFT JOIN civicrm_financial_account {$this->_aliases['civicrm_financial_account']}_credit_1
                    ON {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id = {$this->_aliases['civicrm_financial_account']}_credit_1.id
              LEFT JOIN civicrm_contact credit_contact_1 ON {$this->_aliases['civicrm_financial_account']}_credit_1.contact_id = credit_contact_1.id
              LEFT JOIN civicrm_entity_financial_trxn {$this->_aliases['civicrm_entity_financial_trxn']}_item
                    ON ({$this->_aliases['civicrm_financial_trxn']}.id = {$this->_aliases['civicrm_entity_financial_trxn']}_item.financial_trxn_id AND
                        {$this->_aliases['civicrm_entity_financial_trxn']}_item.entity_table = 'civicrm_financial_item')
              LEFT JOIN civicrm_financial_item fitem
                    ON fitem.id = {$this->_aliases['civicrm_entity_financial_trxn']}_item.entity_id
              LEFT JOIN civicrm_financial_account {$this->_aliases['civicrm_financial_account']}_credit_2
                    ON fitem.financial_account_id = {$this->_aliases['civicrm_financial_account']}_credit_2.id
              LEFT JOIN civicrm_contact credit_contact_2 ON {$this->_aliases['civicrm_financial_account']}_credit_2.contact_id = credit_contact_2.id
              LEFT JOIN civicrm_line_item {$this->_aliases['civicrm_line_item']}
                    ON  fitem.entity_id = {$this->_aliases['civicrm_line_item']}.id AND fitem.entity_table = 'civicrm_line_item'
              ";
  }

  function groupBy() {
    $groupBys = [
      "{$this->_aliases['civicrm_entity_financial_trxn']}.id",
      "{$this->_aliases['civicrm_line_item']}.id",
    ];
    $params = CRM_Utils_Array::value('group_bys', $this->_params);
    if (!empty($params)) {
      foreach ($params as $groupBy => $dontCare) {
        $alias = $groupBy == 'trxn_date' ? $this->_aliases['civicrm_financial_trxn'] : $this->_aliases['civicrm_financial_account'];
        if ($groupBy == 'trxn_date') {
          if (!empty($table['group_bys'][$groupBy]['frequency']) &&
            !empty($this->_params['group_bys_freq'][$groupBy])
          ) {
            switch ($this->_params['group_bys_freq'][$groupBy]) {
              case 'YEAR':
                $groupBys[] = " YEAR({$alias}.{$groupBy})";
                break;

              case 'QUARTER':
                $groupBys[] = "YEAR({$alias}.{$groupBy}), QUARTER({$alias}.{$groupBy})";
                break;

              case 'YEARWEEK':
                $groupBys[] = "YEARWEEK({$alias}.{$groupBy})";
                break;

              case 'MONTH':
                $groupBys[] = "EXTRACT(YEAR_MONTH FROM {$alias}.{$groupBy})";
                break;

              case 'DATE':
                $groupBys[] = "{$alias}.{$groupBy}";
                break;
            }
          }
        }
        else {
          $groupBys[] = $this->_columns['civicrm_financial_account']['group_bys'][$groupBy]['dbAlias'];
        }
      }
    }
    $this->_groupBy = " GROUP BY " . implode(', ', $groupBys);
  }

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
      $financialSelect,
    ];
    $select = "SELECT " . implode(', ', $this->_selectClauses);

    $groupBy = [
      "{$this->_aliases['civicrm_entity_financial_trxn']}.id",
      "{$this->_aliases['civicrm_line_item']}.id",
    ];
    $groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);

    $tempTableName = $this->createTemporaryTable('tempTable', "
                  {$select} {$this->_from} {$this->_where} {$groupBy} ");

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

    $statistics['counts']['amount'] = [
      'value' => implode(', ', $amount),
      'title' => ts('Total Amount'),
      'type' => CRM_Utils_Type::T_STRING,
    ];
    $statistics['counts']['avg'] = [
      'value' => implode(', ', $avg),
      'title' => ts('Average'),
      'type' => CRM_Utils_Type::T_STRING,
    ];
    return $statistics;
  }

}
