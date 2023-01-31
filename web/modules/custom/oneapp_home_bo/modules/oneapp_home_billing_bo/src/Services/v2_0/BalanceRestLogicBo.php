<?php

namespace Drupal\oneapp_home_billing_bo\Services\v2_0;

use Drupal\oneapp_home_billing\Services\v2_0\BalanceRestLogic;

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
    foreach ($this->configBlock["options"]["payment"]["payment"] as $id => $option) {
      if ($option['value'] == "total") {
        $label = str_replace("@amount", $this->utils->getFormattedValue("localCurrency", $this->balance->dueAmount), $option['label']);
        $options[$count]['label'] = $label;
      }
      else {
        $options[$count]['label'] = $option['label'];
      }
      $options[$count]['value'] = $option['value'];
      $options[$count]['show'] = $option['show'] ? TRUE : FALSE;
      if ($option["value"] == "partial" && isset($this->configBlock["configs"]["payments"]["partialPaymentShowDebt"]) && $this->configBlock["configs"]["payments"]["partialPaymentShowDebt"]["show"]) {
        $options[$count]['show'] = $this->balance->dueAmount == 0 ? FALSE : $options[$count]['show'];
      }
      if (isset($option['placeholder'])) {
        $options[$count]['placeholder'] = $option['placeholder'];
      }
      if (isset($option['isEditable'])) {
        $options[$count]['isEditable'] = (bool) $option['isEditable'];
      }
      if (count($this->balance->pendingInvoices) <= 1 && $option['value'] == "partial") {
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
        $oldestInvoice = $this->balance->pendingInvoices[0];
        $label = str_replace("@amount", $this->utils->getFormattedValue("localCurrency", $oldestInvoice->dueAmount), $configs["paymentOldestInvoice"]["label"]);
        $option['label'] = $label;
      }
    }
    return $options;
  }

  /**
   * getOptionsMultiplePayements
   */
  public function getOptionsMultiplePayements() {
    $options = [];
    $configs = $this->configBlock["configs"]["payments"];
    if (count($this->balance->pendingInvoices) > 0) {
      $oldestInvoice = $this->balance->pendingInvoices;
      foreach ($oldestInvoice as $invoice) {
        $label = str_replace("@date", $this->utils->getFormattedValue('mes', $invoice->period), $configs["mutiplePayments"]["label"]);
        $options[] = [
          'label' => $label,
          'value' => $invoice->period,
          'valueType' => 'period',
          'amount' => $invoice->dueAmount,
          'show' => TRUE,
        ];
      }
    }
    return $options;
  }

  /**
   * Get data balance formated.
   *
   * @return array
   *   Return fields as array of objects.
   */
  public function get($id, $account_id_type) {

    $balance = $this->service->getBalance($id, $account_id_type);

    $this->balance = $balance;

    if (is_array($balance) && isset($balance['noData']['value'])) {
      return $balance;
    }

    $data = [];
    if ($balance) {
      $data['dueAmount'] = $this->formatField('dueAmount', $balance->dueAmount);
      $data['minPaymentAmount'] = isset($balance->minPaymentAmount) ? $this->formatField('minPaymentAmount', $balance->minPaymentAmount) : '';
      $data['dueInvoicesCount'] = $this->formatField('dueInvoicesCount', $balance->dueInvoicesCount);
      $data['extendedDueDate'] = $this->formatField('extendedDueDate', $balance->extendedDueDate);

      $data['invoiceId'] = $this->formatField('invoiceId', $balance->invoiceId);
      $data['creationDate'] = $this->formatField('creationDate', $balance->creationDate);
      $data['dueDate'] = $this->formatField('dueDate', $balance->dueDate);
      $data['period'] = $this->formatField('period', $balance->period);

      // Rewrite by original value period.
      if (!empty($balance->endPeriod)) {
        $start_period = $this->utils->getFormattedValue($this->configBlock['fields']['period']['format'], $balance->startPeriod);
        $end_period = $this->utils->getFormattedValue($this->configBlock['fields']['period']['format'], $balance->endPeriod);
        $data['period']['formattedValue'] = t('@startPeriod a @endPeriod', ['@startPeriod' => $start_period, '@endPeriod' => $end_period]);
        unset($start_period);
        unset($end_period);
      }
      $data['hasPayment'] = $this->formatField('hasPayment', $balance->hasPayment);
      $data['hasPayment']['formattedValue'] = (!empty($balance->hasPayment)) ? $this->utils->getFormatValueHasPayment($balance->hasPayment, $balance->dueDate) : '';

      $oldestPendingInvoice = (property_exists($balance, 'pendingInvoices') && count($balance->pendingInvoices) > 0) ? $balance->pendingInvoices[0] : [];
      $data["oldestPendingInvoiceAmount"] = isset($oldestPendingInvoice->dueAmount) ? $this->formatField('oldestPendingInvoiceAmount', $oldestPendingInvoice->dueAmount) : $this->formatField('oldestPendingInvoiceAmount', '');
      $data['oldestPendingInvoiceType'] = isset($oldestPendingInvoice->invoiceType) ? $this->formatField('oldestPendingInvoiceType', $oldestPendingInvoice->invoiceType) : $this->formatField('oldestPendingInvoiceType', '');
      $data['oldestPendingInvoiceType']['formattedValue'] = isset($oldestPendingInvoice->invoiceType) ? $this->utils->setFormatedInvoiceType($oldestPendingInvoice) : '';

      $data['isDelinquent']['value'] = FALSE;
      // If user has not paid the last invoice.
      if (isset($balance) && !$balance->hasPayment) {
        $data['isDelinquent'] = ['value' => $this->utils->isExpiratedDate($balance->dueDate)];
      }
      if (isset($balance->dueInvoicesCount) && $balance->dueInvoicesCount > 1) {
        $data['isDelinquent'] = ['value' => TRUE];
      }
    }
    foreach ($data as $key => $value) {
      if (isset($value['weight'])) {
        unset($data[$key]['weight']);
      }
    }
    $data['pdfUrl'] = $this->service->getFormatUrlDownload($balance->billingAccountId, $account_id_type, $data['invoiceId']['value']);
    unset($period);
    unset($balance);
    return (array) $data;
  }
}
