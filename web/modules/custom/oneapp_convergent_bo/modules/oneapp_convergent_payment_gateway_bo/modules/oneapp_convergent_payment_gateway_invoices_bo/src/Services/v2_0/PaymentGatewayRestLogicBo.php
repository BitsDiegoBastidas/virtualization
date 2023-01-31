<?php

namespace Drupal\oneapp_convergent_payment_gateway_invoices_bo\Services\v2_0;

use Drupal\oneapp_convergent_payment_gateway_invoices\Services\v2_0\PaymentGatewayRestLogic;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PaymentGatewayRestLogicBo.
 */
class PaymentGatewayRestLogicBo extends PaymentGatewayRestLogic {

  /**
   * PaymentGatewayRestLogic start (Inicializa el proceso de pagos).
   */
  public function start($id, $id_type, $business_unit, $product_type, $params) {
    $this->isB2b($id, $id_type);

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

    if (isset($params["isPartialPayment"]) && !$params["isPartialPayment"]) {
      $params['amount'] = $balance["dueAmount"];
    }
    if ($balance['dueAmount'] != $params['amount'] && !$params['isPartialPayment']) {
      throw new \Exception("El monto es incorrecto");
    }
    if ($balance["dueAmount"] <= 0 && !$params['isPartialPayment']) {
      throw new \Exception("No se pueden pagar facturas con deuda 0 o con un valor negativo");
    }
    $amount = ($params["isPartialPayment"] && !$balance['amountForPartialPayment']) ? $params["amount"] : $balance["dueAmount"];
    $account_number_invoice_payment = $this->utilsPayment->getAccountNumberForPaymentGatewayFromToken($business_unit, $id);
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
      $balance['additionalData']['accountNumberInvoicePayments'] = $account_number_invoice_payment;
      $fields['additionalData'] = serialize($balance['additionalData']);
    }
    else {
      $additional_data = new \Stdclass();
      $additional_data->period = $balance['period'];
      $additional_data->paymentMethod = $params['paymentMethodName'] ?? 'creditCard';
      $additional_data->accountNumberInvoicePayments = $account_number_invoice_payment;
      $fields['additionalData'] = serialize($additional_data);
    }
    $transaction_id = $this->transactions->initTransaction($fields, $product_type);
    $finger_print = $this->utilsPayment->getAttachments($id, $business_unit, $product_type, $transaction_id);
    $purchaseorder_id = $this->transactions->encryptId($transaction_id);
    $response = [
      'fingerPrint' => $finger_print,
      'purchaseorderId' => $purchaseorder_id,
      'dueAmount' => $amount,
      'invoiceId' => $balance["invoiceId"],
      'accountNumber' => !empty($balance["accountNumber"]) ? $balance["accountNumber"] : $id,
      'accountId' => $id,
      'isMultipay' => (isset($balance["multipay"]) && $balance["multipay"]) ? TRUE : FALSE,
      'productType' => t('Pago de factura'),
    ];
    if (isset($balance['period'])) {
      $response['period'] = $balance['period'];
    }
    if ($response['isMultipay']) {
      $config_app = (object) $this->tokenAuthorization->getApplicationSettings('configuration_app');
      $response['applicationName'] = $config_app->setting_app_payment['applicationNameMultipay'];
    }
    return $response;
  }

  /**
   * Genera la orden.
   */
  public function generateOrderId($business_unit, $product_type, $id_type, $id, $purchaseorder_id) {
    $this->isB2b($id, $id_type);

    /* If the line is convergent, the business unit must be changed from mobile to home,
    since the APIs only bring information for home accounts and their debt information
    is the same in home and mobile.*/
    $billing_account_id = $id;
    $this->getVariablesIfConvergent($billing_account_id, $business_unit, $id_type);
    $this->tokenAuthorization->setBusinessUnit($business_unit);
    $this->tokenAuthorization->setIdType($id_type);
    if ($business_unit == 'home') {
      $id = $billing_account_id;
      $this->tokenAuthorization->setId($id);
    }
    $this->params['uuid'] = $this->tokenAuthorization->getUserIdPayment();
    $this->params['tokenUuId'] = $this->tokenAuthorization->getTokenUuid();

    $config_value_default = (object) \Drupal::config('oneapp_convergent_payment_gateway.config')->get('fields_default_values');
    $email_default = $config_value_default->email["send_default_value_email"] ? $config_value_default->email["email_default_value"] : '';

    // login/he/otp/phonePass
    // Email = getEmail(), params['email'] , $email_default.
    $this->params['email'] = (!empty($this->tokenAuthorization->getEmail())) ? $this->tokenAuthorization->getEmail() :
      ((!empty($this->params['email'])) ? $this->params['email'] : $email_default);

    if (!$this->tokenAuthorization->isHe()) {
      $this->params['customerNameToken'] =
        $this->tokenAuthorization->getGivenNameUser() . " " . $this->tokenAuthorization->getFirstNameUser();
    }

    $config_app = $this->tokenAuthorization->getApplicationSettings('configuration_app');
    $this->params['apiHost'] = $config_app["setting_app_payment"]["api_path"];
    if (isset($config_app["setting_app_payment"]["aws_service"])) {
      $this->params['aws_service'] = $config_app["setting_app_payment"]["aws_service"];
    }
    if (empty($this->params['street']) && !empty($this->params['address'])) {
      $this->params['street'] = $this->params['address'];
    }
    $decrypt_purchaseorder_id = $this->transactions->decryptId($purchaseorder_id);
    $data_transaction = $this->transactions->getTransactionById($decrypt_purchaseorder_id);
    if ($data_transaction->stateOrder != "INITIALIZED") {
      throw new \Exception(t('Este pago ya fue realizado'), Response::HTTP_BAD_REQUEST);
    }
    $additional_data = $this->setAdditionalData($data_transaction);
    $additional_data['enrollMe'] = $this->params['enrollMe'] ?? FALSE;
    $data_transaction->additionalData = serialize($additional_data);
    $is_multipay = (isset($additional_data['isMultipay']) && $additional_data['isMultipay']) ? TRUE : FALSE;
    $additional_data_for_payment_body = (isset($additional_data['fieldsForPaymentBody'])) ? $additional_data['fieldsForPaymentBody'] : [];
    if ($is_multipay) {
      $additional_data_for_payment_body['applicationName'] = $config_app["setting_app_payment"]['applicationNameMultipay'];
    }
    $body = $this->utilsPayment
      ->getBodyPayment($business_unit, $product_type, $id_type, $id, $purchaseorder_id, $this->params, $additional_data_for_payment_body);
    $this->addAdditionalPaymentInformation($body, $config_app);
    $order_id = $this->utilsPayment
      ->generateOrderId($body, $business_unit, $product_type, $this->params, $is_multipay);
    $fields = [
      'stateOrder' => "ORDER_IN_PROGRESS",
      'changed' => time(),
      'orderId' => $order_id->body->orderId,
      'transactionId' => $order_id->body->transactionId,
      'additionalData' => $data_transaction->additionalData,
    ];
    $this->transactions->updateDataTransaction($decrypt_purchaseorder_id, $fields);
    $body_logs = $body;
    $body_logs['creditCardDetails'] = [];
    $body_logs['sendCvvEmpty'] = isset($this->params['cvv']) & !empty($this->params['cvv']) ? TRUE : FALSE;
    $fields_log = [
      'purchaseOrderId' => $decrypt_purchaseorder_id,
      'message' => "Order in progress",
      'codeStatus' => 200,
      'operation' => $this->transactions::CREATED_ORDER,
      'description' => "Back office response: \n" . json_encode($order_id->body, JSON_PRETTY_PRINT) .
        "\nBody: \n" . json_encode($body_logs, JSON_PRETTY_PRINT),
      'type' => $product_type,
    ];
    $this->transactions->addLog($fields_log);

    return $order_id->body;
  }

  /**
   * Get get Data for Order Details.
   */
  public function getDataForOrderDetails($id, $id_type, $business_unit, $due_amount) {
    /* If the line is convergent, the business unit must be changed from mobile to home,
    since the APIs only bring information for home accounts and their debt information
    is the same in home and mobile.*/
    if ($this->accountService->isConvergent($id, $id_type)) {
      $this->getVariablesIfConvergent($id, $business_unit, $id_type);
    }
    $config_name = "oneapp_{$business_unit}.config";
    $payment = \Drupal::config($config_name)->getRawData()['payment'];
    $params['isPartialPayment'] = $this->getPartialPayment();
    $params['amount'] = $due_amount;
    if ($payment["minimumAmount"] > $due_amount || $due_amount <= 0) {
      throw new \Exception(t("No se pueden pagar facturas con deuda 0, con un valor negativo o con un valor menor al mínimo establecido"));
    }
    else {
      $balance = $this->utilsPayment->getBalance($id, $id_type, $business_unit, $params);
      $rows = [];
      $account_number = $id;
      $utils = \Drupal::service('oneapp.utils');
      if ($business_unit == "home") {
        $fields = $this->config["fields"]["fields_home"];
      }
      else {
        $fields = $this->config["fields"]["fields_mobile"];
        if (isset($balance["accountNumber"]) && $balance["accountNumber"] != "") {
          $account_number = $balance["accountNumber"];
        }
      }
      foreach ($fields as $id => $field) {
        $row[$id] = [
          'label' => $field['label'],
          'show' => ($field['show']) ? TRUE : FALSE,
        ];
        switch ($id) {
          case 'productType':
            $row[$id]['value'] = isset($fields["productType"]["valueDefault"]) ? $fields["productType"]["valueDefault"] : "---------";
            $row[$id]['formattedValue'] =
              isset($fields["productType"]["valueDefault"]) ? $fields["productType"]["valueDefault"] : "---------";
            break;

          case 'invoiceId':
            $row[$id]['value'] = isset($balance["invoiceId"]) ?
              $balance["invoiceId"] : (isset($fields["invoiceId"]["valueDefault"]) ? $fields["invoiceId"]["valueDefault"] : "---------");
            $row[$id]['formattedValue'] = $row[$id]['value'];
            break;

          case 'acountNumber':
            $row[$id]['value'] = $account_number;
            $row[$id]['formattedValue'] = $account_number;
            break;

          case 'invoiceAmount':
            $row[$id]['value'] = $due_amount;
            $row[$id]['formattedValue'] = $utils->formatCurrency($due_amount, TRUE);
            break;

        }
        $rows[$id] = $row[$id];
      }
    }

    return (array) $rows;
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

}
