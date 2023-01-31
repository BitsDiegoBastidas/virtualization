<?php

namespace Drupal\oneapp_mobile_billing_bo\Services\v2_0;

use Drupal\oneapp_mobile_billing\Services\v2_0\InvoicesRestLogic;
/**
 * Class InvoicesRestLogic.
 */
class InvoicesRestLogicBo extends InvoicesRestLogic {

  /**
   * Responds the invoices data.
   *
   * @param string $account_id
   *   Account id.
   * @param string $account_id_type
   *   Account id type.
   *
   * @return mixed
   *   The array with data structure.
   */
  public function get($account_id, $account_id_type) {
    $rows = [];
    $config = $this->configBlock['config'];
    $date_formatter = \Drupal::service('date.formatter');
    $this->paymentGatewayService = $this->initPaymentGatewayService();
    $is_convergent = $this->paymentGatewayService->getBillingAccountIdForConvergentMsisdn($account_id, $account_id_type);
    if ($is_convergent['value']) {
      return $this->getHomeInvoices($is_convergent['billingAccountId'], "billingaccounts");
    }
    // Get invoices.
    $invoices = $this->billingService->getInvoicesData($account_id, $account_id_type);
    if (isset($invoices["noData"]) && (($invoices["noData"]['value'] == 'empty') || ($invoices['noData']['value'] == 'no_access'))) {
      return $invoices;
    }

    if (!isset($invoices['noData'])) {
      $invoices = array_slice($invoices, 0, intval($config['limit']['limit']));
      $rows = [];
      foreach ($invoices as $invoice) {
        $row = [];
        foreach ($this->configBlock['history'] as $id => $field) {
          $row[$id] = [
            'label' => $field['label'],
            'show' => ($field['show']) ? TRUE : FALSE,
          ];

          switch ($id) {
            case 'invoiceId':
              $row[$id]['value'] = isset($invoice->invoiceId) ? $invoice->invoiceId : '';
              $row[$id]['formattedValue'] = isset($invoice->invoiceId) ? $invoice->invoiceId : '';
              break;

            case 'billingPeriod':
              $start_date_time = $invoice->billingPeriod->startDateTime;
              $end_date_time = $invoice->billingPeriod->endDateTime;

              $row[$id]['value'] = [
                'startDateTime' => $start_date_time,
                'endDateTime' => $end_date_time,
              ];
              $start_date_time = $date_formatter->format(strtotime($start_date_time), $config['date']['formatPeriod']);
              $end_date_time = $date_formatter->format(strtotime($end_date_time), $config['date']['formatPeriod']);
              $row[$id]['formattedValue'] = t('@endDateTime', ['@startDateTime' => $start_date_time, '@endDateTime' => $end_date_time]);
              break;

            case 'billingCycle':
              $start_date_time = $date_formatter->format(strtotime($invoice->billingPeriod->startDateTime), $config['date']['formatPeriod']);
              $end_date_time = $date_formatter->format(strtotime($invoice->billingPeriod->endDateTime), $config['date']['formatPeriod']);

              $row[$id]['value'] = [
                'startDateTime' => $invoice->billingPeriod->startDateTime,
                'endDateTime' => $invoice->billingPeriod->endDateTime,
              ];
              $row[$id]['formattedValue'] = t('@startPeriod a @endPeriod', ['@startPeriod' => $start_date_time, '@endPeriod' => $end_date_time]);
              break;

            case 'invoiceAmount':
              $row[$id]['value'] = $invoice->invoiceAmount;
              $local_currency = $this->configBlock["config"]["currency"]["format"] == 'localCurrency';
              $row[$id]['formattedValue'] = $this->utils->formatCurrency($invoice->invoiceAmount, $local_currency);
              break;

            case 'dueAmount':
              $row[$id]['value'] = $invoice->dueAmount;
              $local_currency = $this->configBlock["config"]["currency"]["format"] == 'localCurrency';
              $row[$id]['formattedValue'] = $this->utils->formatCurrency($invoice->dueAmount, $local_currency);
              break;

            case 'dueDate':
              $row[$id]['value'] = $invoice->dueDate;
              $row[$id]['formattedValue'] = $date_formatter->format(strtotime($invoice->dueDate), $config['date']['format']);
              break;

            case 'hasPayment':
              $row[$id]['value'] = $invoice->hasPayment;
              $row[$id]['formattedValue'] = $this->utils->getFormatValueHasPayment($invoice->hasPayment, $invoice->dueDate);
              break;
          }
        }
        $rows[] = $row;
      }

      return [
        'invoiceList' => $rows,
        'urlDownload' => $this->billingService->getFormatUrlDownload($account_id, $account_id_type),
      ];
    }
    else {
      return $invoices;
    }

  }

  /**
   * get Home Balance.
   */
  public function getHomeInvoices($billing_account_id, $account_id_type) {
    $home_billing_service = \Drupal::service('oneapp_home_billing.v2_0.invoices_rest_logic');
    $config_block_service = \Drupal::service('adf_block_config.config_block');
    $home_config_block = $config_block_service
      ->getDefaultConfigBlock('oneapp_home_billing_v2_0_invoices_block');
    $home_billing_service->setConfig($home_config_block);
    $invoices = $home_billing_service->get($billing_account_id, $account_id_type);
    if (!empty($invoices['invoiceList'])) {
      foreach ($invoices['invoiceList'] as &$invoice) {
        $invoice['billingPeriod'] = $invoice['period'];
        unset($invoice['period']);
      }
    }
    return $invoices;
  }

}
