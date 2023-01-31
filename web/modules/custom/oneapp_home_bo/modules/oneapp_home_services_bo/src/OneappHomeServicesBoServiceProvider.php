<?php

namespace Drupal\oneapp_home_services_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of oneapp_home_services_bo.
 *
 * @package Drupal\oneapp_home_services_bo
 */
class OneappHomeServicesBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $current = $container->getDefinition('oneapp_home_services.v2_0.services_rest_logic');
    $current->setClass('Drupal\oneapp_home_services_bo\Services\v2_0\ServicesRestLogicBo');
  }

}
