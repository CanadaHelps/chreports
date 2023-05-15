<?php
use CRM_Chreports_ExtensionUtil as E;

class CRM_Chreports_Form_Report_RetentionRate extends CRM_Report_Form {

  protected $_customGroupGroupBy = FALSE;
  protected $_baseYear;
  public function __construct() {
    $baseYears = function () {
       $y = [];
       $i = (int) date('Y');
       $count = 0;
       while($count <= 10) {
         $y[($i - $count)] = $i - $count;
         $count ++;
       }
       return $y;
    };
    $this->_baseYear = $_POST['base_year_value'] ?? (date('Y') - 5);
    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'filters' => [
          'contact_type' =>[
            'title' => E::ts('Contact Type'),
          ],
          'contact_sub_type' =>[
            'title' => E::ts('Contact Subtype'),
          ],
        ],
      ],
      'civicrm_address' => [
        'dao' => 'CRM_Core_DAO_Address',
        'filters' => [
          'postal_code' =>[
            'title' => E::ts('Postal Code'),
          ],
          'city' =>[
            'title' => E::ts('City'),
          ],
          'country_id' => [
            'name' => 'country_id',
            'title' => ts('Country'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::country(),
          ],
          'state_province_id' => [
            'name' => 'state_province_id',
            'title' => ts('Province'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => [],
          ],
          'county_id' => [
            'name' => 'county_id',
            'title' => ts('County'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => [],
          ],
        ],
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_BAO_Contribution',
        'filters' => [
          'base_year' => [
            'title' => ts('Base Year'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $baseYears(),
            'default' => 2016,
            'type' => CRM_Utils_Type::T_INT,
          ],
          'financial_type_id' => [
            'title' => ts('Fund'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search'),
            'type' => CRM_Utils_Type::T_INT,
          ],
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
            'default' => [1],
            'type' => CRM_Utils_Type::T_INT,
          ],
          'source' => [
            'title' => E::ts('Source'),
          ],
          'contribution_page_id' => [
            'title' => ts('Contribution Page'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionPage(),
            'type' => CRM_Utils_Type::T_INT,
          ],
        ],
      ],
    ];
    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    unset($this->_columns['civicrm_contribution']['fields']['campaign_id']);

    $baseYear = $this->_baseYear;
    $prevYear = $baseYear - 1;
    $this->_columns['civicrm_contact']['fields'] = [
      'retention_rate' => [
        'title' => ' ',
        'required' => TRUE,
        'dbAlias' => 'temp.count_total',
      ],
      $prevYear => [
        'title' => 'Year ' . ' - ' . $prevYear,
        'no_display' => TRUE,
        'required' => TRUE,
        'dbAlias' => 'temp.' . $prevYear,
      ],
      'contact_id' => [
        'title' => 'Contact ID',
        'dbAlias' => 'temp.contact_id',
        'no_display' => TRUE,
        'required' => TRUE,
      ],
    ];
    $currentYear = (int) date('Y');
    for ($i = $baseYear; $i <= $currentYear; $i++) {
      $this->_columns['civicrm_contact']['fields'][$i] = [
        'title' => $i,
        'required' => TRUE,
        'dbAlias' => 'temp.' . $i,
      ];
    }
    parent::__construct();
    unset($this->_columns['civicrm_contact']['fields']['exposed_id']);
  }

  function from() {
    $baseYear = $this->_baseYear;
    $currentYear = (int) date('Y');
    $select = ["(SELECT count(id) FROM civicrm_contribution WHERE YEAR(receive_date) >= '{$baseYear}' AND YEAR(receive_date) <= '{$currentYear}' AND contact_id = cc.contact_id) as count_total"];
    for ($i = $baseYear - 1; $i <= $currentYear; $i++) {
      $select[] = "(SELECT count(id) FROM civicrm_contribution WHERE YEAR(receive_date) = '{$i}' AND contact_id = cc.contact_id) as `$i`";
    }
    $sql = 'SELECT contact_id, ' . implode(', ', $select) . "
      FROM civicrm_contribution cc
      WHERE YEAR(receive_date) >= '{$baseYear}'
      GROUP BY contact_id ";

    $this->_from = "
         FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
               INNER JOIN (
                 $sql
               ) temp ON temp.contact_id = {$this->_aliases['civicrm_contact']}.id
               INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                          ON {$this->_aliases['civicrm_contact']}.id =
                             {$this->_aliases['civicrm_contribution']}.contact_id AND {$this->_aliases['civicrm_contribution']}.is_test = 0
      ";

    $this->joinAddressFromContact();
    $this->joinEmailFromContact();
    $this->joinPhoneFromContact();
  }

  public function storeWhereHavingClauseArray() {
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          // respect pseudofield to filter spec so fields can be marked as
          // not to be handled here
          if (!empty($field['pseudofield']) || $field['name'] == 'base_year') {
            continue;
          }
          $clause = $this->generateFilterClause($field, $fieldName);

          if (!empty($clause)) {
            if (!empty($field['having'])) {
              $this->_havingClauses[] = $clause;
            }
            else {
              $this->_whereClauses[] = $clause;
            }
          }
        }
      }
    }

  }

  public function postProcess() {
    $this->beginPostProcess();

    $this->buildGroupTempTable();
    $this->select();
    $this->from();
    $this->customDataFrom();
    $this->where();

    // build array of result based on column headers. This method also allows
    // modifying column headers before using it to build result set i.e $rows.
    $rows = [];
    $sql = "{$this->_select} {$this->_from} {$this->_where}";
    $this->addToDeveloperTab($sql);
    $this->buildRows($sql, $rows);

    // format result set.
    $this->formatDisplay($rows);

    // assign variables to templates
    $this->doTemplateAssignment($rows);

    // do print / pdf / instance stuff if needed
    $this->endPostProcess($rows);
  }

  public function alterDisplay(&$rows) {
    //CRM_Core_Error::debug_var('column', $this->_columnHeaders);
    //CRM_Core_Error::debug_var('rows', $rows);
    $declareRow = $rows[0];
    $currentYear = date('Y');
    while($currentYear >= $this->_baseYear) {
      $declareRow['civicrm_contact_' . $currentYear] = 0;
      $declareRow['civicrm_contact_contact_id'] = [];
      $currentYear -= 1;
    }
    $newRows = [
      array_merge($declareRow, ['civicrm_contact_retention_rate' => 'Repeat Donors']),
      array_merge($declareRow, ['civicrm_contact_retention_rate' => 'New Donors']),
      array_merge($declareRow, ['civicrm_contact_retention_rate' => 'Retention Rate']),
    ];
    $baseYear = $this->_baseYear;
    $currentYear = (int) date('Y');
    foreach ($rows as $id => $row) {
      if ($row['civicrm_contact_retention_rate'] == 0) {
        continue;
      }
      for ($i = $baseYear; $i <= $currentYear; $i++) {
        if (!is_array($newRows[1]['civicrm_contact_contact_id_' . $i]))
          $newRows[1]['civicrm_contact_contact_id_' . $i] = [];
        if ($row['civicrm_contact_' . $i] > 0 && $row['civicrm_contact_' . ($i - 1)] == 0 && !in_array($row['civicrm_contact_contact_id'], $newRows[1]['civicrm_contact_contact_id_' . $i])) {
          $newRows[1]['civicrm_contact_' . $i] += 1;
          $newRows[1]['civicrm_contact_contact_id_'. $i][] = $row['civicrm_contact_contact_id'];
        }
        if ($row['civicrm_contact_' . $i] > 0 && $row['civicrm_contact_' . ($i - 1)] > 0 && !in_array($row['civicrm_contact_contact_id'], $newRows[0]['civicrm_contact_contact_id_' . $i])) {
          $newRows[0]['civicrm_contact_' . $i] += 1;
          $newRows[0]['civicrm_contact_contact_id_' . $i][] = $row['civicrm_contact_contact_id'];
        }
      }
    }
    for ($i = $baseYear; $i <= $currentYear; $i++) {
      if ($i == $baseYear) {
          $newRows[2]['civicrm_contact_' . $i] = '0.00%';
      }
      else {
        $reminder = ($newRows[0]['civicrm_contact_' . ($i - 1)] + $newRows[1]['civicrm_contact_' . ($i - 1)]);
        if ($reminder == 0) {
          $newRows[2]['civicrm_contact_' . $i] = '0.00%';
        }
        else {
          $newRows[2]['civicrm_contact_' . $i] = round(($newRows[0]['civicrm_contact_' . $i] / $reminder) * 100, 2) . '%';
        }
      }
      $newRows[2]['civicrm_contact_' . $i] = sprintf('<span class="count-link">%s</span>', $newRows[2]['civicrm_contact_' . $i]);
    }
    foreach ($newRows as $key => $newRow) {
      if ($key != 2) {
        for ($i = $baseYear; $i <= $currentYear; $i++) {
          if ($newRow['civicrm_contact_' . $i] > 0) {
            $newRows[$key]['civicrm_contact_' . $i] = sprintf('<a class="count-link" target="_blank" href="%s">%s</a>',
              CRM_Utils_System::url('civicrm/report/contact/summary', sprintf('id_value=%s&id_op=in&force=1', implode(',', $newRow['civicrm_contact_contact_id_' . $i]))), $newRow['civicrm_contact_' . $i]);
          }
        }
      }
    }
    $rows = $newRows;
  }

}
