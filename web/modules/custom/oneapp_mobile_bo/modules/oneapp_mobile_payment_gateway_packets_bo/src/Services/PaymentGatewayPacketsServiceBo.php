<?php

namespace Drupal\oneapp_mobile_payment_gateway_packets_bo\Services;

use Drupal\oneapp_mobile_payment_gateway_packets\Services\PaymentGatewayPacketsService;

/**
 * Class PaymentGatewayPacketsService.
 */
class PaymentGatewayPacketsServiceBo extends PaymentGatewayPacketsService {

  /**
   * {@inheritdoc}
   */
  public function getGInfo($msisdn) {
    return $this->manager
      ->load('oneapp_mobile_v2_0_client_account_general_info_endpoint')
      ->setParams(['id' => $msisdn])
      ->setHeaders([])
      ->setQuery(['searchType' => 'MSISDN', 'documentType' => 1])
      ->sendRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function getIdOfferBySystemOfferId($package_id) {
    $id_packet = '';
    if ($package_id) {
      $entity = $this->entityTypeManager->getStorage('paquetigos_entity');
      $ids = $entity->getQuery()->execute();
      $paquetigos = $entity->loadMultiple($ids);
      foreach ($paquetigos as $paquetigo) {
        $system_offer_id = $paquetigo->getSystemOfferId();
        if ($system_offer_id == $package_id) {
          $id_packet = $paquetigo->getIdOffer();
          break;
        }
      }
      return $id_packet;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSystemOfferIdByIdOffer($package_id) {
    $system_offer_id = '';
    if ($package_id) {
      $entity = $this->entityTypeManager->getStorage('paquetigos_entity');
      $ids = $entity->getQuery()->execute();
      $paquetigos = $entity->loadMultiple($ids);
      foreach ($paquetigos as $paquetigo) {
        $id_packet = $paquetigo->getIdOffer();
        if ($id_packet == $package_id) {
          $system_offer_id = $paquetigo->getSystemOfferId();
          break;
        }
      }
      return $system_offer_id;
    }
    return FALSE;
  }

}
