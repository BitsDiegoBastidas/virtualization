<?php

namespace Drupal\oneapp_mobile_loan_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of oneapp_mobile_loan_bo.
 *
 * @package Drupal\oneapp_mobile_loan_bo
 */
class OneappMobileLoanBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $current = $container->getDefinition('oneapp_mobile_loan.v2_0.loan_data_balance_rest_logic');
    $current->setClass('Drupal\oneapp_mobile_loan_bo\Services\v2_0\LoanDataBalanceRestLogicBo');
  }

}
