<?php

namespace Drupal\oneapp_mobile_plans_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of oneapp_mobile_plans_bo.
 *
 * @package Drupal\oneapp_mobile_plans_bo
 */
class OneappMobilePlansBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $current = $container->getDefinition('oneapp_mobile_plans.v2_0.current_rest_logic');
    $current->setClass('Drupal\oneapp_mobile_plans_bo\Services\v2_0\CurrentRestLogicBo');
  }

}
