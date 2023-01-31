<?php

namespace Drupal\oneapp_mobile_upselling_bo\Services\v2_0;

use Drupal\oneapp_mobile_upselling\Services\v2_0\VoiceBalanceRestLogic;

/**
 * Class VoiceBalanceRestLogicBo.
 */
class VoiceBalanceRestLogicBo extends VoiceBalanceRestLogic {

  /**
   * Responds to GET requests.
   *
   * @param string $msisdn
   *   Msisdn.
   *
   * @return array
   *   The associative array.
   *
   * @throws \ReflectionException
   */
  public function get($msisdn) {
    $rows = [];
    $index = 0;
    $block_configs = $this->configBlock['voiceBalance'];

    // Get voice balance.
    $service = \Drupal::service('oneapp_mobile_upselling.service.data_balance');
    $bucket_list = $service->getBucketsVoice($msisdn);
    $bucket_list = isset($bucket_list->balances) ? $bucket_list->balances : $bucket_list;
    $bucket_list_ilimited = $this->utilsMobile->bucketIlimited($bucket_list, ['Minutos']);
    $bucket_list_sanitized = $this->utilsMobile->bucketSanitized($bucket_list, ['Seg']);
    $bucket_list_sanitized = array_merge($bucket_list_ilimited, $bucket_list_sanitized);
    if (count($bucket_list_sanitized) == 0) {
      return [
        'noData' => ['value' => 'empty'],
      ];
    }

    foreach ($bucket_list_sanitized as $key => $bucket) {

      $row = [];
      $un_limited = $bucket->balanceAmount === "Ilimitado";

      $row['unlimited'] = [
        'value' => $un_limited,
      ];

      foreach ($block_configs as $id => $field) {

        $row[$id] = [
          'label' => $field['label'],
          'show' => ($field['show']) ? TRUE : FALSE,
        ];

        switch ($id) {
          case 'bucketsId':
            $row[$id]['value'] = $key;
            $row[$id]['formattedValue'] = $key;
            break;

          case 'friendlyName':
            $row[$id]['value'] = $bucket->description;
            $row[$id]['formattedValue'] = $bucket->description;
            if ($un_limited) {
              $row[$id]['class'] = $un_limited;
            }
            break;

          case 'remainingValue':
            if ($un_limited) {
              $row[$id]['value'] = 1;
              $row[$id]['formattedValue'] = $this->configBlock['config']['messages']['unlimitedBucket'];
              $row[$id]['label'] = '';
            }
            else {
              if (isset($bucket->balanceAmount)) {
                $minSeg = [
                  '@min' => intval($bucket->balanceAmount / 60),
                  '@seg' => intval($bucket->balanceAmount % 60),
                ];
                $row[$id]['value'] = $bucket->balanceAmount;
                $row[$id]['formattedValue'] = t("@min Min @seg Seg", $minSeg);
              }
              else {
                $row[$id]['value'] = 0;
                $row[$id]['formattedValue'] = 0 . ' ' . $field['description'];
              }
            }
            break;

          case 'reservedAmount':
            if ($un_limited) {
              $row[$id]['value'] = 1;
              $row[$id]['formattedValue'] = '';
              $row[$id]['label'] = '';
            }
            else {
              $row[$id]['value'] = 0;
              $row[$id]['formattedValue'] = 0 . ' ' . $field['description'];
            }
            break;

          case 'endDateTime':
            $end_date = isset($bucket->expirationDate) ? $bucket->expirationDate : '';
            $row[$id]['value'] = $end_date;
            $remaining_time = !empty($end_date) ? $this->utilsMobile->formatDateRegressive($end_date) : '';
            $row[$id]['formattedValue'] = !empty($end_date) ? $field['prefix'] . ' ' . $remaining_time : '';
            break;
        }
      }
      $row['isActive']['value'] = $this->isActive($row);
      $row['showBar']['value'] = $this->showBar($row);
      $rows[$index] = $row;
      $index++;
    }

    if (empty($rows)) {
      $rows[$index] = $this->getFormattedListBucketsEmpty($block_configs);
    }

    return ['voiceBalance' => $rows];
  }

  /**
   * Implements flag showBar.
   *
   * @return bool
   * The showBar Conditions.
   */
  public function showBar($row) {
    return FALSE;
  }

  /**
   * @param $account_id
   * @return array|mixed
   */
  public function getConfigs($account_id) {
    $configs = parent::getConfigs($account_id);
    $min = $this->configBlock["config"]["webcomponent"]["supportedVersions"]["min"];
    $max = $this->configBlock["config"]["webcomponent"]["supportedVersions"]["max"];
    $show = $this->configBlock["config"]["webcomponent"]["show"];
    $this->configBlock["config"]["webcomponent"]["supportedVersions"]["min"] = empty($min) ? '1.0.0' : $min;
    $this->configBlock["config"]["webcomponent"]["supportedVersions"]["max"] = empty($max) ? NULL : $max;
    $this->configBlock["config"]["webcomponent"]["show"] = boolval($show);
    $configs['showDetailWebComponent'] = $this->configBlock["config"]["webcomponent"];
    return $configs;
  }

}
