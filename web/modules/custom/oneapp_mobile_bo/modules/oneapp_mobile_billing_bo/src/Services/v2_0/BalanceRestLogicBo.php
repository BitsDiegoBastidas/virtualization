<?php

namespace Drupal\oneapp_mobile_billing_bo\Services\v2_0;

use Drupal\oneapp_mobile_billing\Services\v2_0\BalanceRestLogic;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BalanceRestLogicBo.
 */
class BalanceRestLogicBo extends BalanceRestLogic {

  /**
   * get options
   */
  public function getOptionsPayment() {
    $configs = $this->configBlock["configs"]["payments"];
    $count = 0;
    foreach ($this->configBlock["options"]["fields"] as $id => $option) {
      if ($id == "total") {
        $label = str_replace("@amount", $this->utils->getFormattedValue("localCurrency", $this->balance->dueAmount), $option['label']);
        $options[$count]['label'] = $label;
      }
      else {
        $options[$count]['label'] = $option['label'];
      }
      $options[$count]['value'] = $id;
      $options[$count]['show'] = $option['show'] ? TRUE : FALSE;
      if ($id == "partial" && isset($this->configBlock["configs"]["payments"]["partialPaymentShowDebt"]) && $this->configBlock["configs"]["payments"]["partialPaymentShowDebt"]["show"]) {
        $options[$count]['show'] = $this->balance->dueAmount == 0 ? FALSE : $options[$count]['show'];
      }
      if (isset($option['placeholder'])) {
        $options[$count]['placeholder'] = $option['placeholder'];
      }
      if (isset($option['isEditable'])) {
        $options[$count]['isEditable'] = (bool) $option['isEditable'];
      }
      if (count($this->balance->pendingInvoices) <= 1 && $id == "partial") {
        $options[$count]['show'] = FALSE;
      }
      $count++;
    }
    if ($configs["paymentOldestInvoice"]["show"]) {
      $options = $this->getOptionPaymentOldestInvoice($options);
    }
    if ($configs["mutiplePayments"]["show"]) {
      $options = array_merge($options, $this->getOptionsMultiplePayements());
    }
    return $options;
  }

  /**
   * GetOptionPaymentOldestInvoice
   */
  public function getOptionPaymentOldestInvoice($options) {
    $configs = $this->configBlock["configs"]["payments"];
    foreach ($options as $key => &$option) {
      if ($option['value'] == "partial") {
        $oldestInvoice = !empty($this->balance->pendingInvoices[0]) ? $this->balance->pendingInvoices[0] : NULL;
        $invoiceAmount = isset($oldestInvoice->dueAmount) ? $oldestInvoice->dueAmount : '';
        $option['label'] = str_replace("@amount", $this->utils->getFormattedValue("localCurrency", $invoiceAmount), $configs["paymentOldestInvoice"]["label"]);
      }
    }
    return $options;
  }

  /**
   * getActions
   */
  public function getActions($data) {
    $actions = $this->configBlock['actions']['fields'];
    unset($actions['convergent']);
    if (isset($data['pdfUrl'])) {
      $data['urlDownload'] = $data['pdfUrl'];
      unset($data['pdfUrl']);
    }
    $actions['downloadUrl'] = [
      'label' => $actions['downloadUrl']['label'] ?? '',
      'show' => is_null($data['urlDownload']) ? FALSE : $this->utils->formatBoolean($actions['downloadUrl']['show']),
      'url' => $data['urlDownload'] ?? '',
      'type' => 'link',
    ];
    $actions['secondaryAction'] = [
      'label' => $actions['secondaryAction']['label'] ?? '',
      'show' => isset($data['secondaryAction']) ? $this->utils->formatBoolean($actions['secondaryAction']['show']) : FALSE,
      'url' => $data['secondaryAction'] ?? '',
      'type' => 'link',
    ];
    $actions['payment']['show'] = $this->utils->formatBoolean($actions['payment']['show']);
    $actions['payment']['type'] = 'button';

    return $actions;
  }

  /**
   * Responds to GET requests.
   *
   * @param string $account_id
   *   Msisdn.
   * @param string $account_id_type
   *   Type of Msisdn.
   *
   * @return array
   *   The associative array.
   */
  public function get($account_id, $account_id_type) {
    $rows = [];
    $date_formatter = \Drupal::service('date.formatter');
    $this->paymentGatewayService = $this->initPaymentGatewayService();
    $is_convergent = $this->paymentGatewayService->getBillingAccountIdForConvergentMsisdn($account_id, $account_id_type);
    if ($is_convergent['value']) {
      return $this->getHomeBalance($is_convergent['billingAccountId'], "billingaccounts");
    }
    // Get debt balance.
    $balance = $this->billingService->getBalance($account_id, $account_id_type);
    $this->balance = $balance;

    if (is_array($balance) && isset($balance['noData']['value'])) {
      return $balance;
    }

    $local_currency = $this->configBlock["configs"]["currency"]["format"] == 'localCurrency';
    foreach ($this->configBlock['debtBalance']['fields'] as $id => $field) {
      $row[$id] = [
        'label' => $field['label'],
        'show' => ($field['show']) ? TRUE : FALSE,
      ];

      switch ($id) {
        case 'invoiceId':
          $row[$id]['value'] = isset($balance->invoiceId) ? $balance->invoiceId : "---------";
          $row[$id]['formattedValue'] = isset($balance->invoiceId) ? $balance->invoiceId : "---------";
          break;

        case 'dueAmount':
          $due_amount = $balance->dueAmount;
          $row[$id]['value'] = $due_amount;
          $row[$id]['formattedValue'] = $this->utils->formatCurrency($due_amount, $local_currency);
          break;

        case 'dueDate':
          $date = isset($balance->dueDate) && $balance->dueDate != "" ? $date_formatter->format(strtotime($balance->dueDate), $this->configBlock["configs"]["date"]["format"]) : "---------";
          $row[$id]['value'] = $date;
          $row[$id]['formattedValue'] = $date;
          break;

        case 'additionalAmount':
          $additional_amount = isset($balance->localAdditionalAmount) ? $balance->localAdditionalAmount : 0;
          $row[$id]['value'] = $additional_amount;
          $row[$id]['formattedValue'] = $this->utils->formatCurrency($additional_amount, $local_currency);
          break;

        case 'minPaymentAmount':
          $local_due_amount = isset($balance->localdueAmount) ? $balance->localdueAmount : 0;
          $row[$id]['value'] = $local_due_amount;
          $row[$id]['formattedValue'] = $this->utils->formatCurrency($local_due_amount, $local_currency);
          break;

        case 'dueInvoicesCount':
          $row[$id]['value'] = isset($balance->dueInvoicesCount) ? $balance->dueInvoicesCount : 0;
          $row[$id]['formattedValue'] = isset($balance->dueInvoicesCount) ? (string) $balance->dueInvoicesCount : '0';
          break;

        case 'billingCycle':
          $row[$id]['value'] = isset($balance->billingCycle) ? $balance->billingCycle : "---------";
          $row[$id]['formattedValue'] = isset($balance->billingCycle) ? $balance->billingCycle . ' ' . $field['description'] : "---------";
          break;

        case 'billingPeriod':
          $row[$id]['value']['startDate'] = isset($balance->startPeriod) ? $balance->startPeriod : "";
          $row[$id]['value']['endDate'] = isset($balance->endPeriod) ? $balance->endPeriod : "";
          $startPeriod = $row[$id]['value']['startDate'] != "" ? $date_formatter->format(strtotime($row[$id]['value']['startDate']), $this->configBlock["configs"]["date"]["billingPeriod"]) : "---------";
          $endPeriod = $row[$id]['value']['endDate'] != "" ? $date_formatter->format(strtotime($row[$id]['value']['endDate']), $this->configBlock["configs"]["date"]["billingPeriod"]) : "---------";
          $row[$id]['formattedValue'] = t('@startPeriod a @endPeriod', ['@startPeriod' => $startPeriod, '@endPeriod' => $endPeriod]);
          unset($startPeriod);
          unset($endPeriod);
          break;

        case 'billingAccountId':
          $row[$id]['value'] = $balance->billingAccountId ?? $account_id;
          $row[$id]['formattedValue'] = $balance->billingAccountId ?? $account_id;
          break;

        case 'lastInvoiceAmount':
          $row[$id]['value'] = $balance->lastInvoiceAmount ?? "---------";
          $row[$id]['formattedValue'] = isset($balance->lastInvoiceAmount) ? $this->utils->formatCurrency($balance->lastInvoiceAmount, $local_currency) : "---------";
          break;

        case 'oldestPendingInvoiceAmount':
          $row[$id]['value'] = $balance->oldestPendingInvoiceAmount ?? "---------";
          $row[$id]['formattedValue'] = isset($balance->oldestPendingInvoiceAmount) ? $this->utils->formatCurrency($balance->oldestPendingInvoiceAmount, $local_currency) : "---------";
          break;

      }
      $rows[$id] = $row[$id];
    }
    $rows['isDelinquent'] = ['value' => FALSE];
    // If user has not paid the last invoice.
    if (isset($balance) && !$balance->hasPayment) {
      $rows['isDelinquent'] = ['value' => $this->utils->isExpiratedDate($balance->dueDate)];
    }
    if (isset($balance->dueInvoicesCount) && is_numeric($balance->dueInvoicesCount) && $balance->dueInvoicesCount > 1) {
      $rows['isDelinquent'] = ['value' => TRUE];
    }
    $rows['urlDownload'] = isset($balance->invoiceId) ? $this->billingService->getFormatUrlDownload($account_id, $account_id_type, $balance->invoiceId) : NULL;
    return $rows;
  }

  /**
   * get Home Balance.
   */
  public function getHomeBalance($billing_account_id, $account_id_type) {
    $home_billing_service = \Drupal::service('oneapp_home_billing.v2_0.balance_rest_logic');
    $config_block_service = \Drupal::service('adf_block_config.config_block');
    $home_config_block = $config_block_service
      ->getDefaultConfigBlock('oneapp_home_billing_v2_0_balance_block');
    $home_billing_service->setConfig($home_config_block);
    return $home_billing_service->get($billing_account_id, $account_id_type);
  }
}
