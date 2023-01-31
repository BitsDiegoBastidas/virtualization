<?php

namespace Drupal\oneapp_mobile_balance_management_bo\Services\v2_0;

use Drupal\oneapp_mobile_balance_management\Services\v2_0\SecureTransferRestLogic;

/**
 * Class BalanceManagementServicesBo.
 */
class SecureTransferRestLogicBo extends SecureTransferRestLogic {

  /**
   * Logic for return data for post method.
   */
  public function getDataforPost($msisdn, $payload) {
    $config = [];
    if ($msisdn != $payload['targetMsisdn']) {
      try {
        if ($this->balanceManagementServices->isValidMinAmount($payload['amount']) && $this->balanceManagementServices->isValidMaxAmount($payload['amount'])) {
          $config['transactionDetails'] = $this->config['transactionDetails'];
          $configMobile = \Drupal::config('oneapp_mobile.config')->getRawData();
          $discountBalance = isset($configMobile["transfer_balance"]["general"]["discountBalance"]) ? $configMobile["transfer_balance"]["general"]["discountBalance"] : FALSE;
          foreach ($config['transactionDetails'] as $key => $field) {
            unset($config['transactionDetails'][$key]['title']);
            unset($config['transactionDetails'][$key]['weight']);
            $config['transactionDetails'][$key]['show'] = (bool) $config['transactionDetails'][$key]['show'];
            switch ($key) {
              case 'productType':
                $config['transactionDetails'][$key]['value'] = "Transferencia";
                $config['transactionDetails'][$key]['formattedValue'] = "Transferencia";
                break;

              case 'originMsisdn':
                $config['transactionDetails'][$key]['value'] = $msisdn;
                $config['transactionDetails'][$key]['formattedValue'] = $msisdn;
                break;

              case 'targetMsisdn':
                $config['transactionDetails'][$key]['value'] = $payload['targetMsisdn'];
                $config['transactionDetails'][$key]['formattedValue'] = $payload['targetMsisdn'];
                break;

              case 'fee':
                $config['transactionDetails'][$key]['value'] = $this->balanceManagementServices->getFee();
                $config['transactionDetails'][$key]['formattedValue'] = $this->utils->formatCurrency($config['transactionDetails'][$key]['value']);
                break;

              case 'totalTransferAmount':
                $config['transactionDetails'][$key]['value'] = $discountBalance ? $payload['amount'] : $payload['amount'] - $this->balanceManagementServices->getFee();
                $config['transactionDetails'][$key]['formattedValue'] = $this->utils->formatCurrency($config['transactionDetails'][$key]['value']);
                break;

              case 'totalTransfer':
                $config['transactionDetails'][$key]['value'] = $discountBalance ? $payload['amount'] + $this->balanceManagementServices->getFee() : $payload['amount'];
                $config['transactionDetails'][$key]['formattedValue'] = $this->utils->formatCurrency($config['transactionDetails'][$key]['value']);
                break;

              case 'paymentMethod':
                $config['transactionDetails'][$key]['value'] = t('Saldo');
                $config['transactionDetails'][$key]['formattedValue'] = t('Saldo');
                break;
            }
          }
          $this->balanceManagementServices->sendSecureTransfer($msisdn, $payload);
          $this->transactionResult = 'success';
        }
        else {
          $this->transactionResult = 'fail';
        }
      }
      catch (\Exception $e) {
        $this->transactionResult = 'fail';
        if ($e->getCode() == '401') {
          throw $e;
        }
        $dataException = $this->getDataException($e);
        $mapping = isset($configMobile["transfer_balance"]["general"]["mapping"]) ? $configMobile["transfer_balance"]["general"]["mapping"] : '';
        $mapping = $this->getMapping($mapping, $dataException);
        $mesaage = isset($mapping["message"]) ? $mapping["message"] : '';
      }
    }
    else {
      $this->transactionResult = 'fail';
    }
    $config['result']['label'] = $this->config['result'][$this->transactionResult]['title'];
    $config['result']['formattedValue'] = isset($mapping["message"]) ? $mapping["message"] : $this->config['result'][$this->transactionResult]['body'];
    $config['result']['value'] = ($this->transactionResult == 'success') ? TRUE : FALSE;
    $config['result']['show'] = TRUE;
    return $config;
  }

  /**
   * Logic for return data for post method.
   */
  public function getDataException($exception) {
    if (method_exists($exception, 'getResponse')) {
      $getResponse = $exception->getResponse();
      $getResponse->getBody()->seek(0);
      $errorContent = isset($getResponse) ? $getResponse->getBody()->getContents() : NULL;
      return isset($errorContent) ? json_decode($errorContent, TRUE) : [];
    }
    else {
      return [];
    }

  }

  /**
   * Logic for return data for post method.
   */
  public function getMapping($mapping, $data) {
    $codes = explode(PHP_EOL, $mapping);
    foreach ($codes as $code) {
      $dataCode = explode("|", $code);
      if ($dataCode[0] == $data["error"]["code"]) {
        return [
          'code' => $data["error"]["code"],
          'message' => str_replace("\r", "", "$dataCode[1]"),
        ];
      }
    }
    return [];
  }

}
