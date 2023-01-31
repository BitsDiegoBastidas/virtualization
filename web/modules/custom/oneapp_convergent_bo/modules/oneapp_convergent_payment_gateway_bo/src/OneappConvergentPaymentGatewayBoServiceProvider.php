<?php

namespace Drupal\oneapp_convergent_payment_gateway_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class OneappConvergentPaymentGatewayBoServiceProvider.
 *
 * @package Drupal\oneapp_convergent_payment_gateway_bo
 */
class OneappConvergentPaymentGatewayBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides cron class to use our own service.
    $definition = $container->getDefinition('oneapp_convergent_payment_gateway.v2_0.invoices_callbacks_service');
    $definition->setClass('Drupal\oneapp_convergent_payment_gateway_bo\Services\v2_0\CallbackInvoicesRestLogicBo');

    $definition = $container->getDefinition('oneapp_convergent_payment_gateway.v2_0.transactions_payment_rest_logic');
    $definition->setClass('Drupal\oneapp_convergent_payment_gateway_bo\Services\v2_0\TransactionsPaymentRestLogicBo');
  }

}
