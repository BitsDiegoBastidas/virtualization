<?php

namespace Drupal\oneapp_mobile_upselling_bo\Services\v2_0;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\oneapp\Exception\HttpException;
use Drupal\oneapp_mobile_upselling\Services\v2_0\AcquiredOffersRestLogic;

/**
 * Class AcquiredOffersRestLogicBo.
 */
class AcquiredOffersRestLogicBo extends AcquiredOffersRestLogic {

  /**
   * {@inheritdoc}
   */
  protected $invoiceCharge;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $primaryNumber;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $targetNumber;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $typingErrors;

  /**
   * Responds to post requests.
   *
   * @param string $msisdn
   *   Msisdn.
   * @param string $data
   *   Data.
   *
   * @return Mixed
   *   The HTTP response object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \ReflectionException
   */
  public function post($msisdn, $data) {
    $this->invoiceCharge = $this->isInvoiceCharge($data);
    $this->loan = $this->isLoan($data);
    $this->coreBalance = ($this->loan == TRUE || $this->invoiceCharge == TRUE) ? FALSE : TRUE;

    $this->utils = \Drupal::service('oneapp.utils');
    $msisdn = str_replace(' ', '', $msisdn);
    $data['msisdn'] = $msisdn;
    $offer = NULL;
    $result = NULL;
    try {
      $target = FALSE;
      $this->primaryNumber['accountId'] = $msisdn;
      $billing_account_list = $this->mobileUtils->getInfoTokenByMsisdn($msisdn);
      if (!isset($billing_account_list['subscriptionType'])) {
        $billing_account_list['subscriptionType'] = $billing_account_list['billingType'];
      }
      $this->primaryNumber['info'] = $billing_account_list['subscriptionType'];

      if (isset($data['targetMSISDN'])) {
        $target = $data['targetMSISDN'];
      }
      elseif (isset($data['targetMsisdn'])) {
        $target = $data['targetMsisdn'];
        unset($data['targetMsisdn']);
      }
      elseif (\Drupal::request()->query->get('targetMSISDN')) {
        $target = \Drupal::request()->query->get('targetMSISDN');
      }
      elseif (\Drupal::request()->query->get('targetMsisdn')) {
        $target = \Drupal::request()->query->get('targetMsisdn');
      }

      if ($this->coreBalance) {
        if ($target) {
          $data['targetMSISDN'] = \Drupal::service('oneapp.mobile.utils')->getFormattedMsisdn($target);
          if ($msisdn != $data['targetMSISDN']) {
            $data_response['result'] = [
              'label' => $this->configBlock['config']['response']['postFailed']['title']['label'],
              'formattedValue' => $this->configBlock['config']['response']['postFailed']['message']['label'],
              'value' => FALSE,
              'show' => (bool) $this->configBlock['config']['response']['postFailed']['title']['show'],
            ];
            return [
              'data' => $data_response,
              'success' => FALSE,
            ];
          }
        }
        if (!isset($data['packageId'])) {
          $package_id = \Drupal::request()->query->get('packageId');
          if (!$package_id) {
            $data_response['result'] = [
              'label' => $this->configBlock['config']['response']['postFailed']['title']['label'],
              'formattedValue' => $this->configBlock['config']['response']['postFailed']['message']['label'],
              'value' => FALSE,
              'show' => (bool) $this->configBlock['config']['response']['postFailed']['title']['show'],
            ];
            return [
              'data' => $data_response,
              'success' => FALSE,
            ];
          }
          $data['packageId'] = $package_id;
        }
        $data['acquisitionTypeId'] = 1;
        $offer = $this->getOffer($msisdn, $data['packageId']);
        $result = $this->acquireOffers($msisdn, $data);
        $response = $this->purchaseCoreBalanceResponse($msisdn, $offer, $result);
      }
      elseif ($this->loan) {
        $loan = $this->getBalanceLoanOffers($msisdn, $data['packageId']);
        $result = $this->acquireOffers($msisdn, $data['packageId'], $data);
        $response = $this->loanResponse($msisdn, $loan, $result);
      }
      elseif ($this->invoiceCharge) {
        // it's Hybrid?
        if ($this->verifyTypeLineForInvoiceCharge($this->primaryNumber['info'])) {
          $target = ($target) ? \Drupal::service('oneapp.mobile.utils')->getFormattedMsisdn($target) : FALSE;
          $target = ($target === FALSE) ? $msisdn : $target;
          if ($msisdn == $target) {
            $data['targetMSISDN'] = $msisdn;
            if ($this->verifyInvoiceCharge()) {
              $result = $this->acquireOffersInvoiceCharge($msisdn, $data);
            }
          }
          else {
            $data['targetMSISDN'] = $target;
            $this->targetNumber['accountId'] = $target;
            $info = $this->getMasterAccountRecord($target);
            $this->targetNumber['info'] = ($info != FALSE) ? $this->getTypeLine($info, $this->targetNumber['accountId']) : FALSE;

            // it's Hybrid?
            if ($this->verifyTypeLineForInvoiceCharge($this->targetNumber['info'])) {
              if ($this->verifyInvoiceCharge($this->targetNumber['accountId'])) {
                $result = $this->acquireOffersInvoiceCharge($msisdn, $data);
              }
            }
          }
        }
        $response = $this->purchaseInvoiceChargeResponse($data, $result);
      }

      return [
        'data' => $response['data'],
        'success' => $response['value'],
      ];
    }
    catch (HttpException $exception) {
      if ($this->loan) {
        $this->validateExceptionLoan($exception);
        return $this->loanResponse($msisdn, FALSE, $result);
      }
      if ($this->invoiceCharge) {
        $this->validateExceptionInvoiceCharge($exception);
        $this->errorsInvoiceCharge();
        return $this->purchaseInvoiceChargeResponse($data, $result);
      }
      else {
        if ($this->coreBalance) {
          $this->validateExceptionCoreBalance($exception);
          return $this->purchaseCoreBalanceResponse($data, $offer, $result);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeLine($account_info, $msisdn) {
    foreach ($account_info->customerAccountList as $customerAccountList) {
      foreach ($customerAccountList->accountList as $accountList) {
        foreach ($accountList->subscriptionList as $subscriptionList) {
          if (isset($subscriptionList->msisdnList) && $subscriptionList->msisdnList != []) {
            foreach ($subscriptionList->msisdnList as $msisdnList) {
              if ($msisdnList->msisdn == $msisdn) {
                return $subscriptionList->subscriptionType;
              }
            }
          }
        }
      }
    }
    $this->tigoInvalido = TRUE;
    return FALSE;
  }

  /**
   * Validate Exception Invoice Charge.
   *
   * @param mixed $exception
   *   Exception occurred.
   */
  public function validateExceptionInvoiceCharge($exception) {
    if ($exception->getStatusCode() == 401) {
      $this->typingErrors['value'] = 'verificationCode';
    }
    elseif ($exception->getStatusCode() == 404) {
      $this->typingErrors['value'] = 'msisdnInvalid';
    }
    elseif ($exception->getStatusCode() == 400) {
      $this->typingErrors['value'] = 'msisdnInvalidForRecharge';
    }
    elseif ($exception->getStatusCode() == 403) {
      $this->typingErrors['value'] = 'limitInvalidForRecharge';
    }
  }

  /**
   * Validate Exception Core Balance.
   *
   * @param mixed $exception
   *   Exception occurred.
   */
  public function validateExceptionCoreBalance($exception) {
    if ($exception->getStatusCode() == 403) {
      $this->typingErrors['value'] = 'limitInvalidForCoreBalance';
      $this->typingErrors['message'] = $exception->getMessage();
    }
  }

    /**
   * Validate Exception Loan.
   *
   * @param mixed $exception
   *   Exception occurred.
   */
  public function validateExceptionLoan($exception) {
    if ($exception->getStatusCode() == 403) {
      $this->typingErrors['value'] = 'limitInvalidForLoan';
      $this->typingErrors['message'] = $exception->getMessage();
    }
  }

  /**
   * Get response formatted by purchase.
   *
   * @param string $msisdn
   *   Msisdn of the user.
   * @param mixed $offer
   *   Offer from api gee.
   * @param mixed $result
   *   Result of the purchase action.
   *
   * @return array
   *   Array formatted.
   */
  protected function purchaseCoreBalanceResponse($msisdn, $offer, $result) {
    $success = (isset($result->status) && $result->status === "OK") ? TRUE : FALSE;
    $post_success = $this->configBlock['config']['response']['postSuccess'];
    if ($success) {
      $message = ($this->verifyPackageId($offer['offerId'])) ? $result->responseMessage
        : $this->configBlock['config']['response']['postSuccess']['message']['label'];
      $data_response['result'] = [
        'label' => $this->configBlock['config']['response']['postSuccess']['title']['label'],
        'formattedValue' => $message,
        'value' => TRUE,
        'show' => (bool) $this->configBlock['config']['response']['postSuccess']['title']['show'],
      ];
      $data_response['transactionDetails']['title'] = [
        'label' => $post_success['transactionDetailsTitle']['value'],
        'show' => (bool) $post_success['transactionDetailsTitle']['show'],
      ];
      $data_response['transactionDetails']['orderId'] = [
        'label' => $post_success['transactionDetailsId']['value'],
        'value' => $result->transactionId,
        'formattedValue' => $result->transactionId,
        'show' => (bool) $post_success['transactionDetailsId']['show'],
      ];
      $data_response['transactionDetails']['detail'] = [
        'label' => $post_success['transactionDetailsDetail']['value'],
        'formattedValue' => $offer['description'],
        'show' => (bool) $post_success['transactionDetailsDetail']['show'],
      ];
      $data_response['transactionDetails']['targetMSISDN'] = [
        'label' => $post_success['transactionDetailsMSISDN']['value'],
        'value' => $msisdn,
        'formattedValue' => $msisdn,
        'show' => (bool) $post_success['transactionDetailsMSISDN']['show'],
      ];
      $formatted_validity = $this->validityToExpirationDate($offer, $this->configBlock);
      $data_response['transactionDetails']['validity'] = [
        'label' => $post_success['transactionDetailsValidity']['value'],
        'value' => $formatted_validity,
        'formattedValue' => $formatted_validity,
        'show' => (bool) $post_success['transactionDetailsValidity']['show'],
      ];
      $formatted_price = $this->utils->formatCurrency($offer['cost'][0]['amount'], $offer['currency'], TRUE);
      $data_response['transactionDetails']['price'] = [
        'label' => $post_success['transactionDetailsPrice']['value'],
        'value' => $offer['cost'][0]['amount'],
        'formattedValue' => $formatted_price,
        'show' => (bool) $post_success['transactionDetailsPrice']['show'],
      ];
      $data_response['paymentMethod'] = [
        'label' => $this->configBlock['config']['response']['postSuccess']['paymentMethod']['label'],
        'formattedValue' => $this->configBlock['config']['response']['postSuccess']['paymentMethod']['value'],
        'value' => $this->configBlock['config']['response']['postSuccess']['paymentMethod']['value'],
        'show' => $this->utils->formatBoolean($this->configBlock['config']['response']['postSuccess']['paymentMethod']['show']),
      ];
      // Get configuration ids:
      // Explode them into array.
      // Verify if offerId is into array.
      $package_id = $offer['offerId'];
      $ids = str_replace(' ', '', $this->configBlock['config']['actions']['favoriteConfigure']['ids']);
      $ids = explode(',', $ids);
      if ($package_id && in_array($package_id, $ids)) {
        $data_response['favoriteConfigure'] = [
          'label' => $this->configBlock['config']['actions']['favoriteConfigure']['label'],
          'url' => str_replace('{msisdn}', $msisdn, $this->configBlock['config']['actions']['favoriteConfigure']['url']),
        ];
        $data_response['result']['formattedValue'] = $this->configBlock['config']['actions']['favoriteConfigure']['message'];
      }
    }
    else {
      $error_text = isset($this->typingErrors['message']) ? $this->typingErrors['message']
        : $this->configBlock['config']['response']['postFailed']['message']['label'];
      if (isset($this->typingErrors['value']) && $this->typingErrors['value'] == 'limitInvalidForCoreBalance') {
        $error_text = $this->typingErrors['message'];
      }
      $data_response['result'] = [
        'label' => $this->configBlock['config']['response']['postFailed']['title']['label'],
        'formattedValue' => $error_text,
        'value' => $success,
        'show' => (bool) $this->configBlock['config']['response']['postFailed']['title']['show'],
      ];
    }

    return [
      'data' => $data_response,
      'value' => $success,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function purchaseInvoiceChargeResponse($data, $result) {
    $success = (isset($result->state) && $result->state === "OK") ? TRUE : FALSE;
    $post_success = $this->configBlock['config']['response']['postSuccess'];
    if ($success) {
      $message = $this->configBlock['config']['response']['postSuccess']['message']['label'];
      $data_response['result'] = [
        'label' => $this->configBlock['config']['response']['postSuccess']['title']['rechargeLabel'],
        'formattedValue' => $message,
        'value' => TRUE,
        'show' => (bool) $this->configBlock['config']['response']['postSuccess']['title']['show'],
      ];
      $data_response['transactionDetails']['title'] = [
        'label' => $post_success['transactionDetailsTitle']['value'],
        'show' => (bool) $post_success['transactionDetailsTitle']['show'],
      ];
      $data_response['transactionDetails']['orderId'] = [
        'label' => $post_success['transactionDetailsId']['value'],
        'value' => '',
        'formattedValue' => '',
        'show' => FALSE,
      ];
      $data_response['transactionDetails']['detail'] = [
        'label' => $post_success['transactionDetailsDetail']['value'],
        'formattedValue' => $post_success['transactionDetailsDetail']['rechargevalue'],
        'show' => (bool) $post_success['transactionDetailsDetail']['show'],
      ];
      $data_response['transactionDetails']['targetMSISDN'] = [
        'label' => $post_success['transactionDetailsMSISDN']['value'],
        'value' => $data['targetMSISDN'],
        'formattedValue' => $data['targetMSISDN'],
        'show' => (bool) $post_success['transactionDetailsMSISDN']['show'],
      ];
      $data_response['transactionDetails']['validity'] = [
        'label' => $post_success['transactionDetailsValidity']['value'],
        'value' => '',
        'formattedValue' => '',
        'show' => FALSE,
      ];
      $formatted_price = $this->utils->formatCurrency($data['amount'], TRUE, TRUE);
      $data_response['transactionDetails']['price'] = [
        'label' => $post_success['transactionDetailsPrice']['value'],
        'value' => $data['amount'],
        'formattedValue' => $formatted_price,
        'show' => (bool) $post_success['transactionDetailsPrice']['show'],
      ];
      $data_response['paymentMethod'] = [
        'label' => $this->configBlock['config']['response']['postSuccess']['paymentMethod']['label'],
        'formattedValue' => $this->configBlock['config']['response']['postSuccess']['paymentMethod']['invoiceCharge'],
        'value' => $this->configBlock['config']['response']['postSuccess']['paymentMethod']['invoiceCharge'],
        'show' => $this->utils->formatBoolean($this->configBlock['config']['response']['postSuccess']['paymentMethod']['show']),
      ];
    }
    else {
      $error_text = isset($this->typingErrors['message']) ? $this->typingErrors['message']
        : $this->configBlock['config']['response']['postFailed']['message']['value'];
      $data_response['result'] = [
        'label' => $this->configBlock['config']['response']['postFailed']['title']['value'],
        'formattedValue' => $error_text,
        'value' => $success,
        'show' => (bool) $this->configBlock['config']['response']['postFailed']['title']['show'],
      ];
    }
    return [
      'data' => $data_response,
      'value' => $success,
    ];
  }

  /**
   * Get response formatted by loan.
   *
   * @param string $msisdn
   *   Msisdn of the user.
   * @param mixed $loan
   *   Offer loan.
   * @param mixed $result
   *   Result of the loan action.
   *
   * @return array
   *   Array formatted.
   */
  protected function loanResponse($msisdn, $loan, $result) {
    $success = $this->checkResponseSuccessOrError($result);
    $post_success = $this->configBlock['config']['response']['postLoanSuccess'];
    // If was success.
    if ($success) {
      $message = $this->configBlock['config']['response']['postLoanSuccess']['message']['label'];
      $data_response['result'] = [
        'label' => $this->configBlock['config']['response']['postLoanSuccess']['title']['label'],
        'formattedValue' => $message,
        'value' => TRUE,
        'show' => (bool) $this->configBlock['config']['response']['postLoanSuccess']['title']['show'],
      ];
      $data_response['transactionDetails']['title'] = [
        'label' => $post_success['transactionDetailsTitle']['value'],
        'show' => (bool) $post_success['transactionDetailsTitle']['show'],
      ];
      $data_response['transactionDetails']['orderId'] = [
        'label' => $post_success['transactionDetailsId']['value'],
        'value' => $result->loanId,
        'formattedValue' => $result->loanId,
        'show' => (bool) $post_success['transactionDetailsId']['show'],
      ];
      $data_response['transactionDetails']['detail'] = [
        'label' => $post_success['transactionDetailsDetail']['value'],
        'formattedValue' => isset($loan->productName) ? $loan->productName : "",
        'show' => (bool) $post_success['transactionDetailsDetail']['show'],
      ];
      $data_response['transactionDetails']['targetMSISDN'] = [
        'label' => $post_success['transactionDetailsMSISDN']['value'],
        'value' => $msisdn,
        'formattedValue' => $msisdn,
        'show' => (bool) $post_success['transactionDetailsMSISDN']['show'],
      ];
      // Get the validity and format it.
      $validity_number = (isset($loan->validityNumber) && $loan->validityNumber != NULL) ? $loan->validityNumber : "";
      $validity_type = (isset($loan->validityType) && $loan->validityType != NULL) ? $loan->validityType : "";
      $formatted_validity = $post_success['transactionDetailsValidity']['value'] . ' ' . $validity_number;
      if ($validity_number == 1) {
        $formatted_validity = "Hoy";
      }
      elseif ($validity_number == 2) {
        $formatted_validity = "Mañana";
      }
      $data_response['transactionDetails']['validity'] = [
        'label' => $post_success['transactionDetailsValidity']['value'],
        'value' => $validity_number,
        'formattedValue' => $formatted_validity . ' ' . $validity_type,
        'show' => (bool) $post_success['transactionDetailsValidity']['show'],
      ];
      // Price.
      $price = isset($loan->price) ? $loan->price : 0;
      $formatted_price = $this->utils->formatCurrency($price, TRUE, TRUE);
      $value = [
        'amount' => isset($loan->price) ? $loan->price : "",
        'currencyId' => isset($loan->price) ? $this->utils->getCurrencyCode(FALSE) : "",
      ];
      $data_response['transactionDetails']['price'] = [
        'label' => $post_success['transactionDetailsPrice']['value'],
        'value' => [$value],
        'formattedValue' => isset($loan->price) ? $formatted_price : "",
        'show' => (bool) $post_success['transactionDetailsPrice']['show'],
      ];
      $fee = isset($loan->lendingFee) ? $loan->lendingFee : 0;
      $formatted_lending_fee = $this->utils->formatCurrency($fee, TRUE, TRUE);
      $data_response['transactionDetails']['fee'] = [
        'label' => $post_success['transactionDetailsFee']['value'],
        'value' => isset($loan->lendingFee) ? $loan->lendingFee : "",
        'formattedValue' => isset($loan->lendingFee) ? $formatted_lending_fee : "",
        'show' => (bool) $post_success['transactionDetailsFee']['show'],
      ];
      $data_response['paymentMethod'] = [
        'label' => $this->configBlock['config']['response']['postLoanSuccess']['paymentMethod']['label'],
        'value' => $this->configBlock['config']['response']['postLoanSuccess']['paymentMethod']['value'],
        'formattedValue' => $this->configBlock['config']['response']['postLoanSuccess']['paymentMethod']['value'],
        'show' => $this->utils->formatBoolean($this->configBlock['config']['response']['postLoanSuccess']['paymentMethod']['show']),
      ];
    }
    // Else was fail.
    else {
      $message = $this->configBlock['config']['response']['postLoanFailed']['message']['label'];
      if (isset($this->typingErrors['value']) && $this->typingErrors['value'] == 'limitInvalidForLoan') {
        $message = $this->typingErrors['message'];
      }
      $data_response['result'] = [
        'label' => $this->configBlock['config']['response']['postLoanFailed']['title']['label'],
        'formattedValue' => $message,
        'value' => FALSE,
        'show' => (bool) $this->configBlock['config']['response']['postLoanFailed']['title']['show'],
      ];
    }
    return [
      'data' => $data_response,
      'value' => $success,
    ];
  }

  /**
   * Get details by offer.
   *
   * @param string $msisdn
   *   Msisdn value.
   * @param string $package_id
   *   Package Id.
   *
   * @return array
   *   Object with information of the offer.
   *
   * @throws \Drupal\oneapp\Exception\HttpException
   * @throws \ReflectionException
   */
  protected function getOffer($msisdn, $package_id) {
    return \Drupal::service('oneapp_mobile_upselling.v2_0.offer_details_rest_logic')->get($msisdn, $package_id);
  }

  /**
   * Implements acquireOffers.
   *
   * @param string $msisdn
   *   Msisdn.
   * @param mixed $data
   *   Data send as body.
   *
   * @return object
   *   Data object.
   *
   * @throws \ReflectionException
   */
  protected function acquireOffers($msisdn, $data, $query = NULL, $data2 = NULL) {
    try {
      if ($this->loan) {
        $params = [
          'msisdn' => $msisdn,
          'productId' => $data,
        ];
        return $this->manager
          ->load('oneapp_mobile_acquired_lending_v2_0_scoring_endpoint')
          ->setHeaders(['Content-Type' => 'application/json'])
          ->setQuery([])
          ->setParams($params)
          ->sendRequest();
      }
      $params = [
        'msisdn' => $msisdn,
        'packageId' => $data['packageId'],
        'acquisitionTypeId' => $data['acquisitionTypeId'],
      ];
      return $this->manager
        ->load('oneapp_mobile_upselling_v2_0_acquired_offers_endpoint_no_bug_mapping')
        ->setHeaders(['Content-Type' => 'application/json'])
        ->setQuery([])
        ->setParams($params)
        ->setBody($data)
        ->sendRequest();
    }
    catch (HttpException $exception) {
      if ($this->verifyPackageId($data['packageId'])) {
        $this->typingErrors['message'] = $exception->getMessage();
      }
      throw $exception;
    }
  }

  /**
   * Implements acquireOffers.
   *
   * @param string $msisdn
   *   Msisdn.
   * @param mixed $data
   *   Data send as body.
   *
   * @return object
   *   Data object.
   *
   * @throws \ReflectionException
   */
  protected function acquireOffersInvoiceCharge($msisdn, $data) {
    try {
      if ($this->invoiceCharge) {
        $body = [
          'amount' => $data['amount'],
          'targetMsisdn' => $data['targetMSISDN'],
          'verificationCode' => $data['verificationCode'],
        ];
        return $this->manager
          ->load('oneapp_mobile_upselling_v2_0_balance_management_topup_endpoint')
          ->setHeaders(['Content-Type' => 'application/json'])
          ->setQuery([])
          ->setParams(['msisdn' => $msisdn])
          ->setBody($body)
          ->sendRequest();
      }
    }
    catch (HttpException $exception) {
      $this->throwException($exception);
    }
  }

  /**
   * Check if success result or failed.
   *
   * @param string $result
   *   Result of the operation loan.
   *
   * @return bool
   *   Return true or false.
   */
  protected function checkResponseSuccessOrError($result) {
    return isset($result->loanId) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function errorsInvoiceCharge() {
    switch ($this->typingErrors['value']) {
      case 'msisdnInvalid':
        if ($this->configBlock['config']['messages']['number_error']['show']) {
          $this->typingErrors['message'] = $this->configBlock['config']['messages']['number_error']['label'];
        }
        break;

      case 'typeAccountInvalid':
        if ($this->configBlock['config']['messages']['typeAccountInvalid']['show']) {
          $this->typingErrors['message'] = $this->configBlock['config']['messages']['typeAccountInvalid']['label'];
        }
        break;

      case 'hasBillsToPay':
        if ($this->configBlock['config']['messages']['hasBillsToPay']['show']) {
          $this->typingErrors['message'] = $this->configBlock['config']['messages']['hasBillsToPay']['label'];
        }
        break;

      case 'isB2B':
        if ($this->configBlock['config']['messages']['isB2B']['show']) {
          $this->typingErrors['message'] = $this->configBlock['config']['messages']['isB2B']['label'];
        }
        break;

      case 'lineStatusSuspend':
        if ($this->configBlock['config']['messages']['lineStatusSuspend']['show']) {
          $this->typingErrors['message'] = $this->configBlock['config']['messages']['lineStatusSuspend']['label'];
        }
        break;

      case 'verificationCode':
        if ($this->configBlock['config']['messages']['verificationCode']['show']) {
          $this->typingErrors['message'] = $this->configBlock['config']['messages']['verificationCode']['label'];
        }
        break;

      case 'msisdnInvalidForRecharge':
        if ($this->configBlock['config']['messages']['msisdnInvalidForRecharge']['show']) {
          $this->typingErrors['message'] = $this->configBlock['config']['messages']['msisdnInvalidForRecharge']['label'];
        }
        break;

      case 'limitInvalidForRecharge':
        if ($this->configBlock['config']['messages']['limitInvalidForRecharge']['show']) {
          $this->typingErrors['message'] = $this->configBlock['config']['messages']['limitInvalidForRecharge']['label'];
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function verifyStatus($msisdn) {
    try {
      $account_info = $this->getMasterAccountRecord($msisdn);
      foreach ($account_info->customerAccountList as $customerAccountList) {
        foreach ($customerAccountList->accountList as $accountList) {
          foreach ($accountList->subscriptionList as $subscriptionList) {
            if (isset($subscriptionList->msisdnList) && $subscriptionList->msisdnList != []) {
              foreach ($subscriptionList->msisdnList as $msisdnList) {
                if ($msisdnList->msisdn == $msisdn) {
                  $this->billingType = $subscriptionList->subscriptionType;
                  $status = $msisdnList->lifecycle->status;
                  break;
                }
              }
            }
          }
        }
      }

      if (strtolower($status) === 'suspend') {
        $this->typingErrors['value'] = 'lineStatusSuspend';
        $this->errorsInvoiceCharge();
        return FALSE;
      }
      return TRUE;
    }
    catch (HttpException $exception) {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function verifyTypeLineForInvoiceCharge($type) {
    // Type line must be hybrid for recharge with invoice charge..
    if ($type === 'hybrid') {
      return TRUE;
    }
    else {
      $this->typingErrors['value'] = 'typeAccountInvalid';
      $this->errorsInvoiceCharge();
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasPayment() {
    $not_allowed_invoices = intval($this->configBlock['config']['messages']['facturas_error']['label']);
    try {
      $invoices = $this->callInvoicesApi($this->primaryNumber['accountId']);
    }
    catch (HttpException $exception) {
      if ($exception->getCode() === 404) {
        return TRUE;
      }
      $this->typingErrors['value'] = 'hasBillsToPay';
      $this->errorsInvoiceCharge();
      return FALSE;
    }

    $count = 0;
    foreach ($invoices as $invoice) {
      if ($invoice->hasPayment === FALSE) {
        $count++;
      }
    }
    $has_payment = ($count >= $not_allowed_invoices) ? FALSE : TRUE;
    if ($has_payment == FALSE) {
      $this->typingErrors['value'] = 'hasBillsToPay';
      $this->errorsInvoiceCharge();
    }
    return $has_payment;
  }

  /**
   * {@inheritdoc}
   */
  public function verifyInvoiceCharge($parameters = FALSE) {
    // Verify if invoice amount is less than 2.
    if ($this->hasPayment()) {
      try {
        // Query customer info API for know msisdn is B2B o STAFF.
        $info = $this->callCustomerInfo($this->primaryNumber['accountId']);
      }
      catch (HttpException $exception) {
        $info = [];
        $this->typingErrors['value'] = 'isB2B';
        $this->errorsInvoiceCharge();
      }
      if (isset($info->businessCharacteristics->isBusiness) && $info->businessCharacteristics->isBusiness === FALSE) {
        if ($parameters == FALSE) {
          if ($this->verifyStatus($this->primaryNumber['accountId'])) {
            return TRUE;
          }
        }
        else {
          if ($this->verifyStatus($this->primaryNumber['accountId'])) {
            if ($this->verifyStatus($this->targetNumber['accountId'])) {
              return TRUE;
            }
          }
        }
      }
      else {
        $this->typingErrors['value'] = 'isB2B';
        $this->errorsInvoiceCharge();
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMasterAccountRecord($id) {
    $header = [
      'Cache-Control' => 'no-cache',
      'bypass-cache' => 'true',
    ];
    return $this->manager
      ->load('oneapp_master_accounts_record_endpoint')
      ->setParams(['msisdn' => $id])
      ->setHeaders($header)
      ->setQuery([])
      ->sendRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function callCustomerInfo($id) {
    return $this->manager
      ->load('oneapp_mobile_billing_v2_0_customer_info_endpoint')
      ->setParams(['id' => $id])
      ->setHeaders([])
      ->setQuery(['businessUnit' => 'MOBILE'])
      ->sendRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function callInvoicesApi($id) {
    return $this->manager
      ->load('oneapp_mobile_billing_v2_0_invoices_endpoint')
      ->setParams(['id' => $id])
      ->setHeaders([])
      ->setQuery([])
      ->sendRequest();
  }

  /**
   * Get is Loan or not.
   *
   * @param mixed $data
   *   Body request data.
   *
   * @return bool
   *   Return if is Loan or not.
   */
  protected function isLoan($data) {
    // Get query object.
    $query = \Drupal::request()->query;
    $lower_params = \Drupal::service('oneapp.mobile.utils')->formatQueryParams($query);
    $loan_type_configs = \Drupal::config('oneapp_mobile.config')->get('loan_types');
    $key = $loan_type_configs['loanTypeQuery']['queryKey'];

    $query_loan_name = strtolower($key);
    $payment_method_name = isset($lower_params[$query_loan_name]) ? $lower_params[$query_loan_name] : '';

    $emergency = $loan_type_configs['emergencyLoan']['queryParamValue'];
    $billing_account_listance = $loan_type_configs['balanceLoan']['queryParamValue'];
    $package = $loan_type_configs['packageLoan']['queryParamValue'];

    if (
      $payment_method_name === $emergency ||
      $payment_method_name === $billing_account_listance ||
      $payment_method_name === $package ||
      (isset($data[$key]) && $data[$key] === $emergency) ||
      (isset($data[$key]) && $data[$key] === $billing_account_listance) ||
      (isset($data[$key]) && $data[$key] === $package)
    ) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Get is Invoice Charge or not.
   *
   * @param mixed $data
   *   Body request data.
   *
   * @return bool
   *   Return if is Invoice Charge or not.
   */
  protected function isInvoiceCharge($data) {
    // Query from the body.
    $body = (isset($data['paymentMethodName']) ? $data['paymentMethodName'] : '');
    $query = \Drupal::request()->query->get('paymentMethodName');
    if ($query == 'invoiceChargeSecurity' || $body == 'invoiceChargeSecurity') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Change validity to expirationDate format.
   */
  public function validityToExpirationDate($data, $config) {
    $date_format_name = "d/m/Y H:i";
    if (!empty($config['fields']['dateFormat']['label'])) {
      $date_format_name = $this->getPatterns($config['fields']['dateFormat']['label']);
    }
    $current_date = date($date_format_name);
    $validity_type = [
      'día' => 'days',
      'días' => 'days',
      'mes' => 'month',
      'meses' => 'month',
      'semana' => 'week',
      'semanas' => 'week',
      'hora' => 'hours',
      'horas' => 'hours',
      'day' => 'days',
      'days' => 'days',
      'month' => 'month',
      'months' => 'month',
      'week' => 'week',
      'weeks' => 'week',
      'hour' => 'hours',
      'hours' => 'hours',
    ];
    $data['validityType'] = strtolower($data['validityType']);
    if (isset($validity_type[trim($data['validityType'])])) {
      $new_validity_type = $validity_type[trim($data['validityType'])];
      $date = DrupalDateTime::createFromFormat($date_format_name, $current_date);
      $date->modify('+' . $data['validityNumber'] . ' ' . $new_validity_type);
      $expiration_date = $date->format($date_format_name);
    }
    else {
      return $data['validity'];
    }

    return $expiration_date;
  }

  /**
   * Get date formats.
   *
   * @return array
   *   Date formats.
   */
  public function getPatterns($name) {
    $date_types = DateFormat::loadMultiple();
    foreach ($date_types as $machineName => $format) {
      if ($format->get('id') === $name) {
        return $format->get('pattern');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  private function verifyPackageId($offer) {
    if ($offer) {
      $ids = $this->configBlock['config']['actions']['emergencyLoan']['offerIds'];
      $haystack = strtolower(str_replace(' ', '', $ids));
      $offer = strtolower($offer);
      $haystack = explode(',', $haystack);
      return in_array($offer, $haystack);
    }
    return FALSE;
  }

}
