<?php

namespace Drupal\oneapp_mobile_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of oneapp_mobile_bo.
 *
 * @package Drupal\oneapp_mobile_bo
 */
class OneappMobileBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {

    $utilsService = $container->getDefinition('oneapp.mobile.utils');
    $utilsService->setClass('Drupal\oneapp_mobile_bo\Services\UtilsServiceBo');

  }

}
