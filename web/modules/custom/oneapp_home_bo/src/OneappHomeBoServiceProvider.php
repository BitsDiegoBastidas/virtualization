<?php

namespace Drupal\oneapp_home_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of oneapp_home_bo.
 *
 * @package Drupal\oneapp_home_bo
 */
class OneappHomeBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('oneapp.home.intraway');
    $definition->setClass('Drupal\oneapp_home_bo\Services\v2_0\IntrawayServiceBo');
  }

}
