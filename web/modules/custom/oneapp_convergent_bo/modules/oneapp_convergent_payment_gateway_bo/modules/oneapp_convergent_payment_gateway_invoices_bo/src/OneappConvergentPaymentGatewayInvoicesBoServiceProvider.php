<?php

namespace Drupal\oneapp_convergent_payment_gateway_invoices_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
/**
 * Modifies the classes of oneapp_convergent_payment_gateway_invoices_bo.
 *
 * @package Drupal\oneapp_convergent_payment_gateway_invoices_bo
 */
class OneappConvergentPaymentGatewayInvoicesBoServiceProvider extends ServiceProviderBase {
  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $pg_restlogic_service = $container->getDefinition('oneapp_convergent_payment_gateway.v2_0.payment_gateway_rest_logic');
    $pg_restlogic_service->setClass('Drupal\oneapp_convergent_payment_gateway_invoices_bo\Services\v2_0\PaymentGatewayRestLogicBo');
  }
}
