<?php

namespace Drupal\oneapp_mobile_balance_management_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of oneapp_mobile_balance_management_bo.
 *
 * @package Drupal\oneapp_mobile_balance_management_bo
 */
class OneappMobileBalanceManagementBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {

    $balanceManagementServices = $container->getDefinition('oneapp_mobile_balance_management.balance_management_services');
    $balanceManagementServices->setClass('Drupal\oneapp_mobile_balance_management_bo\Services\BalanceManagementServicesBo');

    $secureTransfer = $container->getDefinition('oneapp_mobile_balance_management.v2_0.secure_transfer_rest_logic');
    $secureTransfer->setClass('Drupal\oneapp_mobile_balance_management_bo\Services\v2_0\SecureTransferRestLogicBo');
  }

}
