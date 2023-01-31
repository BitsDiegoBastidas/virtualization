<?php

namespace Drupal\oneapp_convergent_payment_gateway_qr_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
/**
 * Modifies the classes of oneapp_convergent_payment_gateway_qr_bo.
 *
 * @package Drupal\oneapp_convergent_payment_gateway_qr_bo
 */
class OneappConvergentPaymentGatewayQrBoServiceProvider extends ServiceProviderBase {
  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $qr_restlogic_service = $container->getDefinition('oneapp_convergent_payment_gateway_qr.v2_0.qr_rest_logic');
    $qr_restlogic_service->setClass('Drupal\oneapp_convergent_payment_gateway_qr_bo\Services\v2_0\QrRestLogicBo');
  }
}
