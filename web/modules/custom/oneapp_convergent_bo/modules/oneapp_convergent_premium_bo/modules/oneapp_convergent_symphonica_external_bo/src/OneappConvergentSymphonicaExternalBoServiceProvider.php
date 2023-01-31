<?php

namespace Drupal\oneapp_convergent_symphonica_external_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of oneapp_convergent_symphonica_external_bo.
 *
 * @package Drupal\oneapp_convergent_symphonica_external_bo
 */
class OneappConvergentSymphonicaExternalBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $external_v2 = $container->getDefinition('oneapp_convergent_symphonica_external_v2.service');
    $external_v2->setClass('Drupal\oneapp_convergent_symphonica_external_bo\Services\SymphonicaExternalServiceV2Bo');
  }

}
