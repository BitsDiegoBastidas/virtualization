<?php

namespace Drupal\oneapp_mobile_premium_bo\Services;

use \Drupal\oneapp_mobile_premium\Services\PremiumService;

/**
 * Class PremiumServiceBo.
 *
 * @package Drupal\oneapp_mobile_premium_bo\Services;
 */
class PremiumServiceBo extends PremiumService {

  /**
   * Data operator_id.
   *
   * @var mixed
   */
  protected $operator_id;

  /**
   * Responds to setConfig.
   *
   * @param mixed $config_block
   *   Config card or default.
   */
  public function setOperatorId($operator_id) {
    $this->operator_id = $operator_id;
  }

  public function getAvailableOffers($id) {
    $headers = ['channel' => 'amazon'];

    try {
      $id = $this->getIdWithoutPrefix($id);
      $available_offers_api = $this->callGetAvailableOffersApi($id, $headers);
      $available_offers = [];
      foreach ($available_offers_api as $availableOffer) {
        if (isset($availableOffer->offerId)) {
          $available_offers[] = ['offeringId' => $availableOffer->offerId, 'offer' => $availableOffer];
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

  public function getActiveOffers($id) {
    $headers = ['channel' => 'amazon'];
    $id = $this->getIdWithoutPrefix($id);
    $active_offers_api = $this->callGetActiveOffersApi($id, $headers);

    $active_offers = [];

    if (isset($active_offers_api->additionalRecurrentOfferingList)) {
      foreach ($active_offers_api->additionalRecurrentOfferingList as $product) {
        if (isset($product->additionalOfferingId)) {
          $active_offers[] = ['offeringId' => $product->additionalOfferingId, 'offer' => $product];
        }
      }
    }

    return $active_offers;
  }

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
    $data = $this->manager
      ->load('oneapp_mobile_premium_v2_0_validations_endpoint')
      ->setParams(['id' => $id])
      ->setHeaders([])
      ->setQuery([])
      ->sendRequest();
    return $data;
  }

  /**
   * Implements getTigoSport api.
   *
   * @param string $id
   *   Account Id.
   * @return mixed
   *   The HTTP response object.
   *
   * @throws \Drupal\oneapp\Exception\HttpException
   */
  public function getTigoSport($id, $headers = []) {

    try {
      $data = $this->manager
        ->load('oneapp_mobile_premium_v2_0_tigo_sport_endpoint')
        ->setParams(['id' => $id])
        ->setHeaders($headers)
        ->setQuery(['operatorId' => $this->operator_id])
        ->sendRequest();
      $data->result->expirationDate = $data->result->endDate;
      $data->subscriptions = $data->result;
    }
    catch (\Exception $e) {
      $data = [];
    }


    return $data;
  }

}
