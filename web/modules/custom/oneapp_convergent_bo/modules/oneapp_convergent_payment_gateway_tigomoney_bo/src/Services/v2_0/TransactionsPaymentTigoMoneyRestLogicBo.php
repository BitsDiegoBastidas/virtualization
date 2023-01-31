<?php

namespace Drupal\oneapp_convergent_payment_gateway_tigomoney_bo\Services\v2_0;

use Drupal\oneapp_convergent_payment_gateway_bo\Services\v2_0\TransactionsPaymentRestLogicBo;

/**
 * Class TransactionsPaymentTigoMoneyRestLogicBo.
 */
class TransactionsPaymentTigoMoneyRestLogicBo extends TransactionsPaymentRestLogicBo {

  /**
   * Actualizando los datos de la base de datos.
   */
  public function updateTransaction($transaction, $businessUnit) {
    $pos = strpos($transaction->productType, 'tm_');
    if ($pos !== FALSE) {
      $productType = str_replace('tm_', '', $transaction->productType);
    }

    $configApp = (object) \Drupal::config("oneapp.payment_gateway_tigomoney.{$businessUnit}_{$productType}.config")->get('configuration_app');
    $params['orderId'] = $transaction->orderId;
    $params['uuid'] = str_replace("-", "", $transaction->uuid);
    $params['apiHost'] = $configApp->setting_app_payment['api_path'];
    $resporse['stateOrder'] = $transaction->stateOrder;
    if (!empty($params['orderId'])) {
      /** @var \Drupal\aws_service\Services\v2_0\AwsApiManager */
      $aws_manager = \Drupal::service('aws.manager');
      $aws_service = isset($configApp->setting_app_payment["aws_service"]) ? $configApp->setting_app_payment["aws_service"] : 'payment';
      $orderData = $aws_manager->callAwsEndpoint('oneapp_convergent_payment_gateway_v2_0_orders_status_endpoint', $aws_service, [], $params, [], []);

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
            elseif (property_exists($orderData->body, 'fulfillmentSucceeded')) {
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
          $fieldsUpdate['stateOrder'] = $resporse['stateOrder'];
          $fieldsUpdate['accountNumber'] = !empty($transaction->accountNumber) ? $transaction->accountNumber : $orderData->body->accountNumber;
          $fieldsUpdate['accountType'] = !empty($transaction->accountType) ? $transaction->accountType : $orderData->body->accountType;
          $fieldsUpdate['accountId'] = !empty($transaction->accountId) ? $transaction->accountId : $orderData->body->phoneNumber;
          $fieldsUpdate['purchaseOrderId'] = !empty($transaction->purchaseOrderId) ? $transaction->purchaseOrderId : $orderData->body->purchaseOrderId;
          $fieldsUpdate['orderId'] = !empty($transaction->orderId) ? $transaction->orderId : $orderData->body->orderId;
          $fieldsUpdate['numberReference'] = !empty($transaction->numberReference) ? $transaction->numberReference : $orderData->body->productReference;
          if (isset($orderData->body->paymentApproved)) {
            $fieldsUpdate['paymentApproved'] = $orderData->body->paymentApproved ? 1 : 0;
          }
          if (isset($orderData->body->fulfillmentSucceeded)) {
            $fieldsUpdate['fulfillmentSucceeded'] = $orderData->body->fulfillmentSucceeded ? 1 : 0;
          }
          $this->updateDataTransaction($transaction->id, $fieldsUpdate);
        }
      }
    }
    if (isset($orderData->body->paymentRejectReason)) {
      $resporse['message'] = $this->getMappingError($orderData->body->paymentRejectReason);
    }
    if ($orderData->body && property_exists($orderData->body, 'fulfillmentRejectReason')) {
      $fulfillmentRejectReason = json_decode($orderData->body->fulfillmentRejectReason);
      if (isset($fulfillmentRejectReason->error) && $fulfillmentRejectReason->error->statusCode == 400) {
        $message = $fulfillmentRejectReason->error->message;
        if (isset($fulfillmentRejectReason->error->_debug->fault)) {
          $fault = json_decode($fulfillmentRejectReason->error->_debug->fault);
          $message = $fault->error->message;
          $resporse['message'] = t($message);
        }
      }
    }
    return $resporse;
  }

  /**
   * Mapeo del callback
   */
  public function getMappingError($codeError) {
    $configTigomoney = (object) \Drupal::config('oneapp_convergent_payment_gateway.config')->get('tigoMoney');
    if ($configTigomoney->validMappingTigoMoney) {
      $codes = explode(PHP_EOL, $configTigomoney->mappingTigoMoney);
      foreach ($codes as $code) {
        $dataCode = explode("|", $code);
        if ($dataCode[0] == $codeError) {
          return $dataCode[1];
        }
      }
    }
    return '';
  }

}
