<?php

namespace Drupal\oneapp_mobile_payment_gateway_topups_bo\Services\v2_0;

use Drupal\oneapp_mobile_payment_gateway_topups\Services\v2_0\GeneratePurchaseOrdersRestLogic;
use Symfony\Component\HttpFoundation\Response;

/**
 * Declare custom class for topups BO.
 */
class GeneratePurchaseOrdersRestLogicBo extends GeneratePurchaseOrdersRestLogic {

  /**
   * Genera la orden.
   */
  public function generateOrderId($businessUnit, $productType, $idType, $id, $purchaseorderId) {
    $this->params['uuid'] = $this->token->getUserIdPayment();
    $this->params['tokenUuId'] = $this->token->getTokenUuid();
    // Orden de prioridad para correo a utilizar.
    $this->params['email'] = (empty($this->token->getEmail()) && isset($this->params['email'])) ? $this->params['email'] : $this->token->getEmail();
    $this->params['email'] = (empty($this->params['email']) && isset($this->params->billingData['email'])) ? $this->params->billingData["email"] : '';
    if (!$this->token->isHe()) {
      $this->params['customerNameToken'] = $this->token->getGivenNameUser() . " " . $this->token->getFirstNameUser();
    }
    $configApp = $this->utilsPaymentConvergent->getConfigPayment($productType, 'configuration_app', $businessUnit);
    $this->params['apiHost'] = $configApp->setting_app_payment['api_path'];
    $this->params['aws_service'] = isset($configApp->setting_app_payment["aws_service"]) ? $configApp->setting_app_payment["aws_service"] : 'payment';
    $decryptPurchaseorderId = $this->transactions->decryptId($purchaseorderId);
    $dataTransaction = $this->transactions->getTransactionById($decryptPurchaseorderId, []);

    if ($dataTransaction->stateOrder != "INITIALIZED") {
      throw new \Exception(t('Este pago ya fue realizado'), Response::HTTP_BAD_REQUEST);
    }
    if (isset($this->params['billingData'])) {
      $type = $this->utilsPaymentConvergent->saveBillingData($this->params['billingData']);
      $this->params['email'] = (isset($this->params['billingData']['email']) && (strlen($this->params['billingData']['email']) > 0)) ?
        trim($this->params['billingData']['email']) : trim($this->params['email']);
    }

    // Validacion de sobrescritura  oneapp_mobile_payment_gateway_config.
    $addData = [];
    $config_fac = \Drupal::config("oneapp.payment_gateway.mobile_topups.config")->getRawData();
    if ($config_fac['billing_form']['overwrite_data']) {
      $this->validateDataOverWrite($addData, $config_fac);
    }
    $addData["purchaseDetails"][] = [
      "name" => (string) $dataTransaction->numberReference,
      "quantity" => '1',
      "amount" => (string) $dataTransaction->amount,
    ];
    $id = $this->modifyMsisdn($id);
    $body = $this->utilsPaymentConvergent
      ->getBodyPayment($businessUnit, $productType, $idType, $id, $purchaseorderId, $this->params, $addData);

    $order = $this->utilsPaymentConvergent
      ->generateOrderId($body, $businessUnit, $productType, $this->params);

    $this->transactions->updateDataTransactionOrderInProgress($decryptPurchaseorderId, $order);
    $bodyLogs = $body;
    $bodyLogs['creditCardDetails'] = [];
    $fieldsLog = [
      'purchaseOrderId' => $decryptPurchaseorderId,
      'message' => "Order in progress",
      'codeStatus' => 200,
      'operation' => $this->transactions::CREATED_ORDER,
      'description' => "Back office response: \n" . json_encode($order->body, JSON_PRETTY_PRINT) . "\nBody: \n" . json_encode($bodyLogs, JSON_PRETTY_PRINT),
      'type' => $productType,
    ];
    $this->transactions->addLog($fieldsLog);
    return $order->body;
  }

}
