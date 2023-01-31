<?php

namespace Drupal\oneapp_mobile_upselling_bo\Services\v2_0;

use Drupal\oneapp_mobile_upselling\Services\v2_0\DataBalanceDetailRestLogic;

/**
 * Class DataBalanceDetailRestLogicBo.
 */
class DataBalanceDetailRestLogicBo extends DataBalanceDetailRestLogic {

  const BUCKET_TYPE_ALLOWED = 'MB';

  /**
   * Responds to GET requests.
   *
   * @param string $msisdn
   *   Msisdn.
   *
   * @return array
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   * @throws \Exception
   *   Throws exception expected.
   */
  public function get($msisdn) {
    $rows = [];
    $count = 0;
    $config = $this->configBlock;
    $flag = FALSE;

    // Get all buckets of data type.
    $bucketList = $this->getBucketList($msisdn);
    $bucketListSanitized = $this->bucketSanitized($bucketList->balances, self::BUCKET_TYPE_ALLOWED);

    if (!empty($bucketListSanitized)) {
      foreach ($bucketListSanitized as $bucket) {
        $row = [];
        $unLimited = $this->checkUnLimitedBucket($bucket);

        foreach ($config['detail']['fields'] as $field_name => $field) {
          switch ($field_name) {
            case 'bucketsId':
              $row[$field_name]['label'] = $field['label'];
              $row[$field_name]['show'] = (bool) $field['show'];
              $row[$field_name]['value'] = $bucket->bucketId;
              $row[$field_name]['formattedValue'] = $bucket->bucketId;
              break;

            case 'name':
              $row[$field_name]['label'] = $field['label'];
              $row[$field_name]['show'] = (bool) $field['show'];
              $row[$field_name]['class'] = $bucket->bucketId;
              $row[$field_name]['value'] = $bucket->wallet;
              $row[$field_name]['formattedValue'] = strpos(strtoupper($bucket->wallet), 'WHATSAPP') !== FALSE ? t('Whatsapp') : $bucket->wallet;
              break;

            case 'reservedAmount':
              $row[$field_name]['label'] = $field['label'];
              $row[$field_name]['show'] = (bool) $field['show'];
              $row[$field_name]['value'] = '';
              $row[$field_name]['formattedValue'] = '';
              break;

            case 'remainingValue':
              $row[$field_name] = $this->getAmountObject($field['label'], $field['show'], $bucket, self::KEY_REMAINING);
              break;

            case 'validFor':
              $notConsumed = $config['detail']['fields']['notConsumed'];
              if (!isset($bucket->expirationDate) && !$unLimited) {
                $row[$field_name]['label'] = '';
                $endDateTime = !empty($bucket->rolloverExpirationDate) ? $bucket->rolloverExpirationDate : 0;
                $remainingTime = ($endDateTime == 0) ? '' : $this->utils->formatDate(strtotime($endDateTime), 'fecha_y_hora_a_m_p_m_');
                $formattedValue = isset($notConsumed['label']) ? $notConsumed['label'] . " " . $remainingTime : $remainingTime;
              }
              else {
                $row[$field_name]['label'] = '';
                $endDateTime = !empty($bucket->expirationDate) ? $bucket->expirationDate : '';
                $remainingTime = !empty($endDateTime) ? $this->utilsMobile->formatDateRegressive($endDateTime) : '';
                $formattedValue = isset($field['label']) && !empty($endDateTime) ? $field['label'] . " " . $remainingTime : $remainingTime;
              }
              $row[$field_name]['show'] = (bool) $field['show'];
              $row[$field_name]['value'] = [
                'startDate' => '',
                'endDateTime' => $endDateTime,
              ];
              if ($this->checkFreeBucket($bucket)) {
                $row[$field_name]['formattedValue'] = $bucket->description ? $bucket->description : '';
              }
              else {
                $row[$field_name]['formattedValue'] = $formattedValue;
                $flag = !$remainingTime ? (!$unLimited ? TRUE : FALSE) : FALSE;
              }
              break;
          }
        }
        if (!$flag) {
          // Flags.
          $row['unlimited'] = ['value' => $unLimited];
          $row['isActive']['value'] = $this->isActive($bucket);
          $row['showBar']['value'] = $this->showBar($row);
          $rows[$count] = $row;
          $count++;
        }
      }
    }
    $actives = array_filter($rows, function ($v) {
      return $v['isActive']['value'];
    });
    $onHold = array_filter($rows, function ($v) {
      return !$v['isActive']['value'];
    });
    $rows = array_merge($actives, $onHold);
    $data = ['bucketsList' => $rows];
    if (empty($rows)) {
      $data['noData']['value'] = 'empty';
    }
    return $data;
  }

  /**
   * Remove bucket not allowed and duplicates.
   *
   * @param object $bucketList
   *   Bucket list to be sanitized.
   * @param string $bucketType
   *   Bucket Type.
   *   Ex: 'data', 'voice', 'sms'.
   *
   * @return array
   *   Bucket list sanitized
   */
  public function bucketSanitized($bucketList, $bucketType) {
    $bucketListSanitized = [];
    foreach ($bucketList as $bucket) {
      // If bucket type is not allowed, we should ommit it.
      if ($bucket->unit == $bucketType || ($bucket->unit == "" && $bucket->balanceAmount === "Ilimitado")) {
        $bucket->bucketId = strtolower(str_replace(' ', '-', $bucket->wallet));
        $bucket->bucketId = str_replace('.', '', $bucket->bucketId);
        $bucketListSanitized[$bucket->bucketId] = $bucket;
      }
    }
    return $bucketListSanitized;
  }

  /**
   * Implements getAmountObject.
   */
  public function getAmountObject($label, $show, $bucket, $type) {
    $bucketValue = !empty($bucket->balanceAmount) ? $bucket->balanceAmount : 0;
    if ($this->checkFreeBucket($bucket)) {
      $formattedValue = $type === self::KEY_REMAINING ? $this->configBlock["detail"]["messages"]["free"] : '';
      return [
        'label' => '',
        'show' => TRUE,
        'value' => 1,
        'formattedValue' => $formattedValue,
      ];
    }
    elseif ($this->checkUnLimitedBucket($bucket)) {
      $formattedValue = $type === self::KEY_REMAINING ? $this->configBlock["detail"]["messages"]["unlimited"] : '';
      return [
        'label' => '',
        'show' => TRUE,
        'value' => 1,
        'formattedValue' => $formattedValue,
      ];
    }

    return [
      'label' => $label,
      'show' => (bool) $show,
      'value' => $bucketValue,
      'formattedValue' => $this->formatData($bucketValue),
    ];
  }

  /**
   * Check if bucket is free.
   *
   * @param object $bucket
   *   The bucket object.
   *
   * @return bool
   *   Return if bucket contains into list bucket ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function checkFreeBucket($bucket) {
    return $bucket->balanceAmount === -2;
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
   * Give format to value.
   *
   * @param string $value
   *   Value to be formatted.
   *
   * @return string
   *   Value formatted
   */
  public function formatData($value) {
    $formatValue = number_format($value, 0, ',', '.');
    return strtoupper($formatValue . ' ' . self::BUCKET_TYPE_ALLOWED);
  }

  /**
   * Implements flag isActive.
   *
   * @return bool
   *   The isActive Conditions.
   */
  public function isActive($bucket) {
    $active = isset($bucket->expirationDate) || $this->checkUnLimitedBucket($bucket) ? TRUE : FALSE;
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
   * Return block configuration
   * @return array
   */
  public function getWebcomponentConfiguration() {
    $configs = [];
    $min = $this->configBlock["detail"]["webcomponent"]["supportedVersions"]["min"];
    $max = $this->configBlock["detail"]["webcomponent"]["supportedVersions"]["max"];
    $show = $this->configBlock["detail"]["webcomponent"]["show"];
    $this->configBlock["detail"]["webcomponent"]["supportedVersions"]["min"] = empty($min) ? '1.0.0' : $min;
    $this->configBlock["detail"]["webcomponent"]["supportedVersions"]["max"] = empty($max) ? NULL : $max;
    $this->configBlock["detail"]["webcomponent"]["show"] = boolval($show);
    $configs['showDetailWebComponent'] = $this->configBlock["detail"]["webcomponent"];
    return $configs;
  }
}
