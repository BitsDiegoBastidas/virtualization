<?php

namespace Drupal\oneapp_mobile_upselling_bo\Services\v2_0;

use Drupal\oneapp\Exception\HttpException;
use Drupal\oneapp_mobile_upselling\Services\v2_0\OfferDetailsRestLogic;

/**
 * Class OfferDetailsRestLogic.
 */
class OfferDetailsRestLogicBo extends OfferDetailsRestLogic {

  /**
   * {@inheritdoc}
   */
  public function get($msisdn, $offer_id) {
    $offer = $this->getOffer($msisdn, $offer_id);
    $is_loan_offer = false;
    if (substr($offer_id, 0, 2) === 'tp') {
      $offer_id = explode(',', $offer_id)[1];
      $is_loan_offer = true;
    }
    $offer_loan = $this->getOfferLoan($msisdn, $offer_id);

    if ($offer != NULL) {
      $parameters = [];
      foreach ($offer->acquisitionMethods as $acquisitionMethod) {
        if (is_array($acquisitionMethod->acquisitionMethod)) {
          foreach ($acquisitionMethod->acquisitionMethod as $method) {
            $acquisition_methods[] = $method;
          }
        }
        if (is_array($acquisitionMethod->acquisitionTypeId)) {
          foreach ($acquisitionMethod->acquisitionTypeId as $typeId) {
            $acquisition_type_id[] = $typeId;
          }
        }
        else {
          $acquisition_methods[] = $acquisitionMethod->acquisitionMethod;
          $acquisition_type_id[] = $acquisitionMethod->acquisitionTypeId;
        }
      }
      if (is_array($acquisition_type_id) && is_array($acquisition_methods)) {
        foreach ($acquisition_type_id as $key => $idMethods) {
          foreach ($acquisition_methods as $value => $methods) {
            if ($key == $value) {
              $parameters[$key] = [
                'id' => $idMethods,
                'paymentMethodName' => $methods,
              ];
            }
          }
        }
      }
      else {
        $parameters[0] = [
          'id' => $acquisition_type_id,
          'paymentMethodName' => $acquisition_methods,
        ];
      }

      $value = [
        'amount' => isset($offer->cost) ? $offer->cost : '',
        'currencyId' => \Drupal::service('oneapp.utils')->getCurrencyCode(FALSE),
      ];
      $data = [
        'offerId' => isset($offer->packageId) ? $offer->packageId : '',
        'type' => isset($offer->type) ? $offer->type : '',
        'cost' => [$value],
        'currency' => isset($offer->currency) ? $offer->currency : '',
        'name' => isset($offer->name) ? $offer->name : '',
        'description' => isset($offer->description) ? $offer->description : '',
        'category' => isset($offer->category) ? $offer->category : '',
        'validity' => isset($offer->validity) ? $offer->validity : '',
        'validityNumber' => isset($offer->validityNumber) ? $offer->validityNumber : '',
        'validityType' => isset($offer->validityType) ? $offer->validityType : '',
        'additionalData' => [
          'acquisitionMethods' => $parameters,
        ],
      ];
    }
    else if ($offer_loan != NULL) {
      $parameters = [];

      $parameters[0]['emergencyLoan'] = [
        'isRecurrent' => false,
        'label' => "Tigo te Presta",
        'paymentMethodName' => "emergencyLoan",
        'show' => "",
        'type' => "link",
        'url' => "/"
       ];


      $value = [
        'amount' => isset($offer_loan->cost) ? $offer_loan->cost : '',
        'currencyId' => \Drupal::service('oneapp.utils')->getCurrencyCode(FALSE),
      ];
      $data = [
        'offerId' => isset($offer_loan->productID) ? $offer_loan->productID: '',
        'type' => isset($offer_loan->productType) ? $offer_loan->productType : '',
        'cost' => [$value],
        'currency' => isset($offer_loan->currency) ? $offer_loan->currency : '',
        'name' => isset($offer_loan->productName) ? $offer_loan->productName : '',
        'description' => isset($offer_loan->productDescription) ? $offer_loan->productDescription : '',
        'category' => isset($offer_loan->productCategory) ? $offer_loan->productCategory : '',
        'validity' => isset($offer_loan->validityType) ? $offer_loan->validityType : '',
        'validityNumber' => isset($offer_loan->validityNumber) ? $offer_loan->validityNumber : '',
        'validityType' => isset($offer_loan->validityType) ? $offer_loan->validityType : '',
        'additionalData' => [
          'acquisitionMethods' => $parameters,
          'show_only_loan_method' => $is_loan_offer
        ],
      ];
    }
    else {
      $data = [
        'error' => [
          'code' => '404 not found',
          'description' => 'No se encontró la oferta.',
        ],
      ];
    }
    return $data;
  }

  /**
   * Implements getAvailableOffers.
   *
   * @param string $msisdn
   *   Msisdn value.
   * @param string $offer_id
   *   OfferId value.
   *
   * @return ResponseInterface
   *   The HTTP response object.
   *
   * @throws \ReflectionException
   */
  public function getOffer($msisdn, $offer_id) {
    $replacers = ['/NBO-/'];
    $offer_id = preg_replace($replacers, '', $offer_id);

    try {
      $offers = $this->manager
        ->load('oneapp_mobile_upselling_v2_0_available_offers_endpoint')
        ->setHeaders([])
        ->setQuery([])
        ->setParams(['msisdn' => $msisdn])
        ->sendRequest();

      $offers = property_exists($offers, "packages") ? $offers->packages->products : $offers->products;

      foreach ($offers as $offer) {
        $offer->packageId = (string) preg_replace($replacers, '', $offer->packageId);

        if ($offer->packageId == $offer_id) {
          return $offer;
        }
      }
    }
    catch (HttpException $exception) {
      // TODO Validar si retornar NULL.
    }
    return NULL;
  }


  /**
   * Implements getAvailableOffers.
   *
   * @param string $msisdn
   *   Msisdn value.
   * @param string $offer_id
   *   OfferId value.
   *
   * @return ResponseInterface
   *   The HTTP response object.
   *
   * @throws \ReflectionException
   */
  public function getOfferLoan($msisdn, $offer_id) {
    try {
      $offers = \Drupal::service('oneapp_mobile_upselling.v2_0.available_offers_rest_logic')->getLendingScoring($msisdn);
      $offers = is_array($offers) ? $offers : [];

      foreach ($offers as $offer) {
        if ($offer->productID == $offer_id) {
          return $offer;
        }
      }
    }
    catch (HttpException $exception) {
      return NULL;
    }
  }

  /**
   * Returns the ids of the packages based in the paquetigos entities
   *
   * @param string $package_id
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getListPaquetigosIds(string $package_id): array {
    $filters = [
      'idOffer' => 'getSystemOfferId',
      'systemOfferId' => 'getIdOffer',
    ];
    $package_ids = [$package_id];

    foreach ($filters as $field => $method) {
      $packages = \Drupal::entityQuery('paquetigos_entity')
        ->condition($field, $package_id, '=')
        ->range(0, 1)
        ->execute();

      if (! empty($packages)) {
        $package = \Drupal::entityTypeManager()
          ->getStorage('paquetigos_entity')
          ->load(reset($packages));

        $package_ids[] = $package->{$method}();
      }
    }

    return array_filter(array_unique($package_ids));
  }

  /**
   * Try load the offer by the package_id but by normal package id and the system package id
   *
   * @param string|array $msisdn
   * @param string $package_id
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getOffersByOfferOrOfferSystem($msisdn, string $package_id): array {
    $package_ids = $this->getListPaquetigosIds($package_id);
    $msisdn_list = array_filter(is_array($msisdn) ? $msisdn : [$msisdn]);
    $offer = [
      'error' => ['code' => '404 not found', 'description' => 'No se encontró la oferta.'],
      'accountId' => $msisdn_list[0],
    ];

    while (count($msisdn_list) > 0) {
      $msisdn = array_pop($msisdn_list);
      $found = false;

      foreach ($package_ids as $local_package_id) {
        $offer = $this->get($msisdn, $local_package_id);
        $found = isset($offer['offerId']);

        if ($found) {
          break;
        }
      }

      if ($found) {
        break;
      }
    }

    return $offer;
  }
}
