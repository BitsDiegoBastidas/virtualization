<?php

namespace Drupal\oneapp_mobile_billing_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of oneapp_mobile_billing_bo.
 *
 * @package Drupal\oneapp_mobile_billing_bo
 */
class OneappMobileBillingBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $billing = $container->getDefinition('oneapp_mobile_billing.billing_service');
    $billing->setClass('Drupal\oneapp_mobile_billing_bo\Services\BillingServiceBo');

    $balance = $container->getDefinition('oneapp_mobile_billing.v2_0.balance_rest_logic');
    $balance->setClass('Drupal\oneapp_mobile_billing_bo\Services\v2_0\BalanceRestLogicBo');

    $internet_transparency = $container->getDefinition('oneapp_mobile_billing.v2_0.internet_transparency_rest_logic');
    $internet_transparency->setClass('Drupal\oneapp_mobile_billing_bo\Services\v2_0\InternetTransparencyRestLogicBo');

    $invoices = $container->getDefinition('oneapp_mobile_billing.v2_0.invoices_rest_logic');
    $invoices->setClass('Drupal\oneapp_mobile_billing_bo\Services\v2_0\InvoicesRestLogicBo');
  }
}
