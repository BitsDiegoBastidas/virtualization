<?php

namespace Drupal\oneapp_convergent_payment_gateway_qr_bo\Services\v2_0;
use Drupal\oneapp_convergent_payment_gateway_qr\Services\v2_0\QrRestLogic;

/**
 * Class QrRestLogicBo.
 */
class QrRestLogicBo extends QrRestLogic {

  /**
   * start transaction and generate url QR
   *
   * @param [type] $id
   * @param [type] $id_type
   * @param [type] $business_unit
   * @param [type] $product_type
   * @param [type] $params
   * @return array
   */
  public function generateCodeQR($id, $id_type, $business_unit, $product_type) {
    /* If the line is convergent, the business unit must be changed from mobile to home,
        since the APIs only bring information for home accounts and their debt information
        is the same in home and mobile.*/
    $billing_account_id = $id;
    $this->paymentGatewayService->getVariablesIfConvergent($billing_account_id, $business_unit, $id_type);
    $this->tokenAuthorization->setBusinessUnit($business_unit);
    $this->tokenAuthorization->setIdType($id_type);
    $this->params['accountNumber'] = $account_number = $this->utilsPayment->getAccountNumberForPaymentGatewayFromToken($business_unit, $billing_account_id);
    $transaction = $this->initPayment($billing_account_id, $account_number, $id_type, $business_unit, $product_type);
    if (empty($transaction['transactionExist'])) {
      if ($business_unit == 'home') {
        $id = $billing_account_id;
        $this->tokenAuthorization->setId($id);
      }
      $data_payment = $this->updatePayment($id, $account_number, $id_type, $business_unit, $product_type, $transaction);
    }
    else {
      $data_payment = $this->getData($id, $transaction['transactionId']);
    }

    $data = array_merge($transaction, $data_payment);
    return $this->getFormat($data);

  }

  /**
   * start payment for save url QR
   */
  public function initPayment($id, $account_number, $id_type, $business_unit, $product_type) {
    $this->validAmount($id, $id_type, $business_unit, $product_type);
    $card_brand = t('QR Simple');
    if (!empty($this->params['paymentMethod']) && $this->params['paymentMethod'] == 'tigoQrPos') {
      $card_brand = 'QR MiTigo Code';
    }
    if ($business_unit == 'home') {
      $id = $account_number;
      $this->tokenAuthorization->setId($id);
    }
    $fields = [
      'uuid' => $this->tokenAuthorization->getUserIdPayment(),
      'cardBrand' => $card_brand,
      'accountId' => $id,
      'accountNumber' => $account_number,
      'accountType' => $business_unit,
      'productType' => $product_type,
      'amount' => $this->params["amount"],
      'numberReference' => 0,
      'accessType' => $this->tokenAuthorization->getAccessType(),
    ];

    $transaction_exist = $this->getDataId($id, $account_number);
    if (!empty($transaction_exist)) {
      $purchaseorder_id = $this->transactions->encryptId($transaction_exist);
      $transaction_id = $this->transactions->decryptId($purchaseorder_id);
    }
    else {
      $transaction_id = $this->transactions->initTransaction($fields, $product_type);
      $purchaseorder_id = $this->transactions->encryptId($transaction_id);
    }

    return [
      'purchaseorderId' => $purchaseorder_id,
      'transactionId' => $transaction_id,
      'transactionExist' => $transaction_exist,
    ];
  }

}
