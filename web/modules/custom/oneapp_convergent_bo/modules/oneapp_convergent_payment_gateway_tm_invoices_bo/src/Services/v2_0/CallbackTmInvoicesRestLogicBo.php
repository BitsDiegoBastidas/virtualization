<?php

namespace Drupal\oneapp_convergent_payment_gateway_tm_invoices_bo\Services\v2_0;

use Drupal\oneapp_convergent_payment_gateway_tm_invoices\Services\v2_0\CallbackTmInvoicesRestLogic;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CallbackTmInvoicesRestLogic.
 */
class CallbackTmInvoicesRestLogicBo extends CallbackTmInvoicesRestLogic {

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
    $emailConfig = \Drupal::config("oneapp.payment_gateway_tigomoney." . $this->businessUnit . "_invoices.config")->get('configuration_mail')['c_mail'];
    if ($emailConfig === 1) {
      $statusMail = ($this->params['fulfillmentSucceded']) ? 'success' : 'fail';
      $this->sendMail($statusMail);
    }
    return [
      "code" => Response::HTTP_NO_CONTENT,
      "message" => "Fulfilment" . ($this->params['fulfillmentSucceded'] ? "" : " not") . " succeded.",
    ];
  }

  /**
   * Procesa la respuesta de fullfitment para pagos multiples.
   */
  public function evaluateMultiplePayment() {
    $moduleConfig = \Drupal::config("oneapp.payment_gateway_tigomoney.{$this->dataTransaction->accountType}_invoices.config")->get();
    if ($moduleConfig["zendesk"]["enableZendesk"]) {
      $balance = NULL;
      foreach ($this->params["multipleAccountsDetail"] as $payment) {
        if ($payment["fulfillmentSucceeded"] === FALSE) {
          /* TODO Validar si es necesario consultar el balance.
          if (empty($balance)) {
          $utilsPayment = \Drupal::service('oneapp_convergent_payment_gateway.v2_0.utils_service');
          $balance = $utilsPayment->getBalance($this->dataTransaction
          ->accountId, '', $this->dataTransaction->accountType);
          }*/
          if ($this->checkBalance($balance, $payment)) {
            $metodoDePago = 'TigoMoney';
            $commentZendeskArreglo = [
              "Transaccion Id." => $this->params["paymentGatewayTransactionId"],
              "order ID" => $this->params["orderId"],
              "Valor a Pagar." => $payment["paymentAmount"],
              "Contrato / codigo de usuario" => $this->dataTransaction->accountNumber,
              "Línea" => $this->params["phoneNumber"],
              "Periodo" => $payment["productReference"],
              "Fecha de Pago." => substr($this->params["registrationDate"], 0, 10),
              "Metodo de Pago." => $metodoDePago,
              "Nombre." => $this->params["customerName"],
              "Correo electronico." => $this->params["email"],
              "Tipo de cliente (mobile o home)" => $this->dataTransaction->accountType == "mobile" ? "Mobile" : "Home",
            ];
            $customFields = [];
            $countCustomFields = $moduleConfig["zendesk"]["custom_fields"];
            for ($i = 1; $i <= $countCustomFields; ++$i) {
              $tipoDeCuenta = ($this->dataTransaction->accountType == "mobile") ? 'mobile' : 'home';
              if (!empty($moduleConfig["zendesk"]['fields'][$tipoDeCuenta][$i]['id'])) {
                $element = [
                  'id' => $this->getValueFromTransactionDataZendesk($moduleConfig["zendesk"]['fields'][$tipoDeCuenta][$i]['id'], $this->params, $this->dataTransaction),
                  'value' => $this->getValueFromTransactionDataZendesk($moduleConfig["zendesk"]['fields'][$tipoDeCuenta][$i]['value'], $this->params, $this->dataTransaction),
                ];
                $customFields[] = $element;
              }
            }
            if (isset($moduleConfig["zendesk"])) {
              $parametrosZendesk = [
                "name" => $this->params["customerName"],
                "email" => $this->params["email"],
                "subject" => $this->getValueFromTransactionDataZendesk($moduleConfig["zendesk"]["subject"], $this->params, $this->dataTransaction),
                "body" => $this->arrayToCommentForZendesk($commentZendeskArreglo),
                "tags" => (strpos($moduleConfig["zendesk"]["tags"], ',') !== FALSE) ? explode(",", $moduleConfig["zendesk"]["tags"]) : $moduleConfig["zendesk"]["tags"],
                "brand_id" => $moduleConfig["zendesk"]["brand_id"],
                "ticket_form_id" => $moduleConfig["zendesk"]["ticket_form_id"],
                'fields' => $customFields,
              ];

              $service = \Drupal::service('oneapp_zendesk.services');
              $code = 200;
              try {
                $ticketResponse = $service->createZendeskTicket($parametrosZendesk);
                $statusZendesk = 'success';
              }
              catch (\Exception $e) {
                $code = $e->getCode();
                $statusZendesk = 'failed';
                $ticketResponse = $e->getMessage();
              }
              $fieldsLog = [
                'purchaseOrderId' => $this->dataTransaction->id,
                'message' => "Zendesk ticket " . $statusZendesk,
                'codeStatus' => $code,
                'operation' => 'ZENDESK_TICKET',
                'description' => "Zendesk parameters: " . json_encode($parametrosZendesk, JSON_PRETTY_PRINT),
                'type' => $this->productType,
              ];
              $this->transactions->addLog($fieldsLog);
            }
            else {
              \Drupal::logger('payment_zendesk')->debug("No existe configuracion de zendesk");
            }
          }
        }
      }
    }
  }

  /**
   * Get value for subject zendesk.
   */
  public function getValueFromTransactionDataZendesk($field_value, $params, $dataTransaction) {
    $value = "";
    if (strpos($field_value, '{') !== FALSE) {
      $regex = '/{\K[^}]*(?=})/m';
      preg_match_all($regex, $field_value, $matches);
      $arrayKeyParams = $matches[0][0];
      if (isset($params[$arrayKeyParams])) {
        $value = $params[$arrayKeyParams];
        $value = str_replace('{' . $arrayKeyParams . '}', $params[$arrayKeyParams], $field_value);
      }
      if (isset($dataTransaction->$arrayKeyParams)) {
        $value = str_replace('{' . $arrayKeyParams . '}', $dataTransaction->$arrayKeyParams, $field_value);
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
  public function arrayToCommentForZendesk($commentArray) {
    $commentString = "";
    foreach ($commentArray as $key => $comment) {
      $commentString .= $key . ":" . $comment . "\n ";
    }
    return $commentString;
  }

  /**
   * Revisión de deuda.
   */
  public function checkBalance($balance, $payment) {
    return TRUE;
    // TODO Validar si es necesario consultar el balance.
    if (isset($balance['pendingInvoices']) && !empty($balance['pendingInvoices'])) {
      foreach ($balance['pendingInvoices'] as $pendingInvoice) {
        if (($pendingInvoice->period == $payment['productReference']) && ($pendingInvoice->dueAmount != 0)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
