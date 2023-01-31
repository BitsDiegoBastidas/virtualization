<?php

namespace Drupal\oneapp_mobile_balance_management_bo\Services;

use Drupal\oneapp_mobile_balance_management\Services\BalanceManagementServices;

/**
 * Class BalanceManagementServicesBo.
 */
class BalanceManagementServicesBo extends BalanceManagementServices {

  /**
   * Return bool if msisdn is valid.
   */
  public function isValidMsisdn($from_msisdn, $to_msisdn) {
    if ($from_msisdn != $to_msisdn) {
      try {
        $manager = \Drupal::service('oneapp_endpoint.manager');
        $accounts = $manager
          ->load('oneapp_master_accounts_record_endpoint')
          ->setHeaders([])
          ->setQuery([])
          ->setParams(['msisdn' => $to_msisdn])
          ->sendRequest();

        $active = (isset($accounts->customerAccountList[0]->accountList[0]->subscriptionList[0]->msisdnList[0]->lifecycle->isActive) ? $accounts->customerAccountList[0]->accountList[0]->subscriptionList[0]->msisdnList[0]->lifecycle->isActive : isset($accounts->customerAccountList[0]->accountList[0]->lifecycle->isActive)) ? $accounts->customerAccountList[0]->accountList[0]->lifecycle->isActive : FALSE;
        $status = (isset($accounts->customerAccountList[0]->accountList[0]->subscriptionList[0]->msisdnList[0]->lifecycle->status) ? $accounts->customerAccountList[0]->accountList[0]->subscriptionList[0]->msisdnList[0]->lifecycle->status : isset($accounts->customerAccountList[0]->accountList[0]->lifecycle->status)) ? $accounts->customerAccountList[0]->accountList[0]->lifecycle->status : '';
        if ($active) {
          if (strtolower($status) == "active") {
            return TRUE;
          }
        }
        return FALSE;
      }
      catch (\Exception $e) {
        return FALSE;
      }
    }
    return FALSE;
  }

  /**
   * Get body for sendSecureTransfer Method.
   */
  public function getSecureTransferBody($msisdn, $fields) {
    $fecha = time();
    $transaction_id = $msisdn . $fecha;
    $body = [
      "verificationCode" => $fields['verificationCode'],
      "transactionID" => $transaction_id,
      "country" => "BOL",
      "targetMsisdn" => $fields['targetMsisdn'],
      "amount" => $fields['amount'],
      "amountFee" => $this->transferBalanceConfig["general"]["fee"],
      "application" => "MCT",
      "additionalParameter" => [
        "parameterName" => "",
        "parameterValue" => "",
      ],
    ];
    return $body;
  }

}
