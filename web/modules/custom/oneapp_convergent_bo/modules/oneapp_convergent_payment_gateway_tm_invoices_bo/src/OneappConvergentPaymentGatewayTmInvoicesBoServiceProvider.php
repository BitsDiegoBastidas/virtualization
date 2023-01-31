<?php

namespace Drupal\oneapp_convergent_payment_gateway_tm_invoices_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class OneappConvergentPaymentGatewayTmInvoicesBoServiceProvider.
 *
 * @package Drupal\oneapp_convergent_payment_gateway_tm_invoices_bo
 */
class OneappConvergentPaymentGatewayTmInvoicesBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides cron class to use our own service.
    $definition = $container->getDefinition('oneapp_convergent_payment_gateway_tigomoney.v2_0.invoices_callbacks_service');
    $definition->setClass('Drupal\oneapp_convergent_payment_gateway_tm_invoices_bo\Services\v2_0\CallbackTmInvoicesRestLogicBo');
    $definition_pg_async_tm = $container->getDefinition('oneapp_convergent_payment_gateway_tigomoney.v2_0.payment_gateway_async_rest_logic');
    $definition_pg_async_tm->setClass('Drupal\oneapp_convergent_payment_gateway_tm_invoices_bo\Services\v2_0\PaymentGatewayTmInvoicesAsyncRestLogicBo');
    $definition_pg_tm_invoices = $container->getDefinition('oneapp_convergent_payment_gateway_tigomoney.v2_0.payment_gateway_rest_logic');
    $definition_pg_tm_invoices->setClass('Drupal\oneapp_convergent_payment_gateway_tm_invoices_bo\Services\PaymentGatewayTmInvoicesRestLogicBo');
  }

}
