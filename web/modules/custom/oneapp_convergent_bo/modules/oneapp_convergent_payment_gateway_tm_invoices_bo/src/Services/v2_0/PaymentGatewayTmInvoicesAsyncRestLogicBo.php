<?php

namespace Drupal\oneapp_convergent_payment_gateway_tm_invoices_bo\Services\v2_0;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\oneapp\ApiResponse\ErrorBase;
use Drupal\oneapp\Exception\BadRequestHttpException;
use \Drupal\oneapp_convergent_payment_gateway_tm_invoices\Services\v2_0\PaymentGatewayTmInvoicesAsyncRestLogic;
use phpDocumentor\Reflection\Types\Parent_;

/**
 * Class PaymentGatewayTmInvoicesRestLogicBo.
 */
class PaymentGatewayTmInvoicesAsyncRestLogicBo extends PaymentGatewayTmInvoicesAsyncRestLogic {

  /**
   * PaymentGatewayRestLogic constructor.
   */
  public function __construct($manager, $utils_payment, $transactions, $token_authorization, $utils) {
    $account_service = \Drupal::service('oneapp_convergent_accounts.v2_0.accounts');
    $data_service = \Drupal::service('oneapp_convergent_payment_gateway.v2_0.my_cards_data_service');
    return parent::__construct($manager, $utils_payment, $transactions, $token_authorization, $utils, $account_service, $data_service);
  }

  /**
   * Return getConvergent.
   */
  public function getVariablesIfConvergent(&$id, &$business_unit, &$id_type) {
    $is_convergent = $this->getBillingAccountIdForConvergentMsisdn($id, $id_type);
    $business_unit = $is_convergent['value'] ? 'home' : $business_unit;
    $id_type = $is_convergent['value'] ? 'billingaccounts' : $id_type;
    $id = $is_convergent['value'] ? $is_convergent['billingAccountId'] : $id;
  }

  /**
   * Start (Initialize the payment process).
   */
  public function start($id, $id_type, $business_unit, $product_type, $params) {
    $this->mobileUtils = \Drupal::service('oneapp.mobile.utils');
    $this->saveInitialData($id, $id_type, $business_unit);
    /* If the line is convergent, the business unit must be changed from mobile to home,
     since the APIs only bring information for home accounts and their debt information
     is the same in home and mobile.*/
    $billing_account_id = $id;
    $this->getVariablesIfConvergent($billing_account_id, $business_unit, $id_type);
    $this->tokenAuthorization->setBusinessUnit($business_unit);
    $this->tokenAuthorization->setIdType($id_type);
    $balance = $this->utilsPayment->getBalance($billing_account_id, $id_type, $business_unit, $params);
    if ($business_unit == 'home') {
      $id = $billing_account_id;
      $this->tokenAuthorization->setId($id);
    }
    $balance['additionalData']['payerAccount'] = $this->getPayerAccount($params, $id);
    $balance['additionalData']['invoiceId'] = $balance["invoiceId"] ?? '';
    if (isset($params["isPartialPayment"]) && !$params["isPartialPayment"]) {
      $params['amount'] = $balance["dueAmount"];
    }
    if ($balance['dueAmount'] != $params['amount'] && !$params['isPartialPayment']) {
      throw new \Exception("El monto es incorrecto");
    }
    if ($balance["dueAmount"] <= 0 && !$params['isPartialPayment']) {
      throw new \Exception("No se pueden pagar facturas con deuda 0 o con un valor negativo");
    }
    if (isset($balance["noData"]["value"]) && $balance["noData"]["value"] == "empty") {
      throw new \Exception("No se pueden pagar facturas con deuda 0 o con un valor negativo");
    }
    $amount = ($params["isPartialPayment"] && !isset($balance['amountForPartialPayment'])) ? $params["amount"] : $balance["dueAmount"];
    $id = $this->modifyMsisdnForPayment($id);
    $fields = [
      'uuid' => $this->tokenAuthorization->getUserIdPayment(),
      'accountId' => $id,
      'accountNumber' => !empty($balance["accountNumber"]) ? $balance["accountNumber"] : $id,
      'accountType' => $business_unit,
      'productType' => $product_type,
      'amount' => $amount,
      'isPartialPayment' => $params['isPartialPayment'] ? 1 : 0,
      'numberReference' => 0,
      'accessType' => $this->tokenAuthorization->getAccessType(),
    ];
    if (isset($balance['additionalData']) && (!empty($balance['additionalData']))) {
      if (isset($balance['period'])) {
        $balance['additionalData']['period'] = $balance['period'];
      }
      elseif(isset($balance['endPeriod'])) {
        $balance['additionalData']['period'] = $balance['endPeriod'];
      }
      elseif(isset($balance['dueDate'])) {
        $balance['additionalData']['period'] = $balance['dueDate'];
      }
      $balance['additionalData'] += $this->initialData;
      $fields['additionalData'] = serialize($balance['additionalData']);
    }
    $transaction_id = $this->transactions->initTransaction($fields, $product_type);
    $purchaseorder_id = $this->transactions->encryptId($transaction_id);

    $response = [
      'purchaseorderId' => $purchaseorder_id,
      'dueAmount' => $amount,
      'invoiceId' => $balance["invoiceId"],
      'accountNumber' => !empty($balance["accountNumber"]) ? $balance["accountNumber"] : $id,
      'accountId' => $id,
      'payerAccount' => $balance['additionalData']['payerAccount'],
      'isMultipay' => (isset($balance["multipay"]) && $balance["multipay"]) ? TRUE : FALSE,
      'productType' => $this->config['fields']['productType']['value'],
      'additionalData' => $this->initialData,
    ];
    if (isset($balance['period'])) {
      $response['period'] = $balance['period'];
    }
    elseif(isset($balance['endPeriod'])) {
      $response['period'] = $balance['endPeriod'];
    }
    elseif(isset($balance['dueDate'])) {
      $response['period'] = $balance['dueDate'];
    }
    return $response;
  }
}
