<?php

namespace Drupal\oneapp_convergent_payment_gateway_bo\Services\v2_0;

use Drupal\oneapp_convergent_payment_gateway_bo\Services\v2_0\UtilsCallbackRestLogicBo;

/**
 * Class CallbackInvoicesRestLogicBo.
 */
class CallbackInvoicesRestLogicBo extends UtilsCallbackRestLogicBo {

  /**
   * {@inheritdoc}
   */
  public function __construct($transactions) {
    parent::__construct($transactions);
  }

  /**
   * {@inheritdoc}
   */
  public function apiPaymentProcessComplete($businessUnit, $purchaseOrderId, $typePage, $params, $headers) {

    parent::setProductType('invoices');
    parent::setBusinessUnit($businessUnit);
    parent::setTypePage($typePage);
    parent::loadTransactions($purchaseOrderId);
    parent::setParamas($params);
    parent::setHeaders($headers);

    // Validate url.
    $this->isValidUrl();

    // Validate token.
    $this->validToken();

    // Validate transaction status.
    $this->isValidStateOrder();

    // Validate Duplicate Callback.
    $this->isDuplicateCallback();

    // Do enrollments
    $this->doEnrollment();

    $response = $this->executeProcesses();

    return $response;
  }

}
