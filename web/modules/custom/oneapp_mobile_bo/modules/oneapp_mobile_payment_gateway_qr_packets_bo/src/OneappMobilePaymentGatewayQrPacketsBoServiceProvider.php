<?php

namespace Drupal\oneapp_mobile_payment_gateway_qr_packets_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\oneapp_mobile_payment_gateway_qr_packets_bo\Services\v2_0\QrPacketsRestLogicBo;

class OneappMobilePaymentGatewayQrPacketsBoServiceProvider extends ServiceProviderBase {
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('oneapp_mobile_payment_gateway_qr_packets.rest_logic');
    $definition->setClass(QrPacketsRestLogicBo::class);
  }
}
