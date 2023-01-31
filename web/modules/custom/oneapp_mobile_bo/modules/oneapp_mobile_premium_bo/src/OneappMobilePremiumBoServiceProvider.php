<?php

namespace Drupal\oneapp_mobile_premium_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of oneapp_mobile_services_bo.
 *
 * @package Drupal\oneapp_mobile_services_bo
 */
class OneappMobilePremiumBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $current = $container->getDefinition('oneapp_mobile_premium.premium_data');
    $current->setClass('Drupal\oneapp_mobile_premium_bo\Services\PremiumServiceBo');

    $sm = $container->getDefinition('oneapp_mobile_premium.supermarket');
    $sm->setClass('Drupal\oneapp_mobile_premium_bo\Services\PremiumServiceSuperMarketBo');

    $premium_rest_logic = $container->getDefinition('oneapp_mobile_premium.v2_0.premium_rest_logic');
    $premium_rest_logic->setClass('Drupal\oneapp_mobile_premium_bo\Services\v2_0\PremiumRestLogicBo');

    $extenal_v2 = $container->getDefinition('oneapp_mobile_premium.symphonica_external_v2');
    $extenal_v2->setClass('Drupal\oneapp_mobile_premium_bo\Services\PremiumServiceSymphonicaExternalV2Bo');

    $tigo = $container->getDefinition('oneapp_mobile_premium.v2_0.premium_tigosport_rest_logic');
    $tigo->setClass('Drupal\oneapp_mobile_premium_bo\Services\v2_0\PremiumTigoSportRestLogicBo');

  }

}
