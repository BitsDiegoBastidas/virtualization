<?php

namespace Drupal\oneapp_mobile_upselling_bo\Services\v2_0;

use Drupal\oneapp_mobile_upselling\Services\v2_0\SmsBalanceRestLogic;

/**
 * Class SmsBalanceRestLogicBo.
 */
class SmsBalanceRestLogicBo extends SmsBalanceRestLogic {

  /**
   * Responds to GET requests.
   *
   * @param string $msisdn
   *   Msisdn.
   *
   * @return array
   *   The Array of buckets.
   *
   * @throws \ReflectionException
   */
  public function get($msisdn) {
    $rows = [];
    $index = 0;
    $flag = FALSE;
    $configs = $this->configBlock;

    // Get all buckets of sms and type.
    $service = \Drupal::service('oneapp_mobile_upselling.service.data_balance');
    $bucket_list = $service->getBucketsSms($msisdn);
    $bucket_list = isset($bucket_list->buckets) ? $bucket_list->buckets : $bucket_list;
    if (isset($bucket_list->noData) && $bucket_list->noData) {
      return [
        'noData' => ['value' => 'empty'],
      ];
    }
    $bucket_list_sanitized = $this->bucketSanitized($bucket_list->balances, 'SMS');
    if (count($bucket_list_sanitized) == 0) {
      return [
        'noData' => ['value' => 'empty'],
      ];
    }

    foreach ($bucket_list_sanitized as $bucket) {

      $row = [];
      $un_imited = $this->checkUnLimitedBucket($bucket);

      foreach ($configs['smsBalance']['fields'] as $id => $field) {

        $row[$id] = [
          'label' => $field['label'],
          'show' => ($field['show']) ? TRUE : FALSE,
        ];

        switch ($id) {
          case 'bucketsId':
            $row[$id]['value'] = $bucket->bucketId;
            $row[$id]['formattedValue'] = $bucket->bucketId;
            break;

          case 'friendlyName':
            $row[$id]['value'] = $bucket->description;
            $row[$id]['formattedValue'] = $bucket->description;
            if ($un_imited) {
              $row[$id]['class'] = $bucket->bucketId;
            }
            break;

          case 'remainingValue':
            if ($un_imited) {
              $row[$id]['value'] = 1;
              $row[$id]['formattedValue'] = $this->configBlock['messages']['unlimitedBucket'];
              $row[$id]['label'] = '';
            }
            else {
              if (isset($bucket->balanceAmount)) {
                $row[$id]['value'] = $bucket->balanceAmount;
                $row[$id]['formattedValue'] = $bucket->balanceAmount . ' ' . $field['description'];
              }
              else {
                $row[$id]['value'] = 0;
                $row[$id]['formattedValue'] = 0 . ' ' . $field['description'];
              }
            }
            break;

          case 'reservedAmount':
            $row[$id]['value'] = '';
            $row[$id]['formattedValue'] = '';
            $row[$id]['label'] = '';
            break;

          case 'endDateTime':
            if (!isset($bucket->expirationDate) && !$un_imited) {
              $row[$id]['label'] = '';
              $endDate_time = !empty($bucket->rolloverExpirationDate) ? $bucket->rolloverExpirationDate : '';
              $remaining_time = '';
              $formatted_value = '';
            }
            else {
              $row[$id]['label'] = '';
              $endDate_time = !empty($bucket->expirationDate) ? $bucket->expirationDate : '';
              $remaining_time = !empty($endDate_time) ? $this->utilsMobile->formatDateRegressive($endDate_time) : '';
              $formatted_value = !empty($endDate_time) ? (isset($field['prefix']) ? $field['prefix'] . " " . $remaining_time : $remaining_time) : '';
            }
            $row[$id]['show'] = !empty($endDate_time) ? (bool) $field['show'] : FALSE;
            $row[$id]['value'] = [
              'startDate' => '',
              'endDateTime' => $endDate_time,
            ];
            $row[$id]['formattedValue'] = $formatted_value;
            $flag = !empty($remaining_time) ? (!$un_imited ? TRUE : FALSE) : FALSE;
            break;
        }
      }
      if (!$flag) {
        // Flags.
        $row['unlimited'] = ['value' => $un_imited];
        $row['isActive']['value'] = $this->isActive($bucket);
        $row['showBar']['value'] = $this->showBar($row);
        $rows[$index] = $row;
        $index++;
      }
    }

    if (empty($rows)) {
      $rows[$index] = $this->getFormattedListBucketsEmpty($configs);
    }

    return ['smsBalance' => $rows];
  }

  /**
   * Remove bucket not allowed and duplicates.
   *
   * @param object $bucket_list
   *   Bucket list to be sanitized.
   * @param string $bucket_type
   *   Bucket Type.
   *   Ex: 'data', 'voice', 'sms'.
   *
   * @return array
   *   Bucket list sanitized
   */
  public function bucketSanitized($bucket_list, $bucket_type) {
    $bucket_list_sanitized = [];
    foreach ($bucket_list as $bucket) {
      // If bucket type is not allowed, we should ommit it.
      if (strpos($bucket->wallet, $bucket_type) !== FALSE) {
        $bucket->bucketId = strtolower(str_replace(' ', '-', $bucket->wallet));
        $bucket->bucketId = str_replace('.', '', $bucket->bucketId);
        $bucket_list_sanitized[$bucket->bucketId] = $bucket;
      }
    }
    return $bucket_list_sanitized;
  }

  /**
   * Check if unlimitted bucket.
   *
   * @param object $bucket
   *   Bucket object.
   *
   * @return bool
   *   Return Unlimiteed Bucket.
   */
  public function checkUnLimitedBucket($bucket) {
    return $bucket->balanceAmount === "Ilimitado";
  }

  /**
   * Implements flag isActive.
   *
   * @return bool
   *   The isActive Conditions.
   */
  public function isActive($bucket) {
    $active = TRUE;
    return $active;
  }

  /**
   * Implements flag showBar.
   *
   * @return bool
   *   The showBar Conditions.
   */
  public function showBar($row) {
    $show = !empty($row['validFor']['value']['startDate']) && !empty($row['validFor']['value']['endDateTime']) ? TRUE : FALSE;
    return $show;
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
