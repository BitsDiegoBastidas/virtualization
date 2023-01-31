<?php

namespace Drupal\oneapp_mobile_premium_bo\Services\v2_0;

use Drupal\oneapp_mobile_premium\Services\v2_0\PremiumTigoSportRestLogic;
use Drupal\file\Entity\File;
use stdClass;

/**
 * Class PremiumRestLogic.
 */
class PremiumTigoSportRestLogicBo extends PremiumTigoSportRestLogic {
  /**
   * Get data all premium products formated.
   *
   * @return array
   *   Return fields as array of objects.
   */
  public function get($accountId) {

    $this->service->setOperatorId($this->configBlock["configs"]["operator"]["id"]);
    $products = $this->service->getTigoSport($accountId);

    if (empty($products) || !isset($products->subscriptions) || empty($products->subscriptions)) {
      return ['noData' => ['value' => 'empty']];
    }
    $product = $products->subscriptions;
    $rows = [];
    $date_formatter = \Drupal::service('date.formatter');
    $util_service = \Drupal::service('oneapp.utils');
    foreach ($this->configBlock['fields'] as $id => $field) {
      $row[$id] = [
        'label' => $field['label'],
        'show' => ($field['show']) ? TRUE : FALSE,
      ];

      switch ($id) {
        case 'productId':
          $row[$id]['value'] = isset($product->productId) ? $product->productId : "---------";
          $row[$id]['formattedValue'] = isset($product->productId) ? $product->productId : "---------";
          break;

        case 'name':
          $description = isset($field['description']) && !empty($field['description']) ? $field['description'] : t('Mundial Qatar 2022 EN VIVO por Tigo Sports');
          $row[$id]['value'] = isset($product->name) && !empty($product->name) ? $product->name : $description;
          $row[$id]['formattedValue'] = isset($product->name) && !empty($product->name) ? $product->name : $description;
          break;

        case 'currency':
          $currency = isset($product->currency) && $product->currency != '' ? $product->currency : '--------';
          $row[$id]['value'] = $currency;
          $row[$id]['formattedValue'] = $currency;
          break;

        case 'expirationDate':
          $row[$id]['value'] = isset($product->expirationDate) && $product->expirationDate != '' ? $product->expirationDate : '--------';
          $row[$id]['formattedValue'] = isset($product->expirationDate) && $product->expirationDate != "" ? $date_formatter->format(strtotime($product->expirationDate), $field["format"]) : "---------";
          break;

        case 'nextRenewalDate':
          $remainingTime = isset($product->nextRenewalDate) && $product->nextRenewalDate != '' ? $util_service->formatRemainingTimeDayHour($product->nextRenewalDate) : '';
          $row[$id]['value'] = isset($product->nextRenewalDate) && $product->nextRenewalDate != '' ? $product->nextRenewalDate : '--------';
          $row[$id]['formattedValue'] = $remainingTime != '' && $remainingTime != NULL ? t('Valido por:') . $remainingTime : ($remainingTime == NULL ? t('Vence hoy') : '--------');
          break;

      }
      $rows[$id] = $row[$id];
    }

    return $rows;
  }

}
