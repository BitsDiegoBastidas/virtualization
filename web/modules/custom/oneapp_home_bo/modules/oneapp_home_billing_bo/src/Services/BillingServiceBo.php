<?php

namespace Drupal\oneapp_home_billing_bo\Services;

use Drupal\oneapp_home_billing\Services\BillingService;
use Drupal\oneapp\Exception\HttpException;

/**
 * Class BillingRestLogicBo.
 */
class BillingServiceBo extends BillingService {


  /**
   * Get data invoices.
   *
   * @param string $id
   *   Account Id.
   * @param string $idType
   *   Account Id Type: to access other info about Id if it is necessary.
   *
   * @return object|array
   *   Data of all invoices
   */
  public function getInvoicesData($id, $idType = NULL) {

    $access = $this->validateAccessToB2bInvoices($id, $idType);
    if (empty($access)) {
      return [
        'invoiceList' => [],
        'noData' => ['value' => 'no_access'],
      ];
    }

    $invoices = $this->callInvoicesApi($id);
    if (isset($invoices->noData) && $invoices->noData) {
      return [
        'invoiceList' => [],
        'noData' => ['value' => 'empty'],
      ];
    }
    foreach ($invoices as $index => &$invoice) {
      $invoice->period = isset($invoice->period) ? "01-" . substr($invoice->period, 4, 2) . '-' . substr($invoice->period, 0, 4) : '';
      if ($invoice->invoiceId == 0) {
        unset($invoices[$index]);
      }
      elseif ($invoice->invoiceId == NULL) {
        unset($invoices[$index]);
      }
    }
    if (count($invoices) == 0) {
      return [
        'invoiceList' => [],
        'noData' => ['value' => 'empty'],
      ];
    }
    return array_reverse($invoices);
  }

  /**
   * Get data invoices.
   *
   * @param string $id
   *   Account Id.
   * @param string $idType
   *   Account Id Type: to access other info about Id if it is necessary.
   *
   * @return object|array
   *   Data of all invoices
   */
  public function getInvoicesDataForBalance($id, $idType) {

    $access = $this->validateAccessToB2bInvoices($id, $idType);
    if (empty($access)) {
      return [
        'invoiceList' => [],
        'noData' => ['value' => 'no_access'],
      ];
    }

    $invoices = $this->callInvoicesApi($id);
    if (isset($invoices->noData) && $invoices->noData) {
      return [
        'invoiceList' => [],
        'noData' => ['value' => 'empty'],
      ];
    }
    return array_reverse($invoices);
  }

  public function getInvoicesDataForBalanceConvergent($id, $idType) {
    $invoices = $this->callInvoicesApi($id);
    if (isset($invoices->noData) && $invoices->noData) {
      return [
        'invoiceList' => [],
        'noData' => ['value' => 'empty'],
      ];
    }
    return array_reverse($invoices);
  }

  /**
   * Implements getPdf api.
   *
   * @param string $id
   *   Account Id.
   * @param string $invoiceId
   *   Invoice Id.
   *
   * @return mixed
   *   The HTTP response object.
   *
   * @throws \Drupal\oneapp\Exception\HttpException
   */
  public function callPdfApi($id, $invoiceId, $decodeJson = TRUE, $query = []) {
    $data = $this->getDataForPdf($id, $invoiceId);
    $pdf = $this->manager
      ->load('oneapp_home_billing_v2_0_pdf_download_endpoint')
      ->setParams(['id' => $id, 'period' => $data])
      ->setHeaders([])
      ->setQuery(['invoiceId' => $invoiceId])
      ->setDecodeJson(FALSE)
      ->setAcceptJson(FALSE)
      ->sendRequest();
    return $pdf;
  }

  /**
   * Implements getDataForPdf api.
   *
   * @param string $id
   *   Account Id.
   * @param string $invoiceId
   *   Invoice Id.
   *
   * @return mixed
   *   The HTTP response object.
   *
   * @throws \Drupal\oneapp\Exception\HttpException
   */
  public function getDataForPdf($id, $invoiceId) {
    $invoices = $invoices = $this->callInvoicesApi($id);
    foreach ($invoices as $invoice) {
      if ($invoice->invoiceId == $invoiceId) {
        return $invoice->period;
      }
    }
  }

  /**
   * Process data for the call api.
   *
   * @param string $id
   *   Account Id.
   * @param string $idType
   *   Account Id Type: to access other info about Id if it is necessary.
   * @param string $invoiceId
   *   Invoice id.
   *
   * @return array
   *   Info to get pdf file with 'url' o 'data' index.
   */
  public function getPdfData($id, $idType, $invoiceId) {
    $content['content'] = $this->callPdfApi($id, $invoiceId);
    return $content;
  }

  /**
   * Get the all balance info.
   *
   * @paramstring $id
   * Account Id.
   * @paramstring $idType
   * Account Id Type: to access other info about Id if it is necessary.
   *
   * @returnobject
   * data.
   */
  public function getBalance($id, $idType) {

    $balance = (object) [
      'dueAmount' => 0,
      'dueInvoicesCount' => 0
    ];
    $this->paymentGatewayService = $this->initPaymentGatewayService();
    $is_convergent = $this->paymentGatewayService->getBillingAccountIdForConvergentMsisdn($id, $idType);
    $id = $is_convergent['value'] ? $is_convergent['billingAccountId'] : $id;
    $invoices = $is_convergent['value'] ? $this->getInvoicesDataForBalanceConvergent($id, $idType) :
      $this->getInvoicesDataForBalance($id, $idType);
    if (isset($invoices["noData"]["value"])) {
      return $invoices;
    }

    foreach ($invoices as $key => $value) {
      if (!$value->hasPayment) {
        if ($value->invoiceDebtType != "ADELANTADA") {
          $balance->dueInvoicesCount++;
        }
        $balance->dueAmount += $value->dueAmount;
      }
    }
    $pendingInvoices = $this->getPendingInvoices($invoices);

    $pendingInvoice = end($pendingInvoices);
    $balance->invoiceId = isset($pendingInvoices[0]) ? $pendingInvoices[0]->invoiceId : 0;
    $balance->creationDate = isset($pendingInvoice) ? $pendingInvoice->creationDate : '';
    $balance->dueDate = isset($pendingInvoice) ? $pendingInvoice->dueDate : '';
    $balance->period = isset($pendingInvoice) ? "01-" . substr($pendingInvoice->period, 4, 2) . '-' . substr($pendingInvoice->period, 0, 4) : '';
    $balance->startPeriod = isset($pendingInvoice->startPeriod) ? $pendingInvoice->startPeriod : '';
    $balance->endPeriod = isset($pendingInvoice->endPeriod) ? $pendingInvoice->endPeriod : '';
    $balance->hasPayment = isset($pendingInvoice) ? $pendingInvoice->hasPayment : '';
    $lastInvoice = $pendingInvoices[0];
    $balance->lastInvoiceAmount = isset($lastInvoice->invoiceAmount) ? $lastInvoice->invoiceAmount : '';
    $balance->billingAccountId = isset($pendingInvoice) ? $pendingInvoice->billingAccountId : '';
    $balance->invoiceType = isset($pendingInvoice->invoiceType) ? $pendingInvoice->invoiceType : '';
    $balance->pendingInvoices = array_reverse($pendingInvoices);
    $balance->extendedDueDate = "";
    foreach ($balance->pendingInvoices as $key => $pendingInvoice) {
      $balance->pendingInvoices[$key]->accountNumber = $pendingInvoice->billingAccountId;
      $balance->pendingInvoices[$key]->amountForPartialPayment = $pendingInvoice->dueAmount;
    }
    if ($is_convergent['value']) {
      $this->setBalanceMobile($balance);
    }
    $this->balance = $balance;
    return $balance;
  }

  /**
   * get Home Balance.
   */
  public function setBalanceMobile($balance) {
    $balance_service = \Drupal::service('oneapp_mobile_billing.v2_0.balance_rest_logic');
    $balance_service->setBalance($balance);
  }

  /**
   * Construct all data required for Payment Gateway.
   *
   * @param string $id
   * @param string $idType
   * @return array
   */
  public function getDataForPayment($id, $idType, $params = []) {
    $this->oldestInvoice = TRUE;
    $balance = $this->getBalance($id, $idType);
    $balance->accountNumber = $balance->billingAccountId;
    if (!isset($params['productType']) || $params['productType'] == 'invoice') {
      $balance->multipay = TRUE;
    }
    $this->balance = $balance;
    return (array) $balance;
  }

  /**
   * Return the formatted period for show on apiux init transaction.
   */
  public function getFormattedPeriod($period) {
    $year = substr($period, 0, 4);
    $month = substr($period, -2);
    $month = str_replace([
      '01',
      '02',
      '03',
      '04',
      '05',
      '06',
      '07',
      '08',
      '09',
      '10',
      '11',
      '12',
    ], [
      'Enero',
      'Febrero',
      'Marzo',
      'Abril',
      'Mayo',
      'Junio',
      'Julio',
      'Agosto',
      'Septiembre',
      'Octubre',
      'Noviembre',
      'Diciembre',
    ], $month
    );
    if ((strlen($month) > 0) && isset($year)) {
      return ($month . ' de ' . $year);
    }
    else {
      return $period;
    }
  }

  /**
   * Request to invoices api.
   *
   * @param [type] $id
   *   Id of account query.
   * @param array $query
   *   Query params for request.
   *
   * @return object
   *   data from api.
   */
  public function callInvoicesApi($id, $query = []) {
    try {
      $invoices = $this->manager
        ->load('oneapp_home_billing_v2_0_invoices_endpoint')
        ->setParams(['id' => $id])
        ->setHeaders([])
        ->setQuery($query)
        ->sendRequest();
      return $invoices;
    }
    catch (\Exception $exception) {
      if ($exception->getCode() == 404) {
        $data = new \stdClass;
        $data->noData = "no_access";
        return $data;
      }
      throw $exception;
    }

  }

}
