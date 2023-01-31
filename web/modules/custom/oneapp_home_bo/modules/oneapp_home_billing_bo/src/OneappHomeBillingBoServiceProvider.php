<?php

namespace Drupal\oneapp_home_billing_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of oneapp_home_billing_bo.
 *
 * @package Drupal\oneapp_home_billing_bo
 */
class OneappHomeBillingBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $billingService = $container->getDefinition('oneapp_home_billing.billing_data');
    $billingService->setClass('Drupal\oneapp_home_billing_bo\Services\BillingServiceBo');

    $balanceRestLogic = $container->getDefinition('oneapp_home_billing.v2_0.balance_rest_logic');
    $balanceRestLogic->setClass('Drupal\oneapp_home_billing_bo\Services\v2_0\BalanceRestLogicBo');
  }

}
