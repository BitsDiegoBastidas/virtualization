<?php

namespace Drupal\oneapp_home_services_bo\Services\v2_0;

use Drupal\oneapp_home_services\Services\v2_0\ServicesRestLogic;

/**
 * Class ServicesRestLogicBo.
 */
class ServicesRestLogicBo extends ServicesRestLogic {

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $utils;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $manager;

  /**
   * Override getAllProducts method to show the active products only.
   *
   * @return array
   *   AllProducts
   */
  public function getAllProducts($id) {
    $response = $this->products->retrieveProductsList($id);
    if (isset($response->code)) {
      return $response;
    }
    $products = [];
    $firstPlan = reset($response);
    if (isset($firstPlan->productList) && !empty($firstPlan->productList)) {
      foreach ($firstPlan->productList as $key => $value) {
        if (isset($value->productName) && $value->productName != NULL) {
          $offeringList = [];
          if (isset($value->offeringList)) {
            foreach ($value->offeringList as $key => $offering) {
              $offeringList[$key]['offeringName'] = $offering->offeringName;
              $offeringList[$key]['offeringType'] = isset($offering->offeringType) ? $offering->offeringType : '';
              $offeringList[$key]['subscriptionNumber'] = $offering->subscriptionNumber;
              if (!empty($offering->detailList)) {
                foreach ($offering->detailList as $device) {
                  if (isset($device->manufacturer) && empty($device->manufacturer)) {
                    unset($device->manufacturer);
                  }
                  $offeringList[$key]['deviceList'][$device->deviceId] = [
                    'serialNumber' => !empty($device->serialNumber) ? $device->serialNumber : '',
                    'extendedUniqueIdentifier' => !empty($device->extendedUniqueIdentifier) ? $device->extendedUniqueIdentifier : '',
                    'status' => $device->active ? t('Activo') : '',
                  ];
                }
              }
            }
          }

          if (!empty($offeringList)) {
            $product = [
              'productId' => $value->productId,
              'productName' => $value->productName,
              'productClass' => strtolower(str_replace(' ', '_', $value->productName)),
              'offeringList' => $offeringList,
            ];
            array_push($products, $product);
            unset($product);
          }
        }
      }
    }
    $products['additionalData']['subtitle']['value'] = !empty($firstPlan->plantName) ? $firstPlan->plantName : '';
    $products['additionalData']['subtitle']['show'] = !empty($firstPlan->plantName) ? TRUE : FALSE;
    unset($response);
    return $products;
  }

  /**
   * Override getProductDetails method.
   *
   * @param string $id
   *   Id.
   * @param string $productId
   *   ProductId.
   * @param string $subscriptionNumber
   *   SubscriptionNumber.
   *
   * @return mixed
   *   response formatted
   */
  public function getProductDetails($id, $productId, $subscriptionNumber) {
    $product = $this->getProductById($id, $productId);
    if (isset($product->offeringList) && count($product->offeringList) > 0) {
      foreach ($product->offeringList as $offering) {
        if ($offering->subscriptionNumber == $subscriptionNumber) {
          $productDetails = $offering;
        }
      }
    }
    if (isset($productDetails->deviceList) && !empty($productDetails->deviceList)) {
      $response['productId'] = $product->productId;
      $response['productName'] = $product->productName;
      $response['offeringId'] = $productDetails->offeringId;
      $response['offeringName'] = $productDetails->offeringName;
      $response['subscriptionNumber'] = $productDetails->subscriptionNumber;
      $response['serviceAddress'] = $productDetails->serviceAddress;
      $response['supportsSpeedButton'] = $productDetails->supportsSpeedButton;
      $response['supportsNGTV'] = $productDetails->supportsNGTV;
      $response['status'] = $productDetails->active;

      foreach ($productDetails->deviceList as $device) {
        if (isset($device->manufacturer) && empty($device->manufacturer)) {
          unset($device->manufacturer);
        }
        $response['devices'][$device->deviceId] = [
          'modelName' => $device->modelname,
          'extendedUniqueIdentifier' => $device->extendedUniqueIdentifier,
        ];
      }
    }
    else {
      throw new \Exception(t('El cliente no tiene detalles relacionados'));
    }
    $response['productClass'] = strtolower(str_replace(' ', '_', $product->productName));

    unset($product);
    unset($product_details);

    return $response;
  }

  /**
   * Override getAllProducts method to show the active products only.
   *
   * @return array
   */
  public function formatPortfolio($response, $config) {
    $portfolio = [];
    $productFields = $config["options"]["tables"]["productData"]["fields"];
    uasort($productFields, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    $offeringFields = $config["options"]["tables"]["offeringData"]["fields"];
    uasort($offeringFields, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    $deviceFields = $config["options"]["tables"]["deviceList"]["fields"];
    uasort($deviceFields, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    foreach ($response as $productKey => $value) {
      foreach ($productFields as $productField => $productFieldValue) {
        foreach ($value as $field => $fieldValue) {
          if ($productField == $field) {
            if ($field == "productName") {
              $portfolio[$productKey][$field] = [
                'label' => $productFieldValue["label"],
                'value' => $fieldValue,
                'formattedValue' => (string) $fieldValue,
                'class' => $value["productClass"],
                'show' => (isset($productFieldValue["show"]) && $productFieldValue["show"]) ? TRUE : FALSE,
              ];
            }
            else {
              $portfolio[$productKey][$field] = [
                'label' => $productFieldValue["label"],
                'value' => $fieldValue,
                'formattedValue' => (string) $fieldValue,
                'show' => (isset($productFieldValue["show"]) && $productFieldValue["show"]) ? TRUE : FALSE,
              ];
            }
          }
        }
      }

      foreach ($value["offeringList"] as $key => $offering) {
        foreach ($offeringFields as $offeringField => $offeringFieldValue) {
          foreach ($offering as $offeringIndex => $offeringValue) {
            if ($offeringField == $offeringIndex) {
              $portfolio[$productKey]['offeringList'][$key][$offeringField] = [
                'label' => $offeringFieldValue["label"],
                'value' => $offeringValue,
                'formattedValue' => (string) $offeringValue,
                'show' => (isset($offeringFieldValue["show"]) && $offeringFieldValue["show"]) ? TRUE : FALSE,
              ];
              if ($offeringField == 'offeringName') {
                $portfolio[$productKey]['offeringList'][$key][$offeringField]['label'] =
                  !empty($offering['offeringType']) ? $offeringFieldValue["label"] : '';
              }
            }
          }
        }

        $deviceList = [];
        if (!empty($offering["deviceList"])) {
          foreach ($offering["deviceList"] as $index => $device) {
            $deviceItem = [];
            foreach ($deviceFields as $deviceFieldIndex => $deviceFieldsValue) {
              $deviceItem[$deviceFieldIndex] = [
                'label' => isset($deviceFieldsValue["label"]) ? $deviceFieldsValue["label"] : '',
                'value' => isset($device[$deviceFieldIndex]) ? $device[$deviceFieldIndex] : '',
                'formattedValue' => isset($device[$deviceFieldIndex]) ? (string) $device[$deviceFieldIndex] : '',
                'show' => $deviceFieldsValue["show"] && !empty($device[$deviceFieldIndex]) ? TRUE : FALSE,
              ];
            }
            array_push($deviceList, $deviceItem);
          }
          $portfolio[$productKey]['offeringList'][$key]['devicesList'] = $deviceList;
        }
      }
    }

    $result['products'] = $portfolio;
    return $result;
  }

}
