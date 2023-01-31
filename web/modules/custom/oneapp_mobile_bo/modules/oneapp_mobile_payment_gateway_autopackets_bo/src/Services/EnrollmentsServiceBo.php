<?php

namespace Drupal\oneapp_mobile_payment_gateway_autopackets_bo\Services;

use Drupal\oneapp_mobile_payment_gateway_autopackets\Services\EnrollmentsService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class EnrollmentsServiceBo.
 */
class EnrollmentsServiceBo extends EnrollmentsService {

  /**
   * get Offer for Id
   */
  public function getOffer($id, $offer_id) {
    $available_service = \Drupal::service('oneapp_mobile_upselling.v2_0.available_offers_rest_logic');
    $offers = $available_service->getOffers($id);
    if (count($offers) > 0) {
      foreach ($offers as $offer) {
        if ($offer->packageId == $offer_id) {
          $offer->validityNumber = $this->getSuscriptionDurationInHours($offer);
          $offer->validityType = 'horas';
          return $offer;
        }
      }
    }
    return [];
  }

  /**
   * get Subscription Data
   */
  public function getSubscriptionData($id) {
    $offer = $this->getOffer($id, $this->params["offerId"]);
    $hours = $this->getSuscriptionDurationInHours($offer);
    $this->params['subscription'] = [
      'name' => isset($offer->name) ? $offer->name : '',
      'amount' => isset($offer->cost) ? (string) $offer->cost : '',
      'duration' => isset($hours) && $hours > 0 ? $this->getDurationRenewOffer($hours) : '',
      'productReference' => (string) $this->params["offerId"],
      'lastOrderTimeStamp' => str_replace(" ", "T", date_format(date_create(), 'Y-m-d H:i:sP')),
    ];
  }

  /**
   * get Subscription Data of additional data
   */
  public function getSubscriptionDataOfAdditionalData($id, $addtional_data) {
    $hours = $this->getSuscriptionDurationInHours($addtional_data);
    $this->params['subscription'] = [
      'name' => $addtional_data->name,
      'amount' => (string) $addtional_data->cost,
      'duration' => $this->getDurationRenewOffer($hours),
      'productReference' => (string) $addtional_data->offerId,
      'lastOrderTimeStamp' => str_replace(" ", "T", date_format(date_create(), 'Y-m-d H:i:sP')),
    ];
    $this->params['billingData'] = $addtional_data->billingData;
  }

  /**
   * get Token card of database
   */
  public function getTokenizedCardOfDatabase($addtional_data) {
    if (empty($this->params["tokenizedCardId"])) {
      if (isset($addtional_data->paymentTokenId)) {
        $this->params['tokenizedCardId'] = isset($this->params['tokenizedCardId']) ?
          $this->params['tokenizedCardId'] : $addtional_data->paymentTokenId;
      }
      if (isset($addtional_data->numberCard)) {
        $this->params['tokenizedCardId'] = isset($this->params['tokenizedCardId']) ?
          $this->params['tokenizedCardId'] : $this->getPaymentTokenId($this->transaction->accountNumber, $addtional_data->numberCard);
      }
    }
  }

  /**
   * get Offer for Id with format
   */
  public function getOfferWithFormat($id, $offer_id) {
    $oneapp_utils = \Drupal::service('oneapp.utils');
    $offer_details = \Drupal::service('oneapp_mobile_upselling.v2_0.offer_details_rest_logic');
    $offer = $this->getOffer($id, $offer_id);
    $validity['value'] = isset($offer->validityNumber) ? $offer->validityNumber : $offer->durationTime;
    $validity['formattedValue'] = isset($offer->validityNumber) ? $offer->validityNumber . ' ' . $offer->validityType :  '';
    $date_formatter = \Drupal::service('date.formatter');
    $date = new \DateTime();
    $expiration_date = $oneapp_utils->formatDateRegressiveWithDuration($date->format('Y-m-d H:i:s'), $validity['value'], false);
    $data = [
      'offerId' => isset($offer->packageId) ? $offer->packageId: '',
      'offerName' => isset($offer->name) ? $offer->name : '',
      'description' => isset($offer->description) ? $offer->description : '',
      'categoryName' => $offer->category,
      'validity' => $validity['formattedValue'],
      'amount' => isset($offer->acquisitionMethods[0]->priceList[0]->ammount) ?
        $oneapp_utils->formatCurrency($offer->acquisitionMethods[0]->priceList[0]->ammount, TRUE) :
        $oneapp_utils->formatCurrency($offer->cost, TRUE),
      'nextPayment' => $date_formatter->format(strtotime($expiration_date["value"]), $this->config["configs"]["dates"]["expirationDate"]),
      'frequency' => $validity['formattedValue'],
    ];
    return $data;
  }

  /**
   * Obtener duración de suscripción en horas.
   */
  public function getSuscriptionDurationInHours($offer) {
    $validity = isset($offer->validity) ? $offer->validity : $offer->validityNumber . ' ' . $offer->validityType;
    switch (str_replace(' ', '', $offer->validityType)) {
      case 'Horas':
      case 'HORAS':
      case 'horas':
      case 'hours':
      case 'HOURS':
        return $offer->validityNumber;
        break;

      case 'Días':
      case 'DIAS':
      case 'Día':
      case 'día':
      case 'DAY':
      case 'DAYS':
      case 'day':
      case 'days':
        return $offer->validityNumber * 24;
        break;

      case 'mes':
      case 'Mes':
      case 'month':
      case 'MONTH':
        return $offer->validityNumber * 30 * 24;
        break;
    }

    switch (str_replace(' ', '', $offer->validityNumber)) {
      case 'Hoy':
      case 'hoy':
      case 'TODAY':
      case 'today':
        try {
          $validity = explode(') ', $offer->validityType);
          $date = new \DateTime($validity[1]);
          $now = new \DateTime();
          $dif = $date->diff($now);
          $validity = $dif->h;
        }
        catch (\Exception $e) {
          $validity = 24;
        }
        break;

      case 'mañana':
      case 'Mañana':
      case 'tomorrow':
      case 'TOMORROW':
      try {
        $validity = explode(') ', $offer->validityType);
        $date = new \DateTime($validity[1]);
        $now = new \DateTime();
        $dif = $date->diff($now);
        $validity = $dif->h + 24;
      }
      catch (\Exception $e) {
        $validity = 48;
      }
      break;
    }

    return $validity;
  }

  /**
   * get params of subscribers
   */
  public function getParamsSubscribers($id) {
    $this->params['uuid'] = $this->tokenAuthorization->getUserIdPayment();
    $this->params['tokenUuId'] = $this->tokenAuthorization->getTokenUuid();
    $addtional_data = isset($this->transaction->additionalData) ? unserialize($this->transaction->additionalData) : [];
    $email_transaction = isset($addtional_data->billingData["email"]) ? $addtional_data->billingData["email"] : '';
    $email_transaction = (empty($email_transaction) && isset($addtional_data->email)) ? $addtional_data->email : $email_transaction;
    $this->params['email'] = empty($email_transaction) ?
      $this->tokenAuthorization->getEmail() : $email_transaction;

    if (!isset($this->params['accountNumber'])) {
      $this->params['accountNumber'] = $id;
    }
    $this->getTokenizedCardOfDatabase($addtional_data);
    if (!empty($addtional_data)) {
      $this->getSubscriptionDataOfAdditionalData($id, $addtional_data);
    }
    elseif (!empty($this->params["offerId"])) {
      $this->getSubscriptionData($id);
    }
  }

  /**
   * Get Body
   */
  public function getBody($id) {
    $config_app = (object) $this->tokenAuthorization->getApplicationSettingsAutoPackets("configuration_app");
    $config_invoice = \Drupal::config("oneapp.payment_gateway.mobile_invoices.config")->get("configuration_app");
    $config_fields = $this->utilsPayment->validateConfigPaymentForms($this->params, 'invoices_autopayments');
    $config_convergente = (object) $this->tokenAuthorization->getConvergentPaymentGatewaySettings('fields_default_values');
    $name_default = $config_convergente->name["send_default_value_name"] ?
      $config_convergente->name["name_default_value"] . ' ' . $config_convergente->name["last_name_default_value"] : '';
    $email_default = $config_convergente->name["send_default_value_name"] ?
      $config_convergente->name["name_default_value"] . ' ' . $config_convergente->name["last_name_default_value"] : '';
    $default_invoice_config = \Drupal::config("oneapp.payment_gateway.mobile_packets.config")->getRawData();
    $document_number = $this->getDocumentNumber($default_invoice_config);
    $params['nit'] = !isset($this->params["billingData"]["nit"]) ? '' : '';
    $this->params['apiHost'] = $config_app->setting_app_payment['api_path'];
    $customer_name = (isset($this->params['billingData']['fullname'])) ? $this->params['billingData']['fullname'] :
      $this->tokenAuthorization->getGivenNameUser() . ' ' . $this->tokenAuthorization->getFirstNameUser();
    $customer_name = $this->utilsPayment->clearString($customer_name);
    $customer_name = empty($customer_name) || $customer_name == ' ' ? $name_default : $customer_name;
    $this->params['name'] = $customer_name;
    $this->params['customerName'] = $customer_name;
    $formatted_name = $customer_name . "-" . $document_number;
    $customer_email = (isset($this->params['billingData']['email'])) ? $this->params['billingData']['email'] :
      $this->params['email'];
    $customer_email = (empty($customer_email)) ? $email_default :
      $customer_email;
    $this->params['userAgent'] = isset($this->params['userAgent']) ?  $this->params['userAgent'] : '';
    $device_id = (isset($this->params['deviceId'])) ? $this->params['deviceId'] :
      $this->utilsPayment->getDeviceId($this->params['uuid'], $this->params['userAgent']);
    if (isset($this->params['numberCard'])) {
      if (!$config_fields->newCardForm['address']['show']) {
        $this->params['street'] = $config_convergente->address['address_default_value'];
      }
      if ($config_convergente->address['send_default_value_address']) {
        $this->params['street'] = $config_convergente->address['address_default_value'];
      }
      $tokenized_card_body = [
        'accountNumber' => $id,
        'accountType' => isset($config_invoice["setting_app_payment"]["typePay"]) ?
          $config_invoice["setting_app_payment"]["typePay"] : $config_app->setting_app_payment['typePay'],
        'deviceId' => $device_id,
        'applicationName' => isset($config_app->setting_app_payment["applicationName"]) ?
          $config_app->setting_app_payment["applicationName"] : $config_invoice["setting_app_payment"]["applicationName"],
      ];
      if ($config_fields->newCardForm['identificationType']['show']) {
        $fietokenized_card_body_id_body['documentType'] = isset($this->params['documentType']) ? $this->params['documentType'] : '';
      }
      if ($config_fields->newCardForm['identificationNumber']['show']) {
        $tokenized_card_body['documentNumber'] = $this->params['documentNumber'];
      }
      $number_card = isset($this->params['numberCard']) ? intval(preg_replace('/[^0-9]+/', '', $this->params['numberCard']), 10) : NULL;
      $tokenized_card_body['creditCardDetails'] = [
        'expirationYear' => trim($this->params['expirationYear']),
        'cvv' => trim($this->params['cvv']),
        'cardType' => $this->params['cardType'],
        'expirationMonth' => trim($this->params['expirationMonth']),
        'accountNumber' => trim($number_card),
      ];
      $user_name = $this->utilsPayment->getNameAndLastname($customer_name);
      $tokenized_card_body['billToAddress'] = [
        'firstName' => trim($user_name['firstName']),
        'lastName' => trim($user_name['lastName']),
        'country' => trim($config_convergente->address["payment_country"]),
        'city' => trim($config_convergente->address["payment_city"]),
        'street' => $this->params['street'],
        'postalCode' => trim($config_convergente->address["payment_postal_code"]),
        'state' => trim($config_convergente->address["payment_state"]),
        'email' => $customer_email,
      ];

      $response_tokenized_card = $this->awsManager->callAwsEndpoint('oneapp_convergent_payment_gateway_v2_0_addcards_endpoint', 'payment', $tokenized_card_body, $this->params, [], []);
      $this->params['tokenizedCardId'] = $response_tokenized_card->body->id;
    }
    if (!$config_fields->newCardForm['phone']['show']) {
      $this->params['phoneNumber'] = $this->utilsPayment->cutOraddPhone($id);
    }
    if (is_null($this->params['accountNumber'])) {
      throw new \Exception('Error accountNumber', Response::HTTP_BAD_REQUEST);
    }
    return [
      "billingSystemName"=> $config_app->setting_app_payment["billingSystemName"],
      "paymentTokenId" => $this->params["tokenizedCardId"],
      "accountNumber"=> $config_app->setting_app_payment["typePay"] == "subscribers" ? $this->params['phoneNumber'] : $this->params['accountNumber'],
      "accountType"=> $config_app->setting_app_payment["typePay"],
      "productType" => $config_app->setting_app_payment["ProductType"],
      "trace" => [
        "deviceId"=> $device_id,
        "applicationName"=> $config_app->setting_app_payment["applicationName"],
        "paymentChannel"=> $config_app->setting_app_payment["paymentChannel"],
        "phoneNumber"=> $this->params['phoneNumber'],
        "customerIpAddress"=> $this->getUserIP(),
        "customerName"=> $formatted_name,
        "email"=> $customer_email,
      ],
      "subscription" => $this->params['subscription'],
    ];
  }

  /**
   * Get DocumentNumber
   */
  public function getDocumentNumber($config_app) {
    if (isset($this->params['billingData']['nit'])) {
      $this->params['billingData']['nit'] = trim($this->params['billingData']['nit']);
      if (strlen($this->params['billingData']['nit']) > 0) {
        return strtoupper($this->params['billingData']['nit']);
      }
    }
    if ((!isset($this->params['billingData']['nit']) || empty($this->params['billingData']['nit']))) {
      $nit = trim($config_app['billing_form']['nit']['default']);
      if (strlen($nit) == 0) {
        return "0";
      } else {
        return strtoupper($nit);
      }
    }
   }

    /**
   * Get the payment enrollments by params.
   */
  public function getEnrollmentsByParams($id) {

    $config = $this->tokenAuthorization->getApplicationSettingsAutoPackets("configuration_app");
    $params['billingSystem'] = $config["setting_app_payment"]["billingSystemName"];
    $params['apiHost'] = $config["setting_app_payment"]["api_path"];
    
    if (isset($id)) {
      $params['id'] = $id;
    }
    else {
      $error = new ErrorBase();
      $error->getError()->set('message', 'The account Id does not exist in the current request.');
      throw new UnauthorizedHttpException($error);
    }
    $aws_service = $config["setting_app_payment"]["aws_service"] ?? 'payment';
    return $this->awsManager->callAwsEndpoint('oneapp_convergent_payment_gateway_v2_0_enrollments_endpoint', $aws_service, [], $params, [], []);
  }

  /**
   * Delete the payment enrollment by ID.
   */
  public function deleteEnrollmentById($id, $enrollment_id) {
    $config = $this->tokenAuthorization->getApplicationSettingsAutoPackets("configuration_app");
    $params['apiHost'] = $config["setting_app_payment"]["api_path"];
    $params['bsname'] = $config["setting_app_payment"]["billingSystemName"];
    $params['accountnumber'] = $id;

    // Get enrollment email.
    $response_enrollments = $this->getEnrollmentsByParams($id);
    if (isset($response_enrollments->body)
      && $response_enrollments->body->id) {
      $this->params['email'] = $response_enrollments->body->trace->email;
    }
    $this->params['email'] = (empty($this->tokenAuthorization->getEmail()) && isset($this->params['email'])) ?
      $this->params['email'] : $this->tokenAuthorization->getEmail();

    $aws_service = $config["setting_app_payment"]["aws_service"] ?? 'payment';
    $delete_enrollment = $this->awsManager->callAwsEndpoint('oneapp_convergent_payment_gateway_v2_0_delete_enrollment_by_params_endpoint', $aws_service, [], $params, [], []);

    $mail_service = \Drupal::service('oneapp_convergent_payment_gateway.v2_0.email_callbacks_service');
    $module_config_mails = $this->tokenAuthorization->getApplicationSettingsAutoPackets('configuration_mail_recurring');
    $tokens = [
      'username' => $this->tokenAuthorization->getGivenNameUser() . ' ' . $this->tokenAuthorization->getFirstNameUser(),
      'mail_to_send' => $this->params['email'],
      'accountId' => $id,
    ];
    if ($module_config_mails['cc_mail'] !== '') {
      $tokens['cc_mail'] = $module_config_mails['cc_mail'];
    }
    $config_mail = [
      'subject' => $module_config_mails['delete_payment']['subject'],
      'body' => $module_config_mails['delete_payment']['body']['value'],
    ];
    if (isset($module_config_mails["send"]) && $module_config_mails["send"]) {
      $mail_service->apiPaymentSendMail($tokens, $config_mail);
    }

return (array) $delete_enrollment;
}

}
