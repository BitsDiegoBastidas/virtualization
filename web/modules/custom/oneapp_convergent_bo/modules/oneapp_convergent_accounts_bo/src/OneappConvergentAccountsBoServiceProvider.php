<?php

namespace Drupal\oneapp_convergent_accounts_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of OneappConvergentAccountsBoServiceProvider.
 *
 * @package Drupal\oneapp_convergent_accounts_bo
 */
class OneappConvergentAccountsBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $invoices = $container->getDefinition('oneapp_convergent_accounts.v2_0.accounts');
    $invoices->setClass('Drupal\oneapp_convergent_accounts_bo\Services\v2_0\AccountsServiceBo');
  }

}
