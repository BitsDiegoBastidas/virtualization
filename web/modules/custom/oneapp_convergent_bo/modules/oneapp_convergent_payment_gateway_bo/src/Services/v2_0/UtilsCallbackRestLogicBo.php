<?php

namespace Drupal\oneapp_convergent_payment_gateway_bo\Services\v2_0;

use Symfony\Component\HttpFoundation\Response;
use Drupal\oneapp_convergent_payment_gateway\Services\v2_0\UtilsCallbackRestLogic;

/**
 * Class PaymentGatewayPacketsRestLogicBo.
 */
class UtilsCallbackRestLogicBo extends UtilsCallbackRestLogic {

  /**
   * Execute fulfillment processes .
   */
  public function executeFulfillmentProcessesPayment($fields) {
    $this->getReversalFulfillment();
    $fields['fulfillmentSucceeded'] = $this->params['fulfillmentSucceded'] ? 1 : 0;

    $complete = ($this->params['fulfillmentSucceded']) ? "COMPLETE" : "NON_COMPLETE";
    $this->changeStatusOrder($complete, $fields);

    if ($this->params['fulfillmentSucceded']) {
      \Drupal::service('module_handler')->invokeAll('succesfull_payment_' . $this->dataTransaction->productType, [$this->params]);
    }
    if (!empty($this->params["multipleAccountsDetail"])) {
      $this->evaluateMultiplePayment();
    }
    else {
      $this->evaluateOnePayment();
    }
    $status_mail = ($this->params['fulfillmentSucceded']) ? 'success' : 'fail';
    $this->sendMail($status_mail);
    return [
      "code" => Response::HTTP_NO_CONTENT,
      "message" => "Fulfilment" . ($this->params['fulfillmentSucceded'] ? "" : " not") . " succeded.",
    ];
  }

  /**
   * Procesa la respuesta de fullfitment para pagos multiples.
   */
  public function evaluateMultiplePayment() {
    $module_config = \Drupal::config("oneapp.payment_gateway.{$this->dataTransaction->accountType}_{$this->productType}.config")->get();
    $balance = NULL;
    foreach ($this->params["multipleAccountsDetail"] as $payment) {
      if ($payment["fulfillmentSucceeded"] === FALSE) {
        if ($this->checkBalance($balance, $payment)) {
          if (isset($this->params["paymentProcessorId"]) && $this->params["paymentProcessorId"] == "QRM") {
            $payment_method = 'Transferencia QR';
            $comment_zendesk = [
              "Transaccion Id." => $this->params["paymentGatewayTransactionId"],
              "order ID" => $this->params["orderId"],
              "Valor a Pagar." => $this->params["paymentAmount"],
              "Contrato / codigo de usuario" => $this->dataTransaction->accountNumber,
              "Línea" => $this->params["phoneNumber"],
              "Periodo" => $payment["productReference"] ,
              "Fecha de Pago." => substr($this->params["registrationDate"], 0, 10),
              "Metodo de Pago." => $payment_method,
              "Nombre." => $this->params["customerName"],
              "Correo electronico." => $this->params["email"],
              "Tipo de cliente (mobile o home)" => $this->dataTransaction->accountType == "mobile" ? "Mobile" : "Home",
            ];
            $this->ticketZendesk($module_config, $comment_zendesk);
          }
          else {
            $payment_method = 'Tarjeta de credito';
            $comment_zendesk = [
              "Transaccion Id." => $this->params["paymentGatewayTransactionId"],
              "order ID" => $this->params["orderId"],
              "Valor a Pagar." => $payment["paymentAmount"],
              "Contrato / codigo de usuario" => $this->dataTransaction->accountNumber,
              "Línea" => $this->params["phoneNumber"],
              "Periodo" => $payment["productReference"],
              "Fecha de Pago." => substr($this->params["registrationDate"], 0, 10),
              "Metodo de Pago." => $payment_method,
              "Nombre." => $this->params["customerName"],
              "Tarjeta." => $this->params["paymentInstrument"]["maskedAccountId"],
              "Vencimiento." => $this->params["paymentInstrument"]["expirationDate"],
              "Correo electronico." => $this->params["email"],
              "Tipo de cliente (mobile o home)" => $this->dataTransaction->accountType == "mobile" ? "Mobile" : "Home",
            ];
            $this->ticketZendesk($module_config, $comment_zendesk);
          }
        }
      }
    }
  }

  /**
   * Procesa la respuesta de fullfitment para pagos simples.
   */
  public function evaluateOnePayment() {

    $module_config = \Drupal::config("oneapp.payment_gateway.{$this->dataTransaction->accountType}_{$this->productType}.config")->get();

    $format_date = $this->languageDateFormat();

    if ($this->params["fulfillmentSucceded"] === FALSE || $this->params["fulfillmentSucceeded"] === FALSE ) {
      if (isset($this->params["paymentProcessorId"]) && $this->params["paymentProcessorId"] == "QRM") {
        $payment_method = 'Transferencia QR';
        $comment_zendesk = [
          "Transaccion Id." => $this->params["paymentGatewayTransactionId"],
          "order ID" => $this->params["orderId"],
          "Valor a Pagar." => $this->params["paymentAmount"],
          "Contrato / codigo de usuario" => $this->dataTransaction->accountNumber,
          "Línea" => $this->params["phoneNumber"],
          "Periodo" => $format_date,
          "Fecha de Pago." => substr($this->params["registrationDate"], 0, 10),
          "Metodo de Pago." => $payment_method,
          "Nombre." => $this->params["customerName"],
          "Correo electronico." => $this->params["email"],
          "Tipo de cliente (mobile o home)" => $this->dataTransaction->accountType == "mobile" ? "Mobile" : "Home",
        ];
        $this->ticketZendesk($module_config, $comment_zendesk);
      }
      else {
        $payment_method = 'Tarjeta de credito';
        $comment_zendesk = [
          "Transaccion Id." => $this->params["paymentGatewayTransactionId"],
          "order ID" => $this->params["orderId"],
          "Valor a Pagar." => $this->params["paymentAmount"],
          "Contrato / codigo de usuario" => $this->dataTransaction->accountNumber,
          "Línea" => $this->params["phoneNumber"],
          "Periodo" => $format_date,
          "Fecha de Pago." => substr($this->params["registrationDate"], 0, 10),
          "Metodo de Pago." => $payment_method,
          "Nombre." => $this->params["customerName"],
          "Tarjeta." => $this->params["paymentInstrument"]["maskedAccountId"],
          "Vencimiento." => $this->params["paymentInstrument"]["expirationDate"],
          "Correo electronico." => $this->params["email"],
          "Tipo de cliente (mobile o home)" => $this->dataTransaction->accountType == "mobile" ? "Mobile" : "Home",
        ];
        $this->ticketZendesk($module_config, $comment_zendesk);
      }
    }
  }

  /**
   * Creates the Body of ticket for realize the shipment.
   */
  public function ticketZendesk($module_config, $comment_zendesk) {
    $custom_fields = [];
    $count_custom_fields = $module_config["zendesk"]["custom_fields"];
    for ($i = 1; $i <= $count_custom_fields; ++$i) {
      $account_type = ($this->dataTransaction->accountType == "mobile") ? 'mobile' : 'home';
      if (!empty($module_config["zendesk"]['fields'][$account_type][$i]['id'])) {
        $item = [
          'id' => $this->getValueFromTransactionDataZendesk($module_config["zendesk"]['fields'][$account_type][$i]['id'], $this->params, $this->dataTransaction),
          'value' => $this->getValueFromTransactionDataZendesk($module_config["zendesk"]['fields'][$account_type][$i]['value'], $this->params, $this->dataTransaction),
        ];
        $custom_fields[] = $item;
      }
    }
    if (isset($module_config["zendesk"])) {
      $params_zendesk = [
        "name" => $this->params["customerName"],
        "email" => $this->params["email"],
        "subject" => $this->getValueFromTransactionDataZendesk($module_config["zendesk"]["subject"], $this->params, $this->dataTransaction),
        "body" => $this->arrayToCommentForZendesk($comment_zendesk),
        "tags" => (strpos($module_config["zendesk"]["tags"], ',') !== FALSE) ? explode(",", $module_config["zendesk"]["tags"]) : $module_config["zendesk"]["tags"],
        "brand_id" => $module_config["zendesk"]["brand_id"],
        "ticket_form_id" => $module_config["zendesk"]["ticket_form_id"],
        "fields" => $custom_fields,
      ];
      $service = \Drupal::service('oneapp_zendesk.services');
      $code = 200;
      try {
        $ticket_response = $service->createZendeskTicket($params_zendesk);
        $status_zendesk = 'success';
      }
      catch (\Exception $e) {
        $code = $e->getCode();
        $status_zendesk = 'failed';
        $ticket_response = $e->getMessage();
      }
      $fields_log = [
        'purchaseOrderId' => $this->dataTransaction->id,
        'message' => "Zendesk ticket " . $status_zendesk,
        'codeStatus' => $code,
        'operation' => 'ZENDESK_TICKET',
        'description' => "Zendesk parameters: " . json_encode($params_zendesk, JSON_PRETTY_PRINT),
        'type' => $this->productType,
      ];
      $this->transactions->addLog($fields_log);
    }
    else {
      \Drupal::logger('payment_zendesk')->debug("No existe configuracion de zendesk");
    }
  }

  /**
   * Create a format depending on the language that is arriving.
   */
  public function languageDateFormat() {

    $additionalData = unserialize($this->dataTransaction->additionalData);
    $period = explode(", ", $additionalData["period"]);

    foreach ($period as $period_string) {
        $date_lenguage = substr($period_string, 0, -5);
        if ($date_lenguage == "January" || $date_lenguage == "Enero"){
          $date = str_replace("Enero", "January", $period_string, $counter);
        }
        elseif ($date_lenguage == "February" || $date_lenguage == "Febrero"){
          $date = str_replace("Febrero", "February", $period_string, $counter);
        }
        elseif ($date_lenguage == "March" || $date_lenguage == "Marzo"){
          $date = str_replace("Marzo", "March", $period_string, $counter);
        }
        elseif ($date_lenguage == "April" || $date_lenguage == "Abril"){
          $date = str_replace("Abril", "April", $period_string, $counter);
        }
        elseif ($date_lenguage == "May" || $date_lenguage == "Mayo"){
          $date = str_replace("Mayo", "May", $period_string, $counter);
        }
        elseif ($date_lenguage == "June" || $date_lenguage == "Junio"){
          $date = str_replace("Junio", "June", $period_string, $counter);
        }
        elseif ($date_lenguage == "July" || $date_lenguage == "Julio"){
          $date = str_replace("Julio", "July", $period_string, $counter);
        }
        elseif ($date_lenguage == "August" || $date_lenguage == "Agosto"){
          $date = str_replace("Agosto", "August", $period_string, $counter);
        }
        elseif ($date_lenguage == "September" || $date_lenguage == "Septiembre"){
          $date = str_replace("Septiembre", "September", $period_string, $counter);
        }
        elseif ($date_lenguage == "October" || $date_lenguage == "Octubre"){
          $date = str_replace("Octubre", "October", $period_string, $counter);
        }
        elseif ($date_lenguage == "November" || $date_lenguage == "Noviembre"){
          $date = str_replace("Noviembre", "November", $period_string, $counter);
        }
        elseif ($date_lenguage == "December" || $date_lenguage == "Diciembre"){
          $date = str_replace("Diciembre", "December", $period_string, $counter);
        }
      $date_english[] = $date;
    }

    foreach ($date_english as $date) {
      $date_formate = date("Ym", strtotime($date));
      $period_format_array[] = $date_formate;
    }

    $formatted_period_string = implode(", ", $period_format_array);

    if (isset($this->params["productReference"]) && $this->params["productReference"] != 0 && $this->params["productReference"] != null) {
      $period = $this->params["productReference"];
    }
    else if (isset($formatted_period_string)) {
      $period = $formatted_period_string;
    }

    return $period;
  }

  /**
   * Get value for subject zendesk.
   */
  public function getValueFromTransactionDataZendesk($field_value, $params, $data_transaction) {
    $value = "";
    if (strpos($field_value, '{') !== FALSE) {
      $regex = '/{\K[^}]*(?=})/m';
      preg_match_all($regex, $field_value, $matches);
      $array_key_params = $matches[0][0];
      if (isset($params[$array_key_params])) {
        $value = $params[$array_key_params];
        $value = str_replace('{' . $array_key_params . '}', $params[$array_key_params], $field_value);
      }
      if (isset($data_transaction->$array_key_params)) {
        $value = str_replace('{' . $array_key_params . '}', $data_transaction->$array_key_params, $field_value);
      }
    }
    else {
      $value = $field_value;
    }
    return $value;
  }

  /**
   * Transforma un arreglo en un texto con saltos de linea por cada posicion.
   */
  public function arrayToCommentForZendesk($comment_array) {
    $comment_string = "";
    foreach ($comment_array as $key => $comment) {
      $comment_string .= $key . ":" . $comment . "\n ";
    }
    return $comment_string;
  }

  /**
   * Revisión de deuda.
   */
  public function checkBalance($balance, $payment) {
    return TRUE; // TODO Validar si es necesario consultar el balance.
    if (isset($balance['pendingInvoices']) && !empty($balance['pendingInvoices'])) {
      foreach ($balance['pendingInvoices'] as $pending_invoice) {
        if (($pending_invoice->period == $payment['productReference']) && ($pending_invoice->dueAmount != 0)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
