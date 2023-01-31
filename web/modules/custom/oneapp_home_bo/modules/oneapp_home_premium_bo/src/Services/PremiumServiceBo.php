<?php

namespace Drupal\oneapp_home_premium_bo\Services;

use Drupal\oneapp_home_premium\Services\PremiumService;

/**
 * Class PremiumServiceBo.
 *
 * @package Drupal\oneapp_home_premium_bo\Services;
 */
class PremiumServiceBo extends PremiumService {

  /**
   * {@inheritdoc}
   */
  public function getActiveOffers($id) {
    $id = $this->getIdWithoutPrefix($id);
    $active_offers_api = $this->callGetActiveOffersApi($id);
    $active_offers = [];

    if (isset($active_offers_api)) {
      foreach ($active_offers_api as $key1 => $value1) {
        foreach ($value1->productList as $key => $value) {
          if (isset($value->offeringList)) {
            foreach ($value->offeringList as $key => $offering) {
              if (isset($offering->offeringId)) {
                $active_offers[] = ['offeringId' => $offering->offeringId, 'offer' => json_decode(json_encode($offering), TRUE)];
              }
            }
          }
        }
      }
    }
    return $active_offers;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableOffers($id) {
    try {
      $id = $this->getIdWithoutPrefix($id);
      $available_offers_api = $this->callGetAvailableOffersApi($id, 0);
      $available_offers = [];

      foreach ($available_offers_api as $availableOffer) {
        if (isset($availableOffer->offeringId)) {
          $available_offers[] = ['offeringId' => $availableOffer->offeringId, 'offer' => json_decode(json_encode($availableOffer), TRUE)];
        }
      }
    }
    catch (\Exception $exception) {
      if ($exception->getCode() == '404') {
        return ['noData' => 'empty'];
      }
      else {
        return [];
      }
    }
    return $available_offers;
  }

  /**
   * Get id with prefix.
   */
  protected function getIdWithoutPrefix($id) {
    $config = \Drupal::config('oneapp_endpoints.settings')->getRawData();
    $prefix_country = $config['prefix_country'];
    if (substr($id, 0, strlen($prefix_country)) == $prefix_country) {
      $id = substr($id, strlen($prefix_country));
    }
    return $id;
  }

  /**
   * Validate Line if can to contract addons.
   */
  public function validateLine($id) {
    try {
      $id = parent::getIdWithoutPrefix($id);
      return $this->callGetValidateApi($id);
    }
    catch (\Exception $exception) {
      throw $exception;
    }
  }

  /**
   * Implements getValidate api.
   *
   * @param string $id
   *   Account Id.
   * @return mixed
   *   The HTTP response object.
   *
   * @throws \Drupal\oneapp\Exception\HttpException
   */
  public function callGetValidateApi($id) {
    return $this->manager
      ->load('oneapp_home_premium_v2_0_validations_endpoint')
      ->setParams(['id' => $id])
      ->setHeaders([])
      ->setQuery([])
      ->sendRequest();
  }

}
