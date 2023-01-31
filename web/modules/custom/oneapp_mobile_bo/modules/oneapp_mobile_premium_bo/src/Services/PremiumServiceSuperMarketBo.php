<?php

namespace Drupal\oneapp_mobile_premium_bo\Services;

use Drupal\oneapp_mobile_premium\Services\PremiumServiceSuperMarket;

/**
 * Class PremiumServiceSuperMarketBo.
 *
 * @package Drupal\oneapp_mobile_premium_bo\Services;
 */
class PremiumServiceSuperMarketBo extends PremiumServiceSuperMarket {

  protected function getType2($service, $available_offers, $active_offers) {
    $config_service = $service->get('service_config')->value;
    $config_service = $config_service != NULL && $config_service != '' ? json_decode($config_service, TRUE) : NULL;
    $prefix = "config_";
    $ids_bundle = explode(',', $config_service[$prefix . 'id_bundle']);
    $ids_addon = explode(',', $config_service[$prefix . 'id_addon']);
    $ids_addon_full = explode(',', $config_service[$prefix . 'id_addon_full']);

    foreach ($ids_addon as $id_addon) {
      if ($id_addon != '' &&  in_array($id_addon, array_column($active_offers, 'offeringId'))) {
        return ['id' => $id_addon, 'type' => 'ADDON'];
      }
    }
    foreach ($ids_addon_full as $id_addon_full) {
      if ($id_addon_full != '' && in_array($id_addon_full, array_column($active_offers, 'offeringId'))) {
        return ['id' => $id_addon_full, 'type' => 'ADDON-FULL'];
      }
    }
    foreach ($ids_bundle as $id_bundle) {
      if ($id_bundle != '' &&  in_array($id_bundle, array_column($active_offers, 'offeringId'))) {
        return ['id' => $id_bundle, 'type' => 'BUNDLE'];
      }
    }

    foreach ($ids_addon as $id_addon) {
      if ($id_addon != '' &&  in_array($id_addon, array_column($available_offers, 'offeringId'))) {
        return ['id' => $id_addon, 'type' => 'ADDON'];
      }
    }
    foreach ($ids_addon_full as $id_addon_full) {
      if ($id_addon_full != '' && in_array($id_addon_full, array_column($available_offers, 'offeringId'))) {
        return ['id' => $id_addon_full, 'type' => 'ADDON-FULL'];
      }
    }
    foreach ($ids_bundle as $id_bundle) {
      if ($id_bundle != '' &&  in_array($id_bundle, array_column($available_offers, 'offeringId'))) {
        return ['id' => $id_bundle, 'type' => 'BUNDLE'];
      }
    }

    return NULL;
  }

  /**
   * Subscribe to amazon.
   */
  public function subscribe($id, $product, $payload, $available_offers, $active_offers) {

    try {
      $id_product = $product->get('id_service')->value;
      $config_service = $product->get('service_config')->value;
      $config_service = $config_service != NULL && $config_service != '' ? json_decode($config_service, TRUE) : NULL;

      $prefix = $this->utils::PREFIX_CONFIG;
      $telco_code = $config_service[$prefix . 'telco_code'];
      $service_id = $config_service[$prefix . 'service_id'];

      $type_service = $this->getType2($product, $available_offers, $active_offers);

      $id = $this->getIdWithPrefix($id);
      $params['productName'] = $id_product;
      $params['msisdn'] = $id;
      $params['telcoCode'] = $telco_code;
      $params['requestId'] = $id . time();
      $params['promoId'] = [$type_service['id']];

      $response = $this->callDocomoSupermarketSubscribeApi($service_id, $params);

      $data_entity = $this->getDataEntity($product, $available_offers, $active_offers);
      return $data_entity;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

}
