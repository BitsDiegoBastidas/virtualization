<?php

namespace Drupal\oneapp_mobile_payment_gateway_packets_bo\Services\v2_0;

use Drupal\oneapp\Exception\HttpException;
use Drupal\oneapp_mobile_payment_gateway_packets\Services\v2_0\PaymentGatewayPacketsRestLogic;
use Drupal\oneapp_mobile_upselling_bo\Services\v2_0\OfferDetailsRestLogicBo;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PaymentGatewayPacketsRestLogicBo.
 */
class PaymentGatewayPacketsRestLogicBo extends PaymentGatewayPacketsRestLogic {

  /**
   * @param $id
   * @param $business_unit
   * @param $product_type
   * @param $params
   * @param $request
   * @param $target_msisdn
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function start($id, $business_unit, $product_type, $params, $request, $target_msisdn) {
    $this->primaryNumber['accountId'] = $id;
    $this->targetNumber['accountId'] = $target_msisdn ?? $id;
    $target_msisdn = $this->targetNumber['accountId'] = $this->modifyMsisdn($this->targetNumber['accountId']);
    $this->getInfoByMsisdn();
    $is_blocked_payment_he = (bool) $this->config['he_otp']['disable']['value'];
    if ($is_blocked_payment_he && $this->tokenAuthorization->isHe()) {
      return [
        'message' => $this->config['he_otp']['disable']['message'],
        'actions' => [
          'label' => $this->config['he_otp']['disable']['button'],
          'show' => TRUE,
          'type' => "button",
        ],
      ];
    }
    $mobile_utils_service = \Drupal::service('oneapp.mobile.utils');
    $module_config = $this->utilsPayment->getConfigPayment($product_type, '', $business_unit);
    if (isset($module_config->configuration_app['setting_app_payment']['enableBilingAccountByMsisdn']) && $module_config->configuration_app['setting_app_payment']['enableBilingAccountByMsisdn']) {
      $params['billingAccountId'] = $mobile_utils_service->getBillingAccountByMsisdn($id);
      $billing_account_id = $params['billingAccountId'];
    }
    $offer = $this->getOffer($this->targetNumber['accountId'], $params["offerId"]);
    $id = $this->modifyMsisdn($id);
    $taxes = FALSE;
    if (isset($module_config->taxes['active']) && $module_config->taxes['active']) {
      $taxes = TRUE;
      $this->setTaxes($offer, $module_config->taxes['tax']);
    }
    $amount = ($taxes) ? $offer->totalCost : $offer->cost;
    if (is_float($amount)) {
      $amount = number_format($amount, 2, '.', '');
    }
    $params['query']['accountNumber'] = (isset($target_msisdn)) ? $target_msisdn : $id;
    $fields = [
      'uuid' => $this->tokenAuthorization->getUserIdPayment(),
      'accountId' => $id,
      'accountNumber' => (isset($target_msisdn)) ? $target_msisdn : $id,
      'accountType' => $business_unit,
      'productType' => $product_type,
      'amount' => $amount,
      'isPartialPayment' => 0,
      'numberReference' => $offer->offerId,
      'additionalData' => serialize($offer),
      'accessType' => $this->tokenAuthorization->getAccessType(),
    ];
    $transaction_id = $this->transactions->initTransaction($fields, $product_type);
    $finger_print = $this->utilsPayment->getAttachments($id, $business_unit, $product_type, $transaction_id);
    $purchaseorder_id = $this->transactions->encryptId($transaction_id);
    $data = [
      'fingerPrint' => $finger_print,
      'purchaseorderId' => $purchaseorder_id,
    ];
    $data_offer = $this->formatOffers($offer, $fields, $taxes);
    $billing_form = $this->utilsPayment->getBillingDataForm('packets', 'mobile');
    $type_form = $this->isAutoPackets ? 'autopackets' : $product_type;
    $new_card_form = $this->utilsPayment->getFormPayment($type_form, $data);
    if ($billing_form) {
      $forms = [$new_card_form, $billing_form];
    }
    else {
      $forms = $new_card_form;
    }
    $actions = $this->config["actions"];
    if ($this->isLowDenominations($transaction_id)) {
      $params['query']['applicationName'] = $this->utilsPayment->getLowDenominationsAppName($product_type, $business_unit);
    }
    $response = [
      'forms' => $forms,
      'data' => $data,
      'cards' => $this->utilsPayment->getCards($business_unit, $params),
      'offer' => $data_offer,
      'actions' => $actions,
    ];
    if ($this->tokenAuthorization->isHe()) {
      $response['message'] = $this->config['he_otp']['flow']['message'];
      $response['actions']['messageButton'] = [
        'label' => $this->config['he_otp']['flow']['button'],
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
   * @param $msisdn
   * @param $package_id
   *
   * @return object
   * @throws \Exception
   */
  public function getOffer($msisdn, $package_id) {
    $module_config = $this->utilsPayment->getConfigPayment('packets', 'configuration_app', 'mobile');

    if (\Drupal::hasService('oneapp_mobile_upselling.v2_0.offer_details_rest_logic')) {
      $service = \Drupal::service('oneapp_mobile_upselling.v2_0.offer_details_rest_logic');
      try {
        if ($msisdn == $this->primaryNumber['accountId']) {
          $offer = (object) $service->get($msisdn, $package_id);

          if (isset($offer->data->error) || isset($offer->error)) {
            throw new \Exception(t('La oferta no es correcta'), Response::HTTP_BAD_REQUEST);
          }
        }
        elseif ($msisdn == $this->targetNumber['accountId']) {
          if ($this->primaryNumber['isQvantel'] && $this->targetNumber['isQvantel']) {
            $offer = (object) $service->get($msisdn, $package_id);

            if (isset($offer->data->error) || isset($offer->error)) {
              throw new \Exception(t('La oferta no es correcta'), Response::HTTP_BAD_REQUEST);
            }
          }
          elseif ($this->primaryNumber['isQvantel']) {
            $offer_id = $this->service->getIdOfferBySystemOfferId($package_id);
            $offer = (object) $service->get($msisdn, $offer_id);

            if (isset($offer->data->error) || isset($offer->error)) {
              throw new \Exception(t('La oferta no es correcta'), Response::HTTP_BAD_REQUEST);
            }
          }
          elseif ($this->targetNumber['isQvantel']) {
            $system_offer_id = $this->service->getSystemOfferIdByIdOffer($package_id);
            $offer = (object) $service->get($msisdn, $system_offer_id);

            if (isset($offer->data->error) || isset($offer->error)) {
              throw new \Exception(t('La oferta no es correcta'), Response::HTTP_BAD_REQUEST);
            }
          }
          else {
            $offer = (object) $service->get($msisdn, $package_id);

            if (isset($offer->data->error) || isset($offer->error)) {
              throw new \Exception(t('La oferta no es correcta'), Response::HTTP_BAD_REQUEST);
            }
          }
        }
      }
      catch (\Exception $e) {
        throw new \Exception(t('La oferta no es correcta'), Response::HTTP_BAD_REQUEST);
      }
    }
    else {
      try {
        $offer = $this->service->getPacketsInfoApi($msisdn, $package_id);
        if (isset($offer->data->error) || isset($offer->error)) {
          throw new \Exception(t('La oferta no es correcta'), Response::HTTP_BAD_REQUEST);
        }
        if (isset($offer->data)) {
          $offer = $offer->data;
        }
      }
      catch (\Exception $e) {
        throw new \Exception(t('La oferta no es correcta'), Response::HTTP_BAD_REQUEST);
      }
    }

    if (is_array($offer->cost)) {
      $currency = $module_config->setting_app_payment['currency'];
      foreach ($offer->cost as $cost) {
        $cost = (array) $cost;
        if ($currency == $cost['currencyId']) {
          $offer->cost = $cost['amount'];
          break;
        }
      }
    }
    $this->translateValidityType($offer->validityType);

    return $offer;
  }

  /**
   * Returns data for msisdn.
   */
  public function getInfoByMsisdn() {
    try {
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
   * {@inheritdoc}
   */
  public function getInfoByClientAccountGeneralInfo() {
    $info = $this->service->getGInfo($this->targetNumber['accountId']);
    if (isset($info->TigoApiResponse->status) && $info->TigoApiResponse->status == "ERROR") {
      $this->targetNumber['info'] = FALSE;
    }
    else {
      foreach ($info->TigoApiResponse->response->contracts->ContractType->accounts->AssetType as $assetType) {
        $this->targetNumber['isQvantel'] = $this->mobileUtils->isQvantel($this->targetNumber['accountId']);
      }
    }
  }

  /**
   * Generate the order.
   */
  public function generateOrderId($business_unit, $product_type, $id_type, $id, $purchaseorder_id) {

    $is_blocked_payment_he = (bool) $this->config['he_otp']['disable']['value'];
    if ($is_blocked_payment_he && $this->tokenAuthorization->isHe()) {
      throw new \Exception($this->config['he_otp']['disable']['message'], Response::HTTP_BAD_REQUEST);
    }

    $this->params['uuid'] = $this->tokenAuthorization->getUserIdPayment();
    $this->params['tokenUuId'] = $this->tokenAuthorization->getTokenUuid();
    // Orden de prioridad para correo a utilizar.
    $this->params['email'] = (empty($this->tokenAuthorization->getEmail()) && isset($this->params['email'])) ? $this->params['email'] : $this->tokenAuthorization->getEmail();
    $this->params['email'] = (empty($this->params['email']) && isset($this->params->billingData['email'])) ? $this->params->billingData["email"] : '';
    if (!$this->tokenAuthorization->isHe()) {
      $this->params['customerNameToken'] = $this->tokenAuthorization->getGivenNameUser() .
        " " . $this->tokenAuthorization->getFirstNameUser();
    }
    if (isset($this->params['billingData'])) {
      $type = $this->utilsPayment->saveBillingData($this->params['billingData']);
      $this->params['email'] = (isset($this->params['billingData']['email']) && (strlen($this->params['billingData']['email']) > 0)) ?
        trim($this->params['billingData']['email']) : trim($this->params['email']);
    }
    // Validacion de sobrescritura  oneapp_mobile_payment_gateway_config.
    $add_data = [];
    $config_fac = \Drupal::config("oneapp.payment_gateway.mobile_packets.config")->getRawData();
    if ($config_fac['billing_form']['overwrite_data']) {
      $this->validateDataOverWrite($add_data, $config_fac);
    }
    $decrypt_purchaseorder_id = $this->transactions->decryptId($purchaseorder_id);
    $config_app = $this->utilsPayment->getConfigPayment($product_type, 'configuration_app', $business_unit);
    $this->params['apiHost'] = $config_app->setting_app_payment['api_path'];
    $this->params['aws_service'] = isset($config_app->setting_app_payment["aws_service"]) ?
      $config_app->setting_app_payment["aws_service"] : 'payment';
    $data_transaction = $this->transactions->getTransactionById($decrypt_purchaseorder_id, []);

    if ($data_transaction->stateOrder != "INITIALIZED") {
      throw new \Exception(t('Este pago ya fue realizado'), Response::HTTP_BAD_REQUEST);
    }

    $add_data["purchaseDetails"][] = [
      "name" => (string) $data_transaction->numberReference,
      "quantity" => '1',
      "amount" => (string) $data_transaction->amount,
    ];

    $this->params['packageId'] = $data_transaction->numberReference;
    $id = $this->modifyMsisdn($id);
    $body = $this->utilsPayment
      ->getBodyPayment($business_unit, $product_type, $id_type, $id, $purchaseorder_id, $this->params, $add_data);
    $order = $this->utilsPayment
      ->generateOrderId($body, $business_unit, $product_type, $this->params);
    $additional_data = [];
    if ($this->isAutoPackets) {
      if (isset($this->params) && isset($this->params["tokenizedCardId"])) {
        $additional_data = unserialize($data_transaction->additionalData);
        $additional_data->paymentTokenId = $this->params["tokenizedCardId"];
      }
      if (isset($this->params["numberCard"])) {
        $additional_data = unserialize($data_transaction->additionalData);
        $additional_data->numberCard = $this->params["numberCard"];
      }
      $additional_data->billingData = $this->params["billingData"];
    }
    $this->transactions->updateDataTransactionOrderInProgress($decrypt_purchaseorder_id, $order, $additional_data);
    $body_logs = $body;
    $body_logs['creditCardDetails'] = [];
    $fields_log = [
      'purchaseOrderId' => $decrypt_purchaseorder_id,
      'message' => "Order in progress",
      'codeStatus' => 200,
      'operation' => $this->transactions::CREATED_ORDER,
      'description' => "Back office response: \n" . json_encode($order->body, JSON_PRETTY_PRINT) . "\nBody: \n" . json_encode($body_logs, JSON_PRETTY_PRINT),
      'type' => $product_type,
    ];
    $this->transactions->addLog($fields_log);
    return $order->body;
  }

  /**
   * Format offer.
   */
  public function formatOffers($offer, $fields, $taxes = FALSE) {
    $row = [];
    $oneapp_utils = \Drupal::service('oneapp.utils');
    $mobile_service = \Drupal::service('oneapp.mobile.utils');
    $validity = isset($offer->validity) ? $offer->validity : $offer->validityNumber . ' ' . $offer->validityType;
    $row['offerId'] = [
      'label' => $this->config["fields"]["offerId"]["label"],
      'show' => $this->config["fields"]["offerId"]["show"] ? TRUE : FALSE,
      'value' => $offer->offerId,
      'formattedValue' => (string) $offer->offerId,
    ];
    $row['accountNumber'] = [
      'label' => $this->config["fields"]["msisdn"]["label"],
      'show' => $this->config["fields"]["msisdn"]["show"] ? TRUE : FALSE,
      'value' => $fields['accountNumber'],
      'formattedValue' => (string) $mobile_service->modifyMsisdnCountryCode($fields['accountNumber'], FALSE),
    ];
    $row['offerName'] = [
      'label' => $this->config["fields"]["offerName"]["label"],
      'show' => $this->config["fields"]["offerName"]["show"] ? TRUE : FALSE,
      'value' => $offer->name,
      'formattedValue' => (string) $offer->name,
    ];
    $row['description'] = [
      'label' => $this->config["fields"]["description"]["label"],
      'show' => $this->config["fields"]["description"]["show"] ? TRUE : FALSE,
      'value' => $offer->description,
      'formattedValue' => (string) $offer->description,
    ];
    $row['categoryName'] = [
      'label' => $this->config["fields"]["categoryName"]["label"],
      'show' => $this->config["fields"]["categoryName"]["show"] ? TRUE : FALSE,
      'value' => $offer->category,
      'formattedValue' => $offer->category,
    ];
    $row['validity'] = [
      'label' => $this->config["fields"]["validity"]["label"],
      'show' => $this->config["fields"]["validity"]["show"] ? TRUE : FALSE,
      'value' => isset($offer->validity) ? $offer->validity : $offer->validityNumber,
      'formattedValue' => $validity,
    ];
    if ($taxes) {
      $row['subtotalAmount'] = [
        'label' => $this->config["fields"]["subtotalAmount"]["label"],
        'show' => $this->config["fields"]["subtotalAmount"]["show"] ? TRUE : FALSE,
        'value' => $offer->cost,
        'formattedValue' => $oneapp_utils->formatCurrency($offer->cost, TRUE),
      ];
      $row['tax'] = [
        'label' => $this->config["fields"]["tax"]["label"],
        'show' => $this->config["fields"]["tax"]["show"] ? TRUE : FALSE,
        'value' => $offer->tax,
        'formattedValue' => $oneapp_utils->formatCurrency($offer->taxValue, TRUE),
      ];
    }
    $amount = ($taxes) ? $offer->totalCost : $offer->cost;
    $row['amount'] = [
      'label' => $this->config["fields"]["amount"]["label"],
      'show' => $this->config["fields"]["amount"]["show"] ? TRUE : FALSE,
      'value' => $amount,
      'formattedValue' => $oneapp_utils->formatCurrency($amount, TRUE),
    ];

    if (isset($this->config["fields"]["nextPayment"]) && isset($this->config["fields"]["frequency"])) {
      $date = new \DateTime();
      $date_formatter = \Drupal::service('date.formatter');
      $validity = $this->getFrecuencyFormatted($offer);
      if (is_numeric($validity['value'])) {
        $expiration_date = $oneapp_utils->formatDateRegressiveWithDuration($date->format('Y-m-d H:i:s'), $validity['value'], FALSE);
        $expiration_date["formattedValue"] = $date_formatter->format(strtotime($expiration_date["value"]),
          $this->config["configs"]["dates"]["expirationDate"]);
      }
      else {
        $expiration_date["value"] = $validity['value'];
        $expiration_date["formattedValue"] = $validity['value'];
      }
      $row['nextPayment'] = [
        'label' => $this->config["fields"]["nextPayment"]["label"],
        'show' => $this->config["fields"]["nextPayment"]["show"] ? TRUE : FALSE,
        'value' => $expiration_date["value"],
        'formattedValue' => $expiration_date["formattedValue"],
      ];
      $row['frequency'] = [
        'label' => $this->config["fields"]["frequency"]["label"],
        'show' => $this->config["fields"]["frequency"]["show"] ? TRUE : FALSE,
        'value' => $validity['value'],
        'formattedValue' => $validity['formattedValue'],
      ];
    }

    return $row;
  }

  /**
   * Get autopackets purchase frequency.
   */
  public function getFrecuencyFormatted($offer) {
    $validity = isset($offer->validity) ? $offer->validity : $offer->validityNumber . ' ' . $offer->validityType;
    switch (str_replace(' ', '', $offer->validityType)) {
      case 'Horas':
      case 'HORAS':
      case 'horas':
        $value = $offer->validityNumber;
        if ($value / 24 >= 1) {
          $days = intval($value / 24);
          $formatted_value = ($days == 1) ? t('@day día', ['@day' => $days]) :
            t('@day días', ['@day' => $days]);
        }
        else {
          $formatted_value = ($value == 1) ? t('@hour hora', ['@hour' => $value]) : t('@hours horas', ['@hours' => $value]);
        }
      return [
        'value' => $value,
        'formattedValue' => $formatted_value,
      ];
      break;

      case 'Días':
      case 'DIAS':
      case 'Día':
      case 'día':
        $hours = $offer->validityNumber * 24;
        $formatted_value = ($offer->validityNumber == 1) ? t('@day día', ['@day' => $offer->validityNumber]) :
          t('@day días', ['@day' => $offer->validityNumber]);
      return [
        'value' => $hours,
        'formattedValue' => $formatted_value,
      ];
      break;

      case 'mes':
      case 'Mes':
        $hours = $offer->validityNumber * 30 * 24;
        $days = $offer->validityNumber * 30;
      $formatted_value = ($days == 1) ? t('@day día', ['@day' => $days]) : t('@day días', ['@day' => $days]);
      return [
        'value' => $hours,
        'formattedValue' => $formatted_value,
      ];
      break;

    }
    switch (str_replace(' ', '', $offer->validityNumber)) {
      case 'Hoy':
      case 'hoy':
        try {
          $validity = explode(') ', $offer->validityType);
          $date = new \DateTime($validity[1]);
          $now = new \DateTime();
          $dif = $date->diff($now);
          $validity = $dif->h;
          if ($validity / 24 >= 1) {
            $days = intval($validity / 24);
            $formatted_value = ($days == 1) ? t('@day día', ['@day' => $days]) :
              t('@day días', ['@day' => $days]);
          }
          else {
            $formatted_value = ($validity == 1) ? t('@hour hora', ['@hour' => $validity]) : t('@hours horas', ['@hours' => $validity]);
          }
          return [
            'value' => $validity,
            'formattedValue' => $formatted_value,
          ];
        }
        catch (\Exception $e) {
          $validity = 0;
        }
        break;

      case 'mañana':
      case 'Mañana':
        try {
          $validity = explode(') ', $offer->validityType);
          $date = new \DateTime($validity[1]);
          $now = new \DateTime();
          $dif = $date->diff($now);
          $validity = $dif->h + 24;
          if ($validity / 24 >= 1) {
            $days = intval($validity / 24);
            $formatted_value = ($days == 1) ? t('@day día', ['@day' => $days]) :
              t('@day días', ['@day' => $days]);
          }
          else {
            $formatted_value = ($validity == 1) ? t('@hour hora', ['@hour' => $validity]) : t('@hours horas', ['@hours' => $validity]);
          }
          return [
            'value' => $validity,
            'formattedValue' => $formatted_value,
          ];
        }
        catch (\Exception $e) {
          $validity = 24;
        }
        break;
    }
    return [
      'value' => $validity,
      'formattedValue' => $offer->validityNumber . ' ' . $offer->validityType,
    ];

  }

  /**
   * @param mixed $primaryNumber
   */
  public function setPrimaryNumber($primaryNumber) {
    $this->primaryNumber = $primaryNumber;
  }

  /**
   * @param mixed $targetNumber
   */
  public function setTargetNumber($targetNumber) {
    $this->targetNumber = $targetNumber;
  }
}
