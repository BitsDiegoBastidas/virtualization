<?php

namespace Drupal\oneapp_mobile_upselling_bo\Services\v2_0;

use Drupal\oneapp_mobile_upselling\Services\v2_0\BalancesRestLogic;

/**
 * Class BalancesRestLogicBo.
 */
class BalancesRestLogicBo extends BalancesRestLogic {

  /**
   * {@inheritdoc}
   */
  public function get($msisdn, $purchaseBalance = NULL) {
    $coreBalance = 0;
    $balances = $this->getBalances($msisdn)->balances;
    // If is to buy with balance.
    if ($purchaseBalance) {
      $coreBalance = $this->getPurchaseBalance($balances, $msisdn);
    }
    else {
      $row = [];
      $count = 0;
      $balanceDetails = [];
      $config = $this->configBlock;
      $dateFormatter = \Drupal::service('date.formatter');
      $dateFormatName = 'short';
      if (!empty($config['general']['dateFormat']['label'])) {
        $dateFormatName = $config['general']['dateFormat']['label'];
      }
      $configHeaders = $config['headerList']['fields'];
      $wallet = [];
      if (isset($this->configBlock["general"]["label_balances_main"]["label"]) && !empty($this->configBlock["general"]["label_balances_main"]["label"])) {
        $wallet = explode('|', $this->configBlock["general"]["label_balances_main"]["label"]);
      }
      $core = \Drupal::service('oneapp_mobile_upselling.v2_0.core_balances_services');
      $coreBalance = $core->getAmount($balances, $wallet);

      foreach ($balances as $balance) {

        $includeEmptyWallet = (bool) $this->mobileUtils->getConfig('core_balance', 'include_empty_wallet');
        if ($balance->unit !== "Bs" || (!$includeEmptyWallet && $balance->balanceAmount === 0)) {
          continue;
        }
        else {
          if (isset($config["general"]["label_balances"]["show"]) && $config["general"]["label_balances"]["show"] && !empty($wallet) && !in_array($balance->wallet, $wallet)) {
            continue;
          }
          else {
            foreach ($configHeaders as $field_name => $field) {
              switch ($field_name) {
                case 'name':
                  $row[$field_name]['value'] = $balance->description;
                  $row[$field_name]['formattedValue'] = ucwords(strtolower($balance->description));
                  $row[$field_name]['show'] = (bool) $configHeaders[$field_name]['show'];
                  $row[$field_name]['label'] = $configHeaders[$field_name]['label'];
                  break;

                case 'remainingAmount':
                  $row[$field_name]['value'] = $balance->balanceAmount;
                  $row[$field_name]['formattedValue'] = $this->utils->formatCurrency($balance->balanceAmount, TRUE);
                  $row[$field_name]['show'] = (bool) $configHeaders[$field_name]['show'];
                  $row[$field_name]['label'] = $configHeaders[$field_name]['label'];
                  break;

                case 'endDateTime':
                  $end_date_time = new \DateTime($balance->expirationDate);
                  $date_now = new \DateTime('now');
                  $diff = $end_date_time->diff($date_now);
                  $show = isset($balance->expirationDate) ? (bool) $configHeaders[$field_name]['show'] : FALSE;
                  $row[$field_name]['value'] = $balance->expirationDate;
                  $row[$field_name]['formattedValue'] = $show ? $dateFormatter->format($end_date_time->getTimestamp(), $dateFormatName) : '';
                  $row[$field_name]['isDelinquent'] = FALSE;
                  $row[$field_name]['show'] = $show;
                  $row[$field_name]['label'] = $show ? $configHeaders[$field_name]['label'] : '';
                  break;
              }
            }
            $balanceDetails[$count] = $row;
            $count++;
          }
        }
      }
    }

    // Final response.
    $response = [];
    $availableBalance = [
      'value' => $coreBalance,
      'formattedValue' => $this->utils->formatCurrency($coreBalance, TRUE),
      'show' => TRUE,
    ];

    if ($purchaseBalance) {
      $response['coreBalancePayment'] = $availableBalance;
    }

    if (isset($balanceDetails)) {
      $response = [
        'coreBalance' => $availableBalance,
        'BucketsBalanceList' => $balanceDetails,
      ];
    }
    return $response;
  }

  /**
   * Add core balance depending msisdn type.
   *
   * @param array $balances
   *   Array balance.
   * @param string $msisdn
   *   Msisdn.
   *
   * @return float
   *   Return sum of balances.
   */
  protected function getPurchaseBalance(array $balances, $msisdn) {
    $availableBalance = 0;

    if (!empty($balances)) {
      if (isset($this->configBlock["general"]["label_balances"]["label"]) && !empty($this->configBlock["general"]["label_balances"]["label"])) {
        $core = \Drupal::service('oneapp_mobile_upselling.v2_0.core_balances_services');
        $wallet = explode('|', $this->configBlock["general"]["label_balances"]["label"]);
        return $core->getAmount($balances, $wallet);
      }
      foreach ($balances as $balance) {
        if ($balance->wallet === "Credito" || $balance->wallet === "Saldo Total") {
          return $balance->balanceAmount;
        }
      }
    }

    return $availableBalance;
  }

}
