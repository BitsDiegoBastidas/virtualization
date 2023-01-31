<?php

namespace Drupal\oneapp_convergent_payment_gateway_bo\Services\v2_0;

use Drupal\oneapp_convergent_payment_gateway\Services\v2_0\TransactionsPaymentRestLogic;
/**
 * Class TransactionsPaymentRestLogicBo.
 */
class TransactionsPaymentRestLogicBo extends TransactionsPaymentRestLogic {

  /**
   * get additional fields
   */
  public function getAdditionalFields($transaction, $config) {
    $payment_gateway = \Drupal::service('oneapp_mobile_payment_gateway_packets.v2_0.payment_gateway_packets_rest_logic');
    $additional_data = (object) unserialize($transaction->additionalData);
    $product_name = isset($additional_data->name) ? $additional_data->name : '';
    $format = isset($config["configs"]["dates"]["nextPayment"]["value"]) ?
      $config["configs"]["dates"]["nextPayment"]["value"] : 'd/M/Y H:i:s';
    // Obtengo un arreglo con dos valores, en value la vigencia en horas y en formattedValue la vigencia formateada en días.
    $validity = $payment_gateway->getFrecuencyFormatted($additional_data);
    if (is_numeric($validity['value'])) {
      $now = new \DateTime();
      $now->modify('+' . $validity['value'] . ' hours');
      $next_payment["formattedValue"] = $now->format($format);
    }
    else {
      $next_payment["formattedValue"] = $validity['value'];
    }
    $transaction->nextPayment = $next_payment["formattedValue"];
    $transaction->frequency = $validity['formattedValue'];
    $transaction->product = $product_name;
    return $transaction;
  }

  /**
   * Actualizando los datos de la base de datos
   */
  public function updateTransaction($transaction, $businessUnit) {
    $orderData = new \stdClass();
    $configApp = (object) \Drupal::config("oneapp.payment_gateway.{$businessUnit}_{$transaction->productType}.config")->get('configuration_app');
    $query['query'] = [];
    if (isset($this->config["configs"]["sendPayment"]["value"]) && $this->config["configs"]["sendPayment"]["value"]) {
      $query['query'] = [
        'forceUpdate' => 'true',
      ];
    }
    $params['orderId'] = $transaction->orderId;
    $params['uuid'] = str_replace("-", "", $transaction->uuid);
    $params['apiHost'] = $configApp->setting_app_payment['api_path'];
    $resporse['stateOrder'] = $transaction->stateOrder;
    if (!empty($params['orderId'])) {
      /** @var \Drupal\aws_service\Services\v2_0\AwsApiManager */
      $aws_manager = \Drupal::service('aws.manager');
      $aws_service = $configApp->setting_app_payment["aws_service"] ?? 'payment';
      $updateTransaction = TRUE;
      try {
        $orderData = $aws_manager->callAwsEndpoint('oneapp_convergent_payment_gateway_v2_0_orders_status_endpoint', $aws_service, [], $params, $query['query'], []);

        if ($orderData->body && property_exists($orderData->body, 'fulfillmentRejectReason')) {
          $fulfillmentRejectReason = json_decode($orderData->body->fulfillmentRejectReason);
          if (isset($fulfillmentRejectReason->error) && $fulfillmentRejectReason->error->statusCode == 403) {
            $message = $fulfillmentRejectReason->error->message;
            if (isset($fulfillmentRejectReason->error->_debug->fault)) {
              $fault = json_decode($fulfillmentRejectReason->error->_debug->fault);
              $message = $fault->error->message;
              throw new \Exception($message);
            }
          }
        }
      }
      catch (\Exception $e) {
        if ($e->getCode() == 500) {
          // TODO: Code is only while PG fix the problem 500 on multipayments.
          $orderData->body->paymentApproved = TRUE;
          $transaction->stateOrder = 'PAYMENT_COMPLETE';
          $updateTransaction = FALSE;
        }
        else {
          throw $e;
        }
      }

      if ($orderData->body && property_exists($orderData->body, 'paymentApproved')) {
        if ($transaction->stateOrder == 'ORDER_IN_PROGRESS' || $transaction->stateOrder == 'PAYMENT_COMPLETE') {
          if ($configApp->setting_app_payment['reversal_payment'] == 0) {
            $resporse['paymentApproved'] = isset($orderData->body->paymentApproved) ? $orderData->body->paymentApproved : '';
            if ($orderData->body->paymentApproved == TRUE) {
              $resporse['stateOrder'] = 'FULFILLMENT_COMPLETE';
            }
            else {
              $resporse['stateOrder'] = 'PAYMENT_NON_COMPLETE';
            }
          }
          else {
            $resporse['paymentApproved'] = isset($orderData->body->paymentApproved) ? $orderData->body->paymentApproved : '';
            if ($orderData->body->paymentApproved == FALSE) {
              $resporse['stateOrder'] = 'PAYMENT_NON_COMPLETE';
            }
            else if (property_exists($orderData->body, 'fulfillmentSucceeded')) {
              $resporse['fulfillmentSucceeded'] = $orderData->body->fulfillmentSucceeded;
              if ($orderData->body->fulfillmentSucceeded == TRUE) {
                $resporse['stateOrder'] = 'FULFILLMENT_COMPLETE';
              }
              else {
                $resporse['stateOrder'] = 'FULFILLMENT_NON_COMPLETE';
              }
            }
            else {
              $resporse['stateOrder'] = 'ORDER_IN_PROGRESS';
            }
          }

          $fieldsUpdate = [];
          if ($updateTransaction) {
            $fieldsUpdate['stateOrder'] = $resporse['stateOrder'];
            $fieldsUpdate['accountNumber'] = !empty($transaction->accountNumber) ? $transaction->accountNumber : $orderData->body->accountNumber;
            $fieldsUpdate['accountType'] = !empty($transaction->accountType) ? $transaction->accountType : $orderData->body->accountType;
            $fieldsUpdate['accountId'] = !empty($transaction->accountId) ? $transaction->accountId : $orderData->body->phoneNumber;
            $fieldsUpdate['maskedAccountId'] = !empty($transaction->paymentInstrument) ? $transaction->productType : $orderData->body->paymentInstrument->maskedAccountId;
            $fieldsUpdate['purchaseOrderId'] = !empty($transaction->purchaseOrderId) ? $transaction->purchaseOrderId : $orderData->body->purchaseOrderId;
            $fieldsUpdate['orderId'] = !empty($transaction->orderId) ? $transaction->orderId : $orderData->body->orderId;
            $fieldsUpdate['numberReference'] = isset($orderData->body->paymentProcessorTransactionId) ? $orderData->body->paymentProcessorTransactionId : $transaction->numberReference;
            $fieldsUpdate['errorCode'] = isset($orderData->body->paymentRejectReason) ? $orderData->body->paymentRejectReason : '';
            $fieldsUpdate['numberAccess'] = isset($orderData->body->paymentAuthorizationCode) ? $orderData->body->paymentAuthorizationCode : $transaction->numberAccess;
            if (isset($orderData->body->paymentApproved)) {
              $fieldsUpdate['paymentApproved'] = $orderData->body->paymentApproved ? 1 : 0;
            }
            if (isset($orderData->body->fulfillmentSucceeded)) {
              $fieldsUpdate['fulfillmentSucceeded'] = $orderData->body->fulfillmentSucceeded ? 1 : 0;
            }
          }
          else {
            $fieldsUpdate['stateOrder'] = $resporse['stateOrder'];
          }
          $this->updateDataTransaction($transaction->id, $fieldsUpdate);
        }
      }
    }

    return $resporse;
  }

}
