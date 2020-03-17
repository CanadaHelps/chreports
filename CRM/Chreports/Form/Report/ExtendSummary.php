<?php
use CRM_Chreports_ExtensionUtil as E;

class CRM_Chreports_Form_Report_ExtendSummary extends CRM_Report_Form_Contribute_Summary {

  public function groupBy() {
    parent::groupBy();
    $this->_groupBy = str_replace($this->_rollup, '', $this->_groupBy);
    $this->_rollup = '';
  }

  public function from($entity = NULL) {
    parent::from($entity);
    if (!strstr($this->_from, 'civicrm_line_item li') && array_key_exists('financial_account', $this->_params['group_bys'])) {
      // replace the core select columns to use respective columns
      $this->_select = str_replace("SUM({$this->_aliases['civicrm_contribution']}.total_amount)", "SUM(IF(ISNULL(ft.from_financial_account_id), fi.amount, -fi.amount))", $this->_select);
      $this->_select = str_replace("COUNT({$this->_aliases['civicrm_contribution']}.total_amount)", "COUNT(DISTINCT {$this->_aliases['civicrm_contribution']}.id)", $this->_select);

      $this->_from .= "
      INNER JOIN civicrm_entity_financial_trxn eft_c ON contribution_civireport.id = eft_c.entity_id AND eft_c.entity_table = 'civicrm_contribution'
      INNER JOIN civicrm_financial_trxn ft ON eft_c.financial_trxn_id = ft.id
      INNER JOIN civicrm_entity_financial_trxn eft_fi ON ft.id = eft_fi.financial_trxn_id AND eft_fi.entity_table = 'civicrm_financial_item'
      INNER JOIN civicrm_financial_item fi ON eft_fi.entity_id = fi.id AND fi.entity_table = 'civicrm_line_item'
      INNER JOIN civicrm_financial_account fa ON fi.financial_account_id = fa.id
      ";
    }
    $tablename = E::getTableNameByName('Campaign_Information');
    if (!empty($tableName)) {
      $from .= "
      LEFT JOIN $tableName ct ON ct.entity_id = contribution_civireport.contribution_page_id
      ";
    }
  }

  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    if (!isset($this->_groupByArray['civicrm_contribution_currency'])) {
      $this->_groupByArray['civicrm_contribution_currency'] = 'currency';
    }
    $group = ' GROUP BY ' . implode(', ', $this->_groupByArray);

    $this->from('contribution');
    $this->customDataFrom();

    // Ensure that Extensions that modify the from statement in the sql also modify it in the statistics.
    CRM_Utils_Hook::alterReportVar('sql', $this, $this);

    $contriQuery = "
COUNT({$this->_aliases['civicrm_contribution']}.total_amount )        as civicrm_contribution_total_amount_count,
SUM({$this->_aliases['civicrm_contribution']}.total_amount )          as civicrm_contribution_total_amount_sum,
ROUND(AVG({$this->_aliases['civicrm_contribution']}.total_amount), 2) as civicrm_contribution_total_amount_avg,
{$this->_aliases['civicrm_contribution']}.currency                    as currency
{$this->_from} {$this->_where}";

if (!strstr($this->_from, 'civicrm_line_item li') && array_key_exists('financial_account', $this->_params['group_bys'])) {
  $contriQuery = "
COUNT(DISTINCT {$this->_aliases['civicrm_contribution']}.id )        as civicrm_contribution_total_amount_count,
SUM(IF(ISNULL(ft.from_financial_account_id), fi.amount, -fi.amount))          as civicrm_contribution_total_amount_sum,
ROUND(AVG(IF(ISNULL(ft.from_financial_account_id), fi.amount, -fi.amount)), 2) as civicrm_contribution_total_amount_avg,
{$this->_aliases['civicrm_contribution']}.currency                    as currency
{$this->_from} {$this->_where}";
}

    $contriSQL = "SELECT {$contriQuery} {$group} {$this->_having}";
    $contriDAO = CRM_Core_DAO::executeQuery($contriSQL);
    $this->addToDeveloperTab($contriSQL);
    $currencies = $currAmount = $currAverage = $currCount = [];
    $totalAmount = $average = $mode = $median = [];
    $averageCount = [];
    $count = 0;
    while ($contriDAO->fetch()) {
      if (!isset($currAmount[$contriDAO->currency])) {
        $currAmount[$contriDAO->currency] = 0;
      }
      if (!isset($currCount[$contriDAO->currency])) {
        $currCount[$contriDAO->currency] = 0;
      }
      if (!isset($currAverage[$contriDAO->currency])) {
        $currAverage[$contriDAO->currency] = 0;
      }
      if (!isset($averageCount[$contriDAO->currency])) {
        $averageCount[$contriDAO->currency] = 0;
      }
      $currAmount[$contriDAO->currency] += $contriDAO->civicrm_contribution_total_amount_sum;
      $currCount[$contriDAO->currency] += $contriDAO->civicrm_contribution_total_amount_count;
      $currAverage[$contriDAO->currency] += $contriDAO->civicrm_contribution_total_amount_avg;
      $averageCount[$contriDAO->currency]++;
      $count += $contriDAO->civicrm_contribution_total_amount_count;

      if (!in_array($contriDAO->currency, $currencies)) {
        $currencies[] = $contriDAO->currency;
      }
    }

    foreach ($currencies as $currency) {
      $totalAmount[] = CRM_Utils_Money::format($currAmount[$currency], $currency) .
        " (" . $currCount[$currency] . ")";
      $average[] = CRM_Utils_Money::format(($currAverage[$currency] / $averageCount[$currency]), $currency);
    }

    $groupBy = "\n{$group}, {$this->_aliases['civicrm_contribution']}.total_amount";
    $orderBy = "\nORDER BY civicrm_contribution_total_amount_count DESC";
    $modeSQL = "SELECT MAX(civicrm_contribution_total_amount_count) as civicrm_contribution_total_amount_count,
      SUBSTRING_INDEX(GROUP_CONCAT(amount ORDER BY mode.civicrm_contribution_total_amount_count DESC SEPARATOR ';'), ';', 1) as amount,
      currency
      FROM (SELECT {$this->_aliases['civicrm_contribution']}.total_amount as amount,
    {$contriQuery} {$groupBy} {$orderBy}) as mode GROUP BY currency";

    $mode = $this->calculateMode($modeSQL);
    $median = $this->calculateMedian();

      $statistics['counts']['amount'] = [
        'title' => ts('Total Amount'),
        'value' => implode(',  ', $totalAmount),
        'type' => CRM_Utils_Type::T_STRING,
      ];
      $statistics['counts']['count'] = [
        'title' => ts('Total Contributions'),
        'value' => $count,
      ];
      $statistics['counts']['avg'] = [
        'title' => ts('Average'),
        'value' => implode(',  ', $average),
        'type' => CRM_Utils_Type::T_STRING,
      ];
      $statistics['counts']['mode'] = [
        'title' => ts('Mode'),
        'value' => implode(',  ', $mode),
        'type' => CRM_Utils_Type::T_STRING,
      ];
      $statistics['counts']['median'] = [
        'title' => ts('Median'),
        'value' => implode(',  ', $median),
        'type' => CRM_Utils_Type::T_STRING,
      ];

    return $statistics;
  }

  function _getTableNameByName($name) {
     $values = civicrm_api3('CustomGroup', 'get', [
       'name' => $name,
       'sequential' => 1,
       'return' => ['table_name'],
     ])['values'];
     if (!empty($values[0])) {
       return CRM_Utils_Array::value('table_name', $values[0]);
     }

     return NULL;
  }

}
