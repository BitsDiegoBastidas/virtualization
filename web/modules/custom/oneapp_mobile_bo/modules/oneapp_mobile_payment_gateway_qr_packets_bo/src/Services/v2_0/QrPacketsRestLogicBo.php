<?php

namespace Drupal\oneapp_mobile_payment_gateway_qr_packets_bo\Services\v2_0;

use Drupal\oneapp\Exception\HttpException;
use Drupal\oneapp_mobile_payment_gateway_qr_packets\Services\v2_0\QrPacketsRestLogic;

class QrPacketsRestLogicBo extends QrPacketsRestLogic {

  /**
   * @var array
   */
  protected $primaryNumber;

  /**
   * @var array
   */
  protected $targetNumber;

  /**
   * @var \Drupal\oneapp_mobile_payment_gateway_packets\Services\PaymentGatewayPacketsService
   */
  protected $paymentService;

  /**
   * @var \Drupal\oneapp_mobile_payment_gateway_packets_bo\Services\v2_0\PaymentGatewayPacketsRestLogicBo
   */
  protected $paymentGatewayService;

  /**
   * @var \Drupal\oneapp_mobile\Services\UtilsService
   */
  protected $mobileUtils;

  public function __construct($oneapp_utils, $qr_rest_logic, $utils_payment, $token_authorization, $transactions, $qr_service, $zero_rate_logic) {
    parent::__construct($oneapp_utils, $qr_rest_logic, $utils_payment, $token_authorization, $transactions, $qr_service, $zero_rate_logic);
    $this->mobileUtils = \Drupal::service('oneapp.mobile.utils');
    $this->paymentService = \Drupal::service('oneapp_mobile_payment_gateway_packets.v2_0.data_service');
    $this->paymentGatewayService = \Drupal::service('oneapp_mobile_payment_gateway_packets.v2_0.payment_gateway_packets_rest_logic');
  }

  /**
   * @return void
   */
  protected function getInfoByClientAccountGeneralInfo() {
    $info = $this->paymentService->getGInfo($this->targetNumber['accountId']);
    if (isset($info->TigoApiResponse->status) && $info->TigoApiResponse->status == "ERROR") {
      $this->targetNumber['info'] = FALSE;
    }
    else {
      foreach ($info->TigoApiResponse->response->contracts->ContractType->accounts->AssetType as $assetType) {
        $this->targetNumber['isQvantel'] = $this->mobileUtils->isQvantel($this->targetNumber['accountId']);
      }
    }
  }

  protected function setAccountInfo() {
    try {
      $info_by_token = $this->mobileUtils->getInfoTokenByMsisdn($this->primaryNumber['accountId']);
      $info_by_token['sourceSystemId'] = strtolower(str_replace(' ', '', $info_by_token['sourceSystemId']));
      $this->primaryNumber['isQvantel'] = $this->mobileUtils->isQvantel($this->primaryNumber['accountId']);

      if (str_replace(' ', '', $this->primaryNumber['accountId']) === str_replace(' ', '', $this->targetNumber['accountId'])) {
        $this->targetNumber['accountId'] = $this->primaryNumber['accountId'];
      }
      else {
        $this->getInfoByClientAccountGeneralInfo();
      }
    }
    catch (HttpException $exception) {
      return FALSE;
    }
  }

  /**
   * @param $id
   * @param $business_unit
   * @param $id_type
   * @param $product_type
   * @param $params
   *
   * @return array
   * @throws \Exception
   */
  public function start($id, $business_unit, $id_type, $product_type, $params): array {
    $account_number = $params['query']['accountNumber'] ?? $id;
    $this->params = $params;
    $this->primaryNumber['accountId'] = $this->mobileUtils->modifyMsisdnCountryCode($id);
    $this->targetNumber['accountId'] = $this->mobileUtils->modifyMsisdnCountryCode($account_number);
    $this->setAccountInfo();

    $this->paymentGatewayService->setPrimaryNumber($this->primaryNumber);
    $this->paymentGatewayService->setTargetNumber($this->targetNumber);

    $is_blocked_payment_he = (bool) $this->configBlock['he_otp']['disable']['value'];
    if ($is_blocked_payment_he && $this->tokenAuthorization->isHe()) {
      return [
        'message' => $this->configBlock['he_otp']['disable']['message'],
        'actions' => [
          'label' => $this->configBlock['he_otp']['disable']['button'],
          'show' => TRUE,
          'type' => "button",
        ],
      ];
    }

    if (isset($this->params['paymentMethodName'])) {
      $this->params['paymentMethod'] = $this->params['paymentMethodName'];
    }

    $offer = $this->paymentGatewayService->getOffer($this->targetNumber['accountId'], $this->params['offerId']);
    $amount = $offer->price[0]->amount ?? ($offer->cost[0]->amount ?? ($offer->cost ?? null));

    if (empty($this->params['amount'])) {
      $this->params['amount'] = $amount;
    }

    $this->qrRestLogic
      ->setConfig($this->configBlock)
      ->setParams($this->params)
      ->setProductType($product_type)
      ->setProduct($offer);

    $card_brand = t('QR Simple');
    if (!empty($this->params['paymentMethod']) && $this->params['paymentMethod'] == 'tigoQrPos') {
      $card_brand = 'QR MiTigo Code';
    }

    $fields = [
      'uuid' => $this->tokenAuthorization->getUserIdPayment(),
      'cardBrand' => $card_brand,
      'accountId' => $id,
      'accountNumber' => $account_number,
      'accountType' => $business_unit,
      'productType' => $product_type,
      'amount' => $amount,
      'isPartialPayment' => 0,
      'numberReference' => $offer->offerId,
      'additionalData' => serialize($offer),
      'accessType' => $this->tokenAuthorization->getAccessType(),
    ];

    $transaction = $this->initPaymentQr($id, $account_number, $id_type, $business_unit, $product_type, $fields);
    $transaction_id = $transaction['transactionId'] ?? 0;
    $purchaseorder_id = $transaction['purchaseorderId'] ?? 0;

    $purchaseorder_id = $this->transactions->encryptId($transaction_id);

    $data_offer = $this->formatOffers($offer, $fields, $purchaseorder_id);
    $billing_form = $this->utilsPayment->getBillingDataForm('packets', 'mobile');
    if (empty($billing_form)) {
      $billing_form = [];
    }
    if (!empty($transaction['transactionExist']) && !empty($billing_form['billingDataForm'])) {
      foreach ($billing_form['billingDataForm'] as $key => $value) {
        $billing_form['billingDataForm'][$key]['validations']['edit'] = FALSE;
      }
    }

    $response = [
      'forms' => $billing_form,
      'data' => [],
      'cards' => [],
      'offer' => $data_offer,
      'actions' => $this->configBlock["actions"],
    ];
    if ($this->tokenAuthorization->isHe()) {
      $response['message'] = $this->configBlock['he_otp']['flow']['message'];
      $response['actions']['messageButton'] = [
        'label' => $this->configBlock['he_otp']['flow']['button'],
        'show' => TRUE,
        'type' => 'button',
      ];
    }
    foreach ($response['actions'] as $key => $action) {
      $response['actions'][$key]['show'] = (bool) $action['show'];
    }

    return $response;
  }

  /**
   * @param $id
   * @param $business_unit
   * @param $id_type
   * @param $product_type
   * @param $data
   * @param $purchaseorder_id
   *
   * @return array
   * @throws \Exception
   */
  public function generateOrderId($id, $business_unit, $id_type, $product_type, $data, $purchaseorder_id): array {
    $this->transactions->setSuffix($product_type);
    $decrypt_purchaseorder_id = $this->transactions->decryptId($purchaseorder_id);
    $data_transaction = $this->transactions->getTransactionById($decrypt_purchaseorder_id, []);
    $this->params['amount'] = $data_transaction->amount ?? 0;
    $this->params['offerId'] = $data_transaction->numberReference ?? 0;
    $transaction = [
      'purchaseorderId' => $purchaseorder_id,
      'transactionId' => $data_transaction->id,
    ];
    if ($data_transaction->stateOrder != "INITIALIZED") {
      $data_payment = $this->qrRestLogic->getData($id, $transaction['transactionId']);
    }
    else {
      $additional_data = unserialize($data_transaction->additionalData);
      $mobile_packets_async_logic = \Drupal::service('oneapp_mobile_payment_gateway_packets.v2_0.payment_gateway_packets_async_rest_logic');
      $offer = $mobile_packets_async_logic->getOffer(
        $data_transaction->accountNumber,
        ($additional_data->offerId ?? $additional_data['product']->offerId)
      );
      $this->setProductType($product_type);
      $this->setProduct($offer);
      $account_number = $data_transaction->accountNumber ?? $id;
      $data_payment = $this->updatePayment($id, $account_number, $id_type, $business_unit, $product_type, $transaction);
    }

    $data = array_merge($transaction, $data_payment);
    return [
      'data' => $this->qrRestLogic->getFormat($data),
      'title' => [
        'value' => $this->configBlock['label'],
        'show' => $this->configBlock['label_display'] == 'visible'
      ],
      'description' => $this->configBlock['description'],
      'footer' => $this->configBlock['footer'],
      'message' => $this->qrRestLogic->getMessages(),
      'actions' => $this->qrRestLogic->getActions($this->params),
    ];
  }
}
