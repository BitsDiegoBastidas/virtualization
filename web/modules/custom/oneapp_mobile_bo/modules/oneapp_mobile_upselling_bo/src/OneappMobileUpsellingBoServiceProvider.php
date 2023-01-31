<?php

namespace Drupal\oneapp_mobile_upselling_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of oneapp_mobile_upselling_bo.
 *
 * @package Drupal\oneapp_mobile_upselling_bo
 */
class OneappMobileUpsellingBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $balanceDefinition = $container->getDefinition('oneapp_mobile_upselling.v2_0.balances_rest_logic');
    $balanceDefinition->setClass('Drupal\oneapp_mobile_upselling_bo\Services\v2_0\BalancesRestLogicBo');

    $rechargeDefinition = $container->getDefinition('oneapp_mobile_upselling.v2_0.recharge_amounts_rest_logic');
    $rechargeDefinition->setClass('Drupal\oneapp_mobile_upselling_bo\Services\v2_0\RechargeAmountRestLogicBo');

    $offerDetailsService = $container->getDefinition('oneapp_mobile_upselling.v2_0.offer_details_rest_logic');
    $offerDetailsService->setClass('Drupal\oneapp_mobile_upselling_bo\Services\v2_0\OfferDetailsRestLogicBo');

    $definition = $container->getDefinition('oneapp_mobile_upselling.v2_0.data_balance_detail_rest_logic');
    $definition->setClass('Drupal\oneapp_mobile_upselling_bo\Services\v2_0\DataBalanceDetailRestLogicBo');

    $definition = $container->getDefinition('oneapp_mobile_upselling.v2_0.data_balance_rest_logic');
    $definition->setClass('Drupal\oneapp_mobile_upselling_bo\Services\v2_0\DataBalanceRestLogicBo');

    $availableOffersDefinition = $container->getDefinition('oneapp_mobile_upselling.v2_0.available_offers_rest_logic');
    $availableOffersDefinition->setClass('Drupal\oneapp_mobile_upselling_bo\Services\v2_0\AvailableOffersRestLogicBo');

    $paymentMService = $container->getDefinition('oneapp_mobile_upselling.v2_0.packets_order_details_rest_logic');
    $paymentMService->setClass('Drupal\oneapp_mobile_upselling_bo\Services\v2_0\PacketsOrderDetailsRestLogicBo');

    $acquiredOffersService = $container->getDefinition('oneapp_mobile_upselling.v2_0.acquired_offers_rest_logic');
    $acquiredOffersService->setClass('Drupal\oneapp_mobile_upselling_bo\Services\v2_0\AcquiredOffersRestLogicBo');

    $rechargeLendingDefinition = $container->getDefinition('oneapp_mobile_upselling.v2_0.recharge_lending_amounts_rest_logic');
    $rechargeLendingDefinition->setClass('Drupal\oneapp_mobile_upselling_bo\Services\v2_0\RechargeLendingAmountsRestLogicBo');

    $voiceBalance = $container->getDefinition('oneapp_mobile_upselling.v2_0.voice_balance_rest_logic');
    $voiceBalance->setClass('Drupal\oneapp_mobile_upselling_bo\Services\v2_0\VoiceBalanceRestLogicBo');

    $rechargeOrderDetailsBoService = $container->getDefinition('oneapp_mobile_upselling.v2_0.recharge_order_details_rest_logic');
    $rechargeOrderDetailsBoService->setClass('Drupal\oneapp_mobile_upselling_bo\Services\v2_0\RechargeOrderDetailsRestLogicBo');

    $smsDefinition = $container->getDefinition('oneapp_mobile_upselling.v2_0.sms_balance_rest_logic');
    $smsDefinition->setClass('Drupal\oneapp_mobile_upselling_bo\Services\v2_0\SmsBalanceRestLogicBo');
    $changeMsisdnDefinition = $container->getDefinition('oneapp_mobile_upselling.v2_0.change_msisdn_rest_logic');
    $changeMsisdnDefinition->setClass('Drupal\oneapp_mobile_upselling_bo\Services\v2_0\ChangeMsisdnRestLogicBo');

    $coreBalancesService = $container->getDefinition('oneapp_mobile_upselling.v2_0.core_balances_services');
    $coreBalancesService->setClass('Drupal\oneapp_mobile_upselling_bo\Services\CoreBalancesServicesBo');
  }

}
