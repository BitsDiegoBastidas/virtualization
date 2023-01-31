<?php

namespace Drupal\oneapp_convergent_payment_gateway_tm_invoices_bo\Services;

use Drupal\oneapp\ApiResponse\ErrorBase;
use Drupal\oneapp\Exception\BadRequestHttpException;
use Drupal\oneapp_convergent_payment_gateway_tm_invoices\Services\PaymentGatewayTmInvoicesRestLogic;

/**
 * Class PaymentGatewayTmInvoicesRestLogic.
 */
class PaymentGatewayTmInvoicesRestLogicBo extends PaymentGatewayTmInvoicesRestLogic {

  /**
   * PaymentGatewayRestLogic constructor.
   */
  public function __construct($manager, $utils_payment, $transactions, $token_authorization, $utils) {
    $account_service = \Drupal::service('oneapp_convergent_accounts.v2_0.accounts');
    $data_service = \Drupal::service('oneapp_convergent_payment_gateway.v2_0.my_cards_data_service');
    return parent::__construct($manager, $utils_payment, $transactions, $token_authorization, $utils, $account_service, $data_service);
  }

  /**
   * Return getConvergent.
   */
  public function getVariablesIfConvergent(&$id, &$business_unit, &$id_type) {
    $is_convergent = $this->getBillingAccountIdForConvergentMsisdn($id, $id_type);
    $business_unit = $is_convergent['value'] ? 'home' : $business_unit;
    $id_type = $is_convergent['value'] ? 'billingaccounts' : $id_type;
    $id = $is_convergent['value'] ? $is_convergent['billingAccountId'] : $id;
  }
}
