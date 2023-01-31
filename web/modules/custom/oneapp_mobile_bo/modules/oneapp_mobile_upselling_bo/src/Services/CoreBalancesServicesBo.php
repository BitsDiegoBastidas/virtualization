<?php

namespace Drupal\oneapp_mobile_upselling_bo\Services;

use Drupal\oneapp_mobile_upselling\Services\CoreBalancesServices;

/**
 * Class CoreBalancesServicesBo.
 */
class CoreBalancesServicesBo extends CoreBalancesServices {

  /**
   * getRemainingAmount.
   */
  public function getRemainingAmount($balance_response) {
    $available_balance = 0;
    if (!empty($balance_response)) {
      $config_manager = \Drupal::service('adf_block_config.config_block');
      $block_config = $config_manager->getDefaultConfigBlock('oneapp_mobile_upselling_v2_0_balances_block');
      if (isset($block_config["general"]["label_balances"]["label"]) && !empty($block_config["general"]["label_balances"]["label"])) {
        $wallet = explode('|', $block_config["general"]["label_balances"]["label"]);
        return $this->getAmount($balance_response, $wallet);
      }
      foreach ($balance_response->balances as $balance) {
        if (strtoupper($balance->wallet) == 'CREDITO' || strtoupper($balance->wallet) == 'SALDO TOTAL') {
          if (isset($balance->balanceAmount)) {
            return $balance->balanceAmount;
          }
        }
      }
    }
    return $available_balance;
  }

  /**
   * getAmount.
   */
  public function getAmount($balance, $wallet) {

    $available_balance = 0;
    if (!empty($balance)) {
      $balances = isset($balance->balances) ? $balance->balances : $balance;
      foreach ($balances as $balance) {
        if (in_array($balance->wallet, $wallet)) {
          if (isset($balance->balanceAmount)) {
            $available_balance += $balance->balanceAmount;
          }
        }
      }
    }
    return $available_balance;
  }

}
