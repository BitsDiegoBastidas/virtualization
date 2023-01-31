<?php

namespace Drupal\oneapp_mobile_bo\Services;

use Drupal\oneapp_mobile\Services\UtilsService;

/**
 * Class VoiceBalanceRestLogicBo.
 */
class UtilsServiceBo extends UtilsService {

  /**
   * Remove bucket not allowed and duplicates.
   *
   * @param array $bucketList
   *   Array of Bucket.
   * @param array $bucketTypes
   *   Array of Bucket Types.
   *   Ex: 'data', 'voice', 'sms'.
   *
   * @return array
   *   Bucket list sanitized
   */
  public function bucketSanitized(array $bucketList, array $bucketTypes) {
    $bucketListSanitized = [];
    foreach ($bucketList as $bucket) {
      // If bucket type is not allowed, we should omit it.
      if (!in_array($bucket->unit, $bucketTypes)) {
        continue;
      }
      $bucketListSanitized[] = $bucket;
    }
    return $bucketListSanitized;
  }

  /**
   * Remove bucket not allowed and duplicates.
   *
   * @param array $bucketList
   *   Array of Bucket.
   * @param array $bucketTypes
   *   Array of Bucket Types.
   *   Ex: 'data', 'voice', 'sms'.
   *
   * @return array
   *   Bucket list sanitized
   */
  public function bucketIlimited(array $bucketList, array $bucketTypes) {
    $bucketListSanitized = [];
    foreach ($bucketList as $bucket) {
      // If bucket type is not allowed, we should omit it.
      if (!in_array($bucket->wallet, $bucketTypes)) {
        continue;
      }
      $bucketListSanitized[] = $bucket;
    }
    return $bucketListSanitized;
  }

  /**
   * Get if msisdn is prepaid or not.
   *
   * @param string $msisdn
   *   Msisdn.
   *
   * @return bool
   *   Reurn true or false.
   */
  public function isPrepaid($msisdn) {
    $header = [
      'Cache-Control' => 'no-cache',
      'bypass-cache' => 'true',
    ];
    try {
      $info = \Drupal::service('oneapp_endpoint.manager')
        ->load('oneapp_master_accounts_record_endpoint')
        ->setParams(['msisdn' => $msisdn])
        ->setHeaders($header)
        ->setQuery([])
        ->sendRequest();
    }
    catch (\Exception $e) {
      // TODO tratar esta exception.
      return [];
    }
    if (is_object($info) && isset($info->customerAccountList[0]->accountList[0]->subscriptionList[0]->subscriptionType)) {
      $billingType = $info->customerAccountList[0]->accountList[0]->subscriptionList[0]->subscriptionType;
    }

    if (!isset($billingType)) {
      $billingType = 'prepaid';
    }
    return strtolower($billingType) === 'prepaid';
  }

  /**
   * Determines
   * @param string|null $msisdn
   *
   * @return void
   */
  public function isQvantel($msisdn): bool {
    $is_qvantel = FALSE;
    $info = json_decode(json_encode($this->getInfoMasterAccount($msisdn)), TRUE);

    if (isset($info['customer']['sourceSystemId'])) {
      $is_qvantel = str_replace(' ', '', strtolower($info['customer']['sourceSystemId'])) == 'qvantel';
    }

    return $is_qvantel;
  }
}
