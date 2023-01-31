<?php

namespace Drupal\oneapp_mobile_loan_bo\Services\v2_0;

use Drupal\oneapp\Exception\HttpException;
use Drupal\oneapp_mobile_loan\Services\v2_0\LoanDataBalanceRestLogic;

/**
 * Class LoanDataBalanceRestLogicBo.
 */
class LoanDataBalanceRestLogicBo extends LoanDataBalanceRestLogic {

  /**
   * Get loan balance.
   *
   * @param string $msisdn
   *   Msisdn of the user.
   *
   * @return array
   *   Return associative array.
   */
  public function get($msisdn) {

    $config = $this->configBlock;
    $scoring = $this->getLendingScoring($msisdn);
    $currencyId = $this->utils->getCurrencyCode(FALSE);

    $creditAvailable = isset($scoring->creditAvailable) && $scoring->creditAvailable != NULL ? $scoring->creditAvailable : 0;
    $formattedCreditAvailable = $this->utils->formatCurrency($creditAvailable, TRUE, TRUE);

    $totalDebt = isset($scoring->totalDebt) && $scoring->totalDebt != NULL ? $scoring->totalDebt : 0;
    $formattedTotalDebt = $this->utils->formatCurrency($totalDebt, TRUE, TRUE);

    $data = [
      'creditAvailable' => [
        'value' => [
          [
            'amount' => (double) $creditAvailable,
            'currencyId' => $currencyId,
          ],
        ],
        'formattedValue' => $formattedCreditAvailable,
        'label' => $config['fields']['creditAvailable']['label'],
        'show' => (bool) $config['fields']['creditAvailable']['show'],
      ],
      'totalDebt' => [
        'value' => [
          [
            'amount' => (double) $totalDebt,
            'currencyId' => $currencyId,
          ],
        ],
        'formattedValue' => $formattedTotalDebt,
        'label' => $config['fields']['totalDebt']['label'],
        'show' => (bool) $config['fields']['totalDebt']['show'],
      ],
      'scoring' => [
        'value' => FALSE,
        'formattedValue' => FALSE,
        'label' => $config['fields']['scoring']['label'],
        'unit' => '',
        'show' => (bool) $config['fields']['scoring']['show'],
      ],
      'overdraft' => [
        'value' => FALSE,
        'formattedValue' => FALSE,
        'label' => $config['fields']['overdraft']['label'],
        'unit' => '',
        'show' => (bool) $config['fields']['overdraft']['show'],
      ],
    ];
    $show = FALSE;
    if ((bool) $config['actions']['purchase']['show']) {
      $show = ((double) $totalDebt) > 0 ? TRUE : FALSE;
    }
    $config = [
      'actions' => [
        'purchase' => [
          'label' => $config['actions']['purchase']['label'],
          'url' => $config['actions']['purchase']['url'],
          'type' => $config['actions']['purchase']['type'],
          'show' => $show,
        ],
        'info' => [
          'label' => $config['actions']['info']['label'],
          'url' => $config['actions']['info']['url'],
          'type' => $config['actions']['info']['type'],
          'show' => (bool) $config['actions']['info']['show'],
        ],
      ],
    ];

    return [
      'data' => $data,
      'config' => $config,
    ];

  }

  /**
   * Get Lending Scoring from api gee.
   *
   * @param string $msisdn
   *   Msisdn of the user.
   *
   * @return mixed
   *   Returm Std class.
   */
  protected function getLendingScoring($msisdn) {
    try {
      return $this->manager
        ->load('oneapp_mobile_lending_v2_0_scoring_endpoint')
        ->setHeaders([])
        ->setQuery([])
        ->setParams(['msisdn' => $msisdn])
        ->sendRequest();

    }
    catch (HttpException $e) {
      //TODO
    }
  }

}
