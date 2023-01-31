<?php

namespace Drupal\oneapp_mobile_upselling_bo\Services\v2_0;

use Drupal\oneapp_mobile_upselling\Services\v2_0\DataBalanceRestLogic;

/**
 * Class DataBalanceRestLogicBo.
 */
class DataBalanceRestLogicBo extends DataBalanceRestLogic {

  const BUCKET_TYPE_ALLOWED = 'MB';
  const UNIT_BASE = 'MB';

  /**
   * Responds to GET requests.
   *
   * @param string $msisdn
   *   Msisdn.
   *
   * @return array
   *   The response to summary configurations.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   * @throws \Exception
   *   Throws exception expected.
   */
  public function get($msisdn, $request = NULL) {
    $utils_mobile = \Drupal::service('oneapp.mobile.utils');
    $config = $this->configBlock['summary']['fields'];
    $bucket_list = $this->getBucketList($msisdn);
    if (isset($bucket_list->noData) && $bucket_list->noData) {
      return [
        'noData' => ['value' => 'empty'],
      ];
    }
    $bucket_list_sanitized = $this->bucketSanitized($bucket_list->balances, self::BUCKET_TYPE_ALLOWED);
    if (count($bucket_list_sanitized) == 0) {
      return [
        'noData' => ['value' => 'empty'],
      ];
    }

    $summary_remaining_value = $this->summaryRemainingValue($bucket_list_sanitized);
    $summary_reserved_amount = $this->summaryReservedAmount($bucket_list_sanitized);
    $summary_end_date_value = $this->summaryEndDateValue($bucket_list_sanitized);

    return [
      'summaryRemainingValue' => [
        'value' => $summary_remaining_value,
        'formattedValue' => $this->formatData($summary_remaining_value),
        'label' => $config['remainingValue']['label'],
        'show'  => (bool) $config['remainingValue']['show'],
      ],
      'summaryReservedAmount' => [
        'value' => $summary_reserved_amount,
        'formattedValue' => $this->formatData($summary_reserved_amount),
        'label' => $config['reservedAmount']['label'],
        'show'  => (bool) $config['reservedAmount']['show'],
      ],
      'summaryUsedValue' => [
        'value' => '',
        'formattedValue' => '',
        'label' => $config['usedValue']['label'],
        'show'  => (bool) $config['usedValue']['show'],
      ],
      'summaryDateValue' => [
        'value' => $summary_end_date_value,
        'formattedValue' => $utils_mobile->formatDateRegressive($summary_end_date_value),
        'label' => $config['dateValue']['label'],
        'show' => (bool) $config['dateValue']['show'],
      ],
    ];
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
      $not_consumed = strpos(strtolower($bucket->wallet), 'no consumido') !== FALSE ? TRUE : FALSE;
      if ($bucket->unit == $bucket_type && !$not_consumed) {
        $bucket->bucketId = strtolower(str_replace(' ', '-', $bucket->wallet));
        $bucket_list_sanitized[$bucket->bucketId] = $bucket;
      }
    }
    return $bucket_list_sanitized;
  }

  /**
   * Summatory to remaining value.
   *
   * @param array $bucket_list
   *   Bucket list to make summatory.
   *
   * @return int
   *   Summatory
   */
  public function summaryRemainingValue(array $bucket_list) {
    $summary_remaining_value = 0;

    foreach ($bucket_list as $bucket) {
      $summary_remaining_value += $bucket->balanceAmount;
    }

    return $summary_remaining_value;
  }

  /**
   * Summatory to reserved Amount.
   *
   * @param array $bucket_list
   *   Bucket list to make summatory.
   *
   * @return int
   *   Summatory
   */
  public function summaryReservedAmount(array $bucket_list) {
    $summary_reserved_amount = 0;

    foreach ($bucket_list as $bucket) {
      $amount = isset($bucket->reservedAmount) ? $bucket->reservedAmount : $bucket->balanceAmount;
      $summary_reserved_amount += $amount;
    }

    return $summary_reserved_amount;
  }

  /**
   * Give format to value.
   *
   * @param string $value
   *   Value to be formatted.
   *
   * @return string
   *   Value formatted
   */
  public function formatData($value) {
    $format_value = number_format($value, 0, ',', '.');
    return strtoupper($format_value . ' ' . self::BUCKET_TYPE_ALLOWED);
  }

  /**
   * Summary  end Date.
   *
   * @param array $bucket_list
   *   Bucket find endDate.
   *
   * @return int
   *   Summatory
   */
  public function summaryEndDateValue(array $bucket_list) {
    $summary_end_date_value = 0;
    foreach ($bucket_list as $bucket) {
      $end_date = isset($bucket->expirationDate) ? $bucket->expirationDate :
        (isset($bucket->rolloverExpirationDate) ? $bucket->rolloverExpirationDate : 0);
      if (strtotime($end_date) > strtotime($summary_end_date_value)) {
        $summary_end_date_value = $end_date;
      }
    }
    return $summary_end_date_value;
  }

  /**
   * Return block configuration
   * @return array
   */
  public function getWebcomponentConfiguration() {
    $configs = [];
    $min = $this->configBlock["summary"]["webcomponent"]["supportedVersions"]["min"];
    $max = $this->configBlock["summary"]["webcomponent"]["supportedVersions"]["max"];
    $show = $this->configBlock["summary"]["webcomponent"]["show"];
    $this->configBlock["summary"]["webcomponent"]["supportedVersions"]["min"] = empty($min) ? '1.0.0' : $min;
    $this->configBlock["summary"]["webcomponent"]["supportedVersions"]["max"] = empty($max) ? NULL : $max;
    $this->configBlock["summary"]["webcomponent"]["show"] = boolval($show);
    $configs['showDetailWebComponent'] = $this->configBlock["summary"]["webcomponent"];
    return $configs;
  }
}
