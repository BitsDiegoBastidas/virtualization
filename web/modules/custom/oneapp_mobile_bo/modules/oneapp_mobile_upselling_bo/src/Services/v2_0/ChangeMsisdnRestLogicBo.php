<?php

namespace Drupal\oneapp_mobile_upselling_bo\Services\v2_0;

use Drupal\oneapp\Exception\HttpException;
use Drupal\oneapp_mobile_upselling\Services\v2_0\ChangeMsisdnRestLogic;

/**
 * Class ChangeMsisdnRestLogicBo.
 */
class ChangeMsisdnRestLogicBo extends ChangeMsisdnRestLogic {

  /**
   * Accept Recharge target.
   *
   * @var bool
   */
  protected $acceptRecharge = FALSE;

  /**
   * Billing Type of target msisdn.
   *
   * @var string
   */
  protected $billingType;

  /**
   * Responds to post requests.
   *
   * @param string $msisdn
   *   Msisdn.
   * @param string $targetMsisdn
   *   Target Msisdn.
   * @param string $type
   *   Type.
   *
   * @return mixed
   *   mixed
   *
   * @throws \ReflectionException
   */
  public function getStatus($msisdn, $targetMsisdn, $type) {
    // Get info from token.
    $baL = $this->mobileUtils->getInfoTokenByMsisdn($msisdn);
    if (!$baL['subscriptionType']) {
      $baL['subscriptionType'] = $baL['billingType'];
    }
    $targetMsisdn = str_replace(' ', '', $targetMsisdn);
    $targetMsisdn = $this->mobileUtils->getFormattedMsisdn($targetMsisdn);

    try {
      // If origin Msisdn is tigo number valid.
      if ($baL['subscriptionType']) {

        switch ($type) {
          case self::PACKAGE_KEY:
            return $this->validateMsisdnForPackages($baL, $targetMsisdn, $msisdn === $targetMsisdn);

          case self::RECHARGE_KEY:
            return $this->validateMsisdnForRecharges($baL, $targetMsisdn, $msisdn === $targetMsisdn);
        }
      }
      else {
        return $this->formatErrorResponse();
      }
    }
    catch (HttpException $exception) {
      return $this->formatErrorResponse();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseToPurchaseOfPackages($billingType, $originPlanMsisdn, $msisdn) {
    if ($billingType) {
      switch ($originPlanMsisdn['subscriptionType']) {
        case self::PREPAID:
        case self::CONTROL:
        case self::HYBRID:
          if ($billingType === self::PREPAID || $billingType === self::CONTROL || $billingType === self::HYBRID) {
            return $this->formatSuccesResponse($billingType, $msisdn, [self::PREPAID, self::CONTROL], self::PACKAGE_KEY);
          }
          return $this->formatErrorResponse(self::PACKAGE_KEY);

        case self::POSTPAID:
          return $this->formatErrorResponse(self::PACKAGE_KEY);

        default:
          return $this->formatErrorResponse();
      }
    }
    else {
      return $this->formatErrorResponse();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseToRecharge($billingType, $originPlanMsisdn, $msisdn) {
    if ($billingType) {
      switch ($originPlanMsisdn['subscriptionType']) {
        case self::PREPAID:
        case self::CONTROL:
        case self::POSTPAID:
          if (($billingType === self::PREPAID ||
            $billingType === self::CONTROL ||
            $billingType === self::HYBRID) &&
            $this->acceptRecharge
          ) {
            return $this->formatSuccesResponse($billingType, $msisdn, [self::PREPAID, self::CONTROL], self::RECHARGE_KEY);
          }
          return $this->formatErrorResponse(self::RECHARGE_KEY);

        case self::HYBRID:
          if ($billingType === self::CONTROL || $billingType === self::HYBRID || $billingType === self::PREPAID) {
            return $this->formatSuccesResponse($billingType, $msisdn, [self::PREPAID, self::CONTROL], self::RECHARGE_KEY);
          }
          return $this->formatErrorResponse(self::RECHARGE_KEY);

        default:
          return $this->formatErrorResponse();
      }
    }
    else {
      return $this->formatErrorResponse();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateMsisdnForPackages($originPlanMsisdn, $targetMsisdn, $isSame = FALSE) {
    $msisdn = $targetMsisdn;
    $billingType = $this->findBillingTypeTarget((array) $originPlanMsisdn, $targetMsisdn, $isSame, $msisdn);
    return $this->getResponseToPurchaseOfPackages($billingType, (array) $originPlanMsisdn, $msisdn);
  }

  /**
   * Find Billing Type of msisdn target.
   *
   * @param array $originPlanMsisdn
   *   Billing type of the origin msisdn.
   * @param string $targetMsisdn
   *   Target msisdn.
   * @param bool $isSame
   *   If same target and msisdn.
   * @param string $msisdn
   *   Msisdn.
   *
   * @return string|array
   *   Return billing type or array formatted error.
   *
   * @throws \ReflectionException
   */
  protected function findBillingTypeTarget(array $originPlanMsisdn, $targetMsisdn, $isSame, &$msisdn) {
    if ($isSame) {
      return $originPlanMsisdn['subscriptionType'];
    }
    else {
      try {
        // Find info by target msisdn.
        $this->getAccountInfo($targetMsisdn);
        return $this->billingType;
      }
      catch (HttpException $e) {
        return FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateMsisdnForRecharges($originPlanMsisdn, $targetMsisdn, $isSame = FALSE) {
    $msisdn = $targetMsisdn;
    $billingType = $this->findBillingTypeTarget($originPlanMsisdn, $targetMsisdn, $isSame, $msisdn);
    return $this->getResponseToRecharge($billingType, $originPlanMsisdn, $msisdn);
  }

  /**
   * Get info by msisdn.
   *
   * @param string $msisdn
   *   Msisdn.
   *
   * @throws \ReflectionException
   */
  protected function getAccountInfo($msisdn) {
    try {
      $accountInfo = $this->manager
        ->load('oneapp_master_accounts_record_endpoint')
        ->setHeaders([])
        ->setQuery([])
        ->setParams(['msisdn' => $msisdn])
        ->sendRequest();

      foreach ($accountInfo->customerAccountList as $customerAccountList) {
        foreach ($customerAccountList->accountList as $accountList) {
          foreach ($accountList->subscriptionList as $subscriptionList) {
            if (isset($subscriptionList->msisdnList) && $subscriptionList->msisdnList != []) {
              foreach ($subscriptionList->msisdnList as $msisdnList) {
                if ($msisdnList->msisdn == $msisdn) {
                  $this->billingType = $subscriptionList->subscriptionType;
                  $acceptRecharge = $msisdnList->lifecycle->isActive;
                  break;
                }
              }
            }
          }
        }
      }

      if ($acceptRecharge) {
        $this->acceptRecharge = TRUE;
      }
    }
    catch (HttpException $exception) {
      $message = $this->configBlock['messages']['number_error'];
      $reflectedObject = new \ReflectionClass(get_class($exception));
      $property = $reflectedObject->getProperty('message');
      $property->setAccessible(TRUE);
      $property->setValue($exception, $message);
      $property->setAccessible(FALSE);

      throw $exception;
    }
  }

}
