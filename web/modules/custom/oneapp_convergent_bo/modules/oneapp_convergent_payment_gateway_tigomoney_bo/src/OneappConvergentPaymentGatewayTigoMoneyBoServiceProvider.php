<?php

namespace Drupal\oneapp_convergent_payment_gateway_tigomoney_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class OneappConvergentPaymentGatewayTigoMoneyBoServiceProvider.
 *
 * @package Drupal\oneapp_convergent_payment_gateway_tigomoney_bo
 */
class OneappConvergentPaymentGatewayTigoMoneyBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides cron class to use our own service.
    $definition = $container->getDefinition('oneapp_convergent_payment_gateway_tigomoney.v2_0.transactions_payment_rest_logic');
    $definition->setClass('Drupal\oneapp_convergent_payment_gateway_tigomoney_bo\Services\v2_0\TransactionsPaymentTigoMoneyRestLogicBo');
  }

}
