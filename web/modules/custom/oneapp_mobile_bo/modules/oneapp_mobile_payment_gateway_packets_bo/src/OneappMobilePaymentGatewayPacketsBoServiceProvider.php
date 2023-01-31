<?php

namespace Drupal\oneapp_mobile_payment_gateway_packets_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class PaymentGatewayPacketsBoServiceProvider.
 */
class OneappMobilePaymentGatewayPacketsBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides cron class to use our own service.
    $definition = $container->getDefinition('oneapp_mobile_payment_gateway_packets.v2_0.payment_gateway_packets_rest_logic');
    $definition->setClass('Drupal\oneapp_mobile_payment_gateway_packets_bo\Services\v2_0\PaymentGatewayPacketsRestLogicBo');
    $service_definition = $container->getDefinition('oneapp_mobile_payment_gateway_packets.v2_0.data_service');
    $service_definition->setClass('Drupal\oneapp_mobile_payment_gateway_packets_bo\Services\PaymentGatewayPacketsServiceBo');
  }

}
