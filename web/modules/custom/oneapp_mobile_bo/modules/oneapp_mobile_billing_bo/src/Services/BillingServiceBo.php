<?php

namespace Drupal\oneapp_mobile_billing_bo\Services;

use DateTime;
use Drupal\oneapp_mobile_billing\Services\BillingService;
use stdClass;
use Drupal\Core\Datetime\Entity\DateFormat;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BillingServiceBo.
 */
class BillingServiceBo extends BillingService {

  const DIRECTORY_IMAGES_MOBILE = 'mobile_billing';

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $oldestInvoice = FALSE;

  /**
   * Process to request the invoice api
   *
   * @param [string] $id
   * @param [string] $id_type
   * @return mixed
   */
  public function getInvoicesData($id, $id_type) {
    $billing_account_id = $id;
    if ($id_type == "subscribers") {
      $config_mobile = \Drupal::config('oneapp_mobile.config')->getRawData();
      if (isset($config_mobile["billing"]["getBilingAccountByMsisdn"]) && $config_mobile["billing"]["getBilingAccountByMsisdn"]) {
        //obtener billingAccountId
        $mobile_utils_service = \Drupal::service('oneapp.mobile.utils');
        $billing_account_id = $mobile_utils_service->getBillingAccountByMsisdn($id);
      }
    }
    $validate_access = isset($config_mobile["billing"]['validate_access']) ? $config_mobile["billing"]['validate_access'] : '1';
    if ($validate_access) {
      if (!$this->validateAccessToB2bInvoices($id, $id_type)) {
        return [
          'noData' => [
            'invoiceList' => [],
            'value' => 'no_access',
          ],
        ];
      }
    }

    $invoices = $this->callInvoicesApi($billing_account_id);
    if (isset($invoices->noData) && $invoices->noData) {
      return [
        'invoiceList' => [],
        'noData' => ['value' => 'empty'],
      ];
    }

    if (is_array($invoices)) {
      $rows = [];
      foreach ($invoices as $index => $invoice) {
        $invoice->billingAccountId = $invoice->billingAcountId;
        $period = isset($invoice->period) ? explode(" ", $invoice->period) : [];
        $invoice->billingPeriod = new stdClass;
        $invoice->billingPeriod->startDateTime = isset($period[0]) ? $period[0] : "";
        $invoice->billingPeriod->endDateTime = isset($period[2]) ? $period[2] : "";
        $invoice->billingCycle = $this->getCycleDay($period[3]);
        $rows[] = $invoice;
      }
      return $rows;
    }
    return $invoices;
  }

  /**
   * {@inheritdoc}
   */
  public function getBalance($id, $id_type) {
    $billing_account_id = $id;
    $balance = new stdClass;
    if ($id_type == "subscribers") {
      $config_mobile = \Drupal::config('oneapp_mobile.config')->getRawData();
      if (isset($config_mobile["billing"]["getBilingAccountByMsisdn"]) && $config_mobile["billing"]["getBilingAccountByMsisdn"]) {
        //obtener billingAccountId
        $mobile_utils_service = \Drupal::service('oneapp.mobile.utils');
        $billing_account_id = $mobile_utils_service->getBillingAccountByMsisdn($id);
      }
    }
    $validate_access = isset($config_mobile["billing"]['validate_access']) ? $config_mobile["billing"]['validate_access'] : '1';
    if ($validate_access) {
      if (!$this->validateAccessToB2bInvoices($id, $id_type)) {
        return [
          'noData' => [
            'value' => 'no_access',
          ],
        ];
      }
    }

    $invoices = $this->getInvoicesData($id, $id_type);
    if (isset($invoices['noData']) && $invoices['noData']['value'] == 'empty') {
      return [
        'invoiceList' => [],
        'noData' => ['value' => 'empty'],
      ];
    }
    elseif (isset($invoices['noData'])) {
      $balance->hasPayment = TRUE;
      $balance->billingAccountId = $billing_account_id;
    }
    else {
      if ($this->oldestInvoice) {
        $pending_invoices = $this->getPendingInvoices($invoices);
        if (count($pending_invoices) > 0) {
          $balance = end($pending_invoices);
        }
      }
      else {
        $balance = $invoices[0];
        $balance->lastInvoiceAmount = $balance->invoiceAmount;
        $balance->dueAmount = $this->getAmountTotalPending($invoices);
      }
      $balance->dueInvoicesCount = $this->countDueInvoices($invoices);
    }
    $balance->pendingInvoices = $this->getPendingInvoices($invoices);
    $balance->lastInvoiceAmount = (isset($balance->lastInvoiceAmount) ? $balance->lastInvoiceAmount : isset($balance->invoiceAmount)) ? $balance->invoiceAmount : $invoices[0]->invoiceAmount;
    $oldest_pending_invoice = end($balance->pendingInvoices);
    $balance->oldestPendingInvoiceAmount = isset($oldest_pending_invoice->dueAmount) ? $oldest_pending_invoice->dueAmount : 0;
    foreach ($balance->pendingInvoices as $key => $pendingInvoice) {
      $balance->pendingInvoices[$key]->accountNumber = $pendingInvoice->contractId;
      $balance->pendingInvoices[$key]->amountForPartialPayment = $pendingInvoice->dueAmount;
    }
    $balance->pendingInvoices = array_reverse($balance->pendingInvoices);
    $this->balance = $balance;
    return $balance;
  }

  /**
   * Implements getPdf api.
   *
   * @param string $billing_account_id
   *   Billing account Id.
   * @param string $invoice_id
   *   Invoice Id.
   *
   * @return mixed
   *   The HTTP response object.
   *
   * @throws \Drupal\oneapp\Exception\HttpException
   */
  public function callPdfApi($id, $invoice_id, $invoice_serial = NULL, $decode_json = TRUE) {

    $data = $this->getDataInvoice($id, $invoice_id);
    return $this->manager
      ->load('oneapp_mobile_billing_v2_0_invoices_pdf_endpoint')
      ->setHeaders([])
      ->setQuery($data)
      ->setDecodeJson(FALSE)
      ->setParams([
        'id' => $id,
        'invoice_id' => $invoice_id,
      ])
      ->sendRequest();
  }

  /**
   * get data invoice for invoiceId
   */
  public function getDataInvoice($id, $invoice_id) {
    $invoices = $this->callBalanceApi($id);
    foreach ($invoices->invoices as $invoice) {
      if ($invoice->number == $invoice_id) {
        return [
          'cycle' => $invoice->billingInfo->cycle,
          'date' => $invoice->expirationDate,
          'serial' => $invoice->serial,
          'requestType' => $invoice->type,
        ];
      }
    }
  }

  public function getDataForPayment($id, $id_type, $params = []) {
    $this->oldestInvoice = TRUE;
    $balance = $this->getBalance($id, $id_type);
    $balance = (array) $balance;
    $balance['accountNumber'] = (string) $balance['contractId'];
    $balance['multipay'] = TRUE;
    return $balance;
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
    return $home_billing_service->getBalance($billing_account_id, $account_id_type);
  }

  /**
   * get amount total pending
   */
  public function getAmountTotalPending($invoices) {
    $amount = 0;
    foreach ($invoices as $invoice) {
      if (!$invoice->hasPayment) {
        $amount = $amount + $invoice->dueAmount;
      }
    }
    return $amount;
  }

  /**
   * Get amount of invoices.
   *
   * @param array $invoices
   *   FriendlyName.
   *
   * @return int
   *   The Integer response.
   */
  protected function countDueInvoices(array $invoices) {
    $count = 0;
    foreach ($invoices as $invoice) {
      if (!$invoice->hasPayment) {
        $count++;
      }
    }
    return ($count == 0) ? t('Ninguna') : $count;
  }

  /**
   * Return the accountNumber with the format necessary for PG.
   */
  public function getAccountNumberForMultipay($current_invoice) {
    return $current_invoice->contractId . '|' . $this->getProductReferenceForPayment($current_invoice);
  }

  /**
   * Implements getInvoices.
   *
   * @param string $id
   *   Billing account Id.
   *
   * @return ResponseInterface
   *   The HTTP response object.
   *
   * @throws \Drupal\oneapp\Exception\HttpException
   */
  public function callInvoicesApi($id) {
    try {
      return $this->manager
        ->load('oneapp_mobile_billing_v2_0_invoices_endpoint')
        ->setParams(['id' => $id])
        ->setHeaders([])
        ->setQuery([])
        ->sendRequest();
    }
    catch (\Exception $exception) {
      if ($exception->getCode() == 404) {
        $data = new stdClass;
        $data->noData = "no_access";
        return $data;
      }
      throw $exception;
    }
  }

  /**
   * Get usage data hourly by app
   *
   * @param string $id
   * @param string $start_date
   * @param string $end_date
   * @return object|array
   */
  public function retrieveUsageDataHourlyAppByRange($id, $start_date, $end_date) {
    try {
       return $this->manager
        ->load('oneapp_mobile_billing_v2_0_usage_data_hourly_by_app_endpoint')
        ->setParams([
          'id' => $id,
        ])
        ->setHeaders([])
        ->setQuery([
          'startDate' => $start_date,
          'endDate' => $end_date,
        ])
        ->sendRequest();
    } catch (\Exception $e) {
      return (object) [
        'code' => 404,
        'message' => $e->getMessage(),
        'status' => 'failed',
      ];
    }
  }

  /**
   * Get list of dates to filter Consumption
   *
   * @param int $number_days
   * @param string $date_format
   * @param bool $formatted_date
   * @return array
   */
  public function listOfDatesToFilterConsumption($number_days = 1, $date_format ='Y-m-d', $formatted_date = FALSE) {
    $days = [];
    $formatted_days = [];
    $current_day = new DateTime();

    $days[] = $current_day;
    for ($i = 1; $i < $number_days; $i++) {
      $day = new DateTime();
      $days[$i] = $day->modify("-{$i} days");
    }

    for ($i = 0; $i <= (count($days) -1) ; $i++) {
      $day_datetime = $days[$i];
      $new_date = $day_datetime->format('Y-m-d');
      $new_datetime = new DateTime($new_date);
      $formatted_days[$i] = [
        "optionValue"     => 1,
        "value"           => $i,
        "formattedValue"  => $formatted_date ? $this->getFormattedDate($day_datetime->format('Y-m-d'), $date_format) : $new_datetime->format($date_format),
      ];
    }
    return $formatted_days;
  }

  /**
   * Get Formatted Date
   *
   * @param string $date
   * @param string $current_format_date
   * @return string
   */
  public function getFormattedDate($date, $current_format_date) {
    $format_pattern = null;
    $list_format_dates = DateFormat::loadMultiple();
    $date_formatter_service = \Drupal::service('date.formatter');

    foreach ($list_format_dates as $name => $format) {
      if ($name == $current_format_date) {
        $format_pattern = $format->getPattern();
        break;
      }
    }
    return $date_formatter_service->format(strtotime($date), 'custom', $format_pattern, date_default_timezone_get());
  }
}
