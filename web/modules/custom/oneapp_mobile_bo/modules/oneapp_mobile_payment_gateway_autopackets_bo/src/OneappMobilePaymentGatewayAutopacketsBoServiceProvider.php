<?php

namespace Drupal\oneapp_mobile_payment_gateway_autopackets_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of oneapp_mobile_payment_gateway_autopackets_bo.
 *
 * @package Drupal\oneapp_mobile_payment_gateway_autopackets_bo
 */
class OneappMobilePaymentGatewayAutopacketsBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $enrollment_service = $container->getDefinition('oneapp_mobile_payment_gateway_autopackets.enrollments_service');
    $enrollment_service->setClass('Drupal\oneapp_mobile_payment_gateway_autopackets_bo\Services\EnrollmentsServiceBo');
  }

}
