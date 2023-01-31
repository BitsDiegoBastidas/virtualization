<?php

namespace Drupal\oneapp_convergent_upgrade_plan_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of oneapp_convergent_upgrade_plan_bo.
 *
 * @package Drupal\OneappConvergentUpgradePlanBo
 */
class OneappConvergentUpgradePlanBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {

    $definition = $container->getDefinition('oneapp_convergent_upgrade_plan.upgrade_service');
    $definition->setClass('Drupal\oneapp_convergent_upgrade_plan_bo\Services\UpgradeServiceBo');

    $definition = $container->getDefinition('oneapp_convergent_upgrade_plan.utils');
    $definition->setClass('Drupal\oneapp_convergent_upgrade_plan_bo\Services\UtilServiceBo');

    $definition = $container->getDefinition('oneapp_convergent_upgrade_plan.v2_0.plan_card_rest_logic');
    $definition->setClass('Drupal\oneapp_convergent_upgrade_plan_bo\Services\v2_0\UpgradePlanCardRestLogicBo');

    $definition = $container->getDefinition('oneapp_convergent_upgrade_plan.v2_0.recommended_offers_rest_logic');
    $definition->setClass('Drupal\oneapp_convergent_upgrade_plan_bo\Services\v2_0\UpgradeRecommendedOffersRestLogicBo');

    $definition = $container->getDefinition('oneapp_convergent_upgrade_plan.v2_0.plan_send_rest_logic');
    $definition->setClass('Drupal\oneapp_convergent_upgrade_plan_bo\Services\v2_0\UpgradePlanSendRestLogicBo');

  }

}
