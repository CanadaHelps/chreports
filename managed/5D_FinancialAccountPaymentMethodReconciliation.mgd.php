<?php
return array(
  array(
    'module' => 'biz.jmaconsulting.chreports',
    'name' => 'GL Account & Payment Method Reconciliation Report (Full)',
    'update' => 'never',
    'entity' => 'ReportInstance',
    'params' => array(
      'version' => 3,
      'report_id' => 'chreports/contrib_glaccount',
      'title' => ts('GL Account & Payment Method Reconciliation Report (Full)'),
      'name' => 'contrib_glaccount_payment_reconciliation',
      "description" => "Shows Bookkeeping Transactions Report",
      'permission' => 'administer Reports',
      'is_active' => 1,
      "form_values" => NULL,
      'is_reserved' =>  0,
    ),
  ),
);
