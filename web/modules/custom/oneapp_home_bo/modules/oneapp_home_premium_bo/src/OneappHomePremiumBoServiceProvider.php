<?php

namespace Drupal\oneapp_home_premium_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of oneapp_home_services_bo.
 *
 * @package Drupal\oneapp_home_services_bo
 */
class OneappHomePremiumBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $current = $container->getDefinition('oneapp_home_premium.premium_data');
    $current->setClass('Drupal\oneapp_home_premium_bo\Services\PremiumServiceBo');

    $premium_rest_logic = $container->getDefinition('oneapp_home_premium.v2_0.premium_rest_logic');
    $premium_rest_logic->setClass('Drupal\oneapp_home_premium_bo\Services\v2_0\PremiumRestLogicBo');

    $extenal_v2 = $container->getDefinition('oneapp_home_premium.symphonica_external_v2');
    $extenal_v2->setClass('Drupal\oneapp_home_premium_bo\Services\PremiumServiceSymphonicaExternalV2Bo');
  }

}
