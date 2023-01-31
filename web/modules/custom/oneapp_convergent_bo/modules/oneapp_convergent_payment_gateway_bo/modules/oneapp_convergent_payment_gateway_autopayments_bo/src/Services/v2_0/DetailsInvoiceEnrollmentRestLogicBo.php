<?php

namespace Drupal\oneapp_convergent_payment_gateway_autopayments_bo\Services\v2_0;

use Drupal\oneapp\ApiResponse\ErrorBase;
use Drupal\oneapp\Exception\HttpException;
use Drupal\oneapp_convergent_payment_gateway_autopayments\Services\v2_0\DetailsInvoiceEnrollmentRestLogic;

/**
 * Class DetailsInvoiceRestLogic.
 */
class DetailsInvoiceEnrollmentRestLogicBo extends DetailsInvoiceEnrollmentRestLogic {

  /**
   * return data invoice.
   *
   * @param $business_unit
   * @param $id_type
   * @param $id
   *
   * @return mixed
   */
  public function get($id, $id_type, $business_unit, $data_invoice = []) {
    $is_b2b = $this->authTokenService->isB2B($id, $id_type);
    $config = [];
    $billing_account_id = $id;
    $this->paymentGatewayService->getVariablesIfConvergent($billing_account_id, $business_unit, $id_type);
    $this->authTokenService->setBusinessUnit($business_unit);
    $this->authTokenService->setIdType($id_type);
    if (isset($this->config['blockAccess'][$business_unit])) {
      if ($this->config['blockAccess'][$business_unit]['blockb2bstaff']) {
        if ($business_unit == 'mobile') {
          $mobile_services = \Drupal::service('oneapp.mobile.utils');
          $is_b2b_staff = $mobile_services->isB2b($id);
          if ($is_b2b_staff) {
            $error_base = new ErrorBase();
            $error_base->getError()->set('message', $this->config['blockAccess'][$business_unit]['blockmessage']);
            throw new HttpException(401, $error_base);
          }
        }
      }
    }
    $service = \Drupal::service('recurring_payment_gateway.v2_0.details_invoice_service');
    if (count($data_invoice) == 0) {
      try {
        $data_invoice = $service->detailsInvoice($id, $id_type, $business_unit);
      }
      catch (\Throwable $th) {
        $data_invoice = [];
      }
    }
    if ($business_unit == 'home') {
      $id = $billing_account_id;
      $this->authTokenService->setId($id);
    }
    $utils = \Drupal::service('oneapp.utils');
    $fiels = "fields_{$business_unit}";
    if (isset($data_invoice["accountNumber"]) && strlen($data_invoice['accountNumber']) > 0) {
      $this->params['accountNumber'] = $data_invoice["accountNumber"];
    }
    else {
      $this->params['accountNumber'] = $this->utilsPayment->getAccountNumberForPaymentGatewayFromToken($business_unit, $id);
      $data_invoice['accountNumber'] = $this->params['accountNumber'];
    }
    foreach ($this->config[$fiels] as $key => $value) {
      if ($key == 'startBillingCycle' || $key == 'endBillingCycle') {
        break;
      }
      $index = $key;
      $data[$index] = [
        "show" => ($this->config[$fiels][$key]["show"]) ? TRUE : FALSE,
        "label" => $this->config[$fiels][$key]["label"],
      ];
      switch ($key) {
        case 'accountNumber':
          $data[$index]['value'] = isset($data_invoice["accountNumber"]) ? $data_invoice["accountNumber"] :
            $this->config[$fiels][$key]["valueDefault"];
          $data[$index]['formattedValue'] = $data[$index]['value'];
          break;

        case 'accountToEnroll':
          $data[$index]['value'] = $id;
          $data[$index]['formattedValue'] = $data[$index]['value'];
          break;

        case 'invoiceAmount':
          $amount = NULL;
          $currency = isset($this->config["config"]["currency"][$business_unit]) ? $this->config["config"]["currency"][$business_unit] :
            FALSE;
          if (isset($data_invoice["lastInvoiceAmount"])) {
            $amount = $utils->formatCurrency($data_invoice["lastInvoiceAmount"], $currency);
          }
          $data[$index]['value'] = isset($data_invoice["lastInvoiceAmount"]) ? $data_invoice["lastInvoiceAmount"] :
            $this->config[$fiels][$key]["valueDefault"];
          $data[$index]['formattedValue'] = isset($data_invoice["lastInvoiceAmount"]) ? $amount : $data[$index]['value'];
          break;

        default:
          $data[$index]['value'] = isset($this->config[$fiels][$key]["value"]) ? $this->config[$fiels][$key]["value"] :
            $this->config[$fiels][$key]["valueDefault"];
          $data[$index]['formattedValue'] = isset($this->config[$fiels][$key]["formattedValue"]) ?
            $this->config[$fiels][$key]["formattedValue"] : $data[$index]['value'];
          break;
      }
    }
    return $data;
  }

  /**
   * mock up the api response.
   *
   * @param $business_unit
   * @param $id_type
   * @param $id
   * @param $request
   *
   * @return array
   */
  public function getDetailsNewEnrollment($business_unit, $id_type, $id, $request) {
    $is_b2b = $this->authTokenService->isB2B($id, $id_type);
    $billing_account_id = $id;
    $this->paymentGatewayService->getVariablesIfConvergent($billing_account_id, $business_unit, $id_type);
    $this->authTokenService->setBusinessUnit($business_unit);
    $this->authTokenService->setIdType($id_type);
    if ($business_unit == 'home') {
      $id = $billing_account_id;
      $this->authTokenService->setId($id);
    }
    /** @var \Drupal\oneapp_convergent_payment_gateway_autopayments\Services\EnrollmentsService $payment_gateway */
    $payment_gateway = \Drupal::service('oneapp_convergent_payment_gateway.recurring_payments.v2_0.enrollments');
    $this->params['customerIpAddress'] = $payment_gateway->getUserIp();
    $payment_gateway->setParams($this->params);
    $payment_gateway->setConfig($this->config);
    $response_enrollment = $payment_gateway->createEnrollment($business_unit, 'invoices', $id_type, $id, $request);
    $this->statusEnrollment = isset($response_enrollment["typeEnrollment"]) ? $response_enrollment["typeEnrollment"] : 'false';
    $data = [];
    try {
      $response_enrollment["balance"] = isset($response_enrollment["balance"]) ? $response_enrollment["balance"] : [];
      $data['invoice'] = $this->get($id, $id_type, $business_unit, $response_enrollment["balance"]);
    } catch (\Throwable $th) {}
    try {
      $enrollments_response = $this->getFormattedEnrollment($business_unit, $id_type, $id, $response_enrollment["balance"]);
      $data['enrollment'] = $enrollments_response['enrollments'];
    }
    catch (\Throwable $th) {}
    return $data;
  }

  /**
   * return data tokenizecadr.
   *
   * @param $business_unit
   *
   * @return array
   */
  public function getTokenizedCardsList($business_unit) {
    $this->params['uuid'] = $this->authTokenService->getUserIdPayment();
    $this->params['query']['accountNumber'] = $this->authTokenService->getId();
    $cards = $this->utilsPayment->getCards($business_unit, $this->params);
    return isset($cards['tokenizedCards']) ? $cards['tokenizedCards'] : [];
  }

}
