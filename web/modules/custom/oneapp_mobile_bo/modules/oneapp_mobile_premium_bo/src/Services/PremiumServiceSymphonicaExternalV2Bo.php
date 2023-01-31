<?php

namespace Drupal\oneapp_mobile_premium_bo\Services;

use Drupal\oneapp_mobile_premium\Services\PremiumServiceSymphonicaExternalV2;

/**
 * Class PremiumServiceSymphonicaExternalV2Bo.
 *
 * @package Drupal\oneapp_mobile_premium_bo\Services;
 */
class PremiumServiceSymphonicaExternalV2Bo extends PremiumServiceSymphonicaExternalV2 {

  /**
   * Subscribe to channel.
   */
  public function subscribe($id, $product, $payload, $available_offers, $active_offers) {
    try {
      $this->init($product, $available_offers, $active_offers);
      $id = $this->utils->getIdWithPrefix($id);
      $offer_id = $this->offerData['offerId'];
      $type = 'mobile';
      $offer_type = strtolower(str_replace('-', '_', $this->offerData['type']));

      if (!isset($payload['upgrade'])) { // Standalone
        $optional = ['com' => TRUE, 'action' => 'add', 'offerType' => $offer_type];
      }
      elseif (!empty($payload['upgrade'])) { // Upgrade
        $optional = ['com' => TRUE, 'action' => 'update', 'offerType' => $offer_type];
        $optional['oldOfferId'] = $this->getOldOfferId($active_offers);
      }
      return $this->subscribeDisney($id, $offer_id, $type, $optional);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Unsubscribe to channel.
   */
  public function unsubscribe($id, $product, $payload, $available_offers, $active_offers) {
    try {
      $this->init($product, $available_offers, $active_offers);
      $id = $this->utils->getIdWithPrefix($id);
      $type = 'mobile';
      $offer_type = strtolower(str_replace('-', '_', $this->offerData['type']));

      if (!isset($payload['downgrade'])) { // Standalone
        $offer_id = $this->offerData['offerId'];
        $optional = ['com' => TRUE, 'action' => 'terminate', 'offerType' => $offer_type];
      }
      elseif (!empty($payload['downgrade'])) { // Downgrade
        $offer_id = $payload['sku'];
        $optional = ['com' => TRUE, 'action' => 'update', 'offerType' => $offer_type];
        $optional['oldOfferId'] = $this->offerData['offerId'];
        $optional['skuDowngrade'] = $this->getSkuDowngrade($product, $offer_id);
      }
      return $this->unSubscribeDisney($id, $offer_id, $type, $optional);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

}
