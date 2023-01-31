<?php

namespace Drupal\oneapp_convergent_payment_gateway_autopayments_bo\Services;

use Drupal\oneapp\ApiResponse\ErrorBase;
use Drupal\oneapp\Exception\UnauthorizedHttpException;
use Drupal\oneapp_convergent_payment_gateway_autopayments\Services\EnrollmentsService;

/**
 * Class EnrollmentsServiceBo.
 */
class EnrollmentsServiceBo extends EnrollmentsService {

  /**
   * Get the payment enrollments by params.
   */
  public function getEnrollmentsByParams($business_unit, $id_type, $id, $account_number = NULL) {
    $billing_account_id = $id;
    $this->paymentGatewayService->getVariablesIfConvergent($billing_account_id, $business_unit, $id_type);
    $this->tokenAuthorization->setBusinessUnit($business_unit);
    $this->tokenAuthorization->setIdType($id_type);
    if ($business_unit == 'home') {
      $id = $billing_account_id;
      $this->tokenAuthorization->setId($id);
    }
    if (empty($account_number)) {
      $account_number = $this->utilsPayment->getAccountNumberForPaymentGatewayFromToken($business_unit, $id);
      $enrrollment_accoun_id = $account_number;
    }
    else {
      $enrrollment_accoun_id = $account_number;
    }
    $is_b2b = $this->tokenAuthorization->isB2B($id, $id_type);
    $config = $this->tokenAuthorization->getApplicationSettings("configuration_app");
    $params['billingSystem'] = $config["setting_app_payment"]["billingSystemName"];
    $params['apiHost'] = $config["setting_app_payment"]["api_path"];
    if (isset($id)) {
      $params['id'] = $enrrollment_accoun_id;
    }
    else {
      $error = new ErrorBase();
      $error->getError()->set('message', 'The account Id does not exist in the current request.');
      throw new UnauthorizedHttpException($error);
    }

    $aws_service = $config["setting_app_payment"]["aws_service"] ?? 'payment';
    return $this->awsManager->callAwsEndpoint('oneapp_convergent_payment_gateway_v2_0_enrollments_endpoint', $aws_service, [], $params, [], []);
  }

  /**
   * Delete the payment enrollment by ID.
   */
  public function deleteEnrollmentById($business_unit, $id_type, $id, $enrollment_id) {
    $is_b2b = $this->tokenAuthorization->isB2B($id, $id_type);
    $billing_account_id = $id;
    $this->paymentGatewayService->getVariablesIfConvergent($billing_account_id, $business_unit, $id_type);
    $this->tokenAuthorization->setBusinessUnit($business_unit);
    $this->tokenAuthorization->setIdType($id_type);
    $config = $this->tokenAuthorization->getApplicationSettings('configuration_app');
    $params['apiHost'] = $config["setting_app_payment"]["api_path"];
    $params['bsname'] = $config["setting_app_payment"]["billingSystemName"];
    if ($business_unit == 'home') {
      $id = $billing_account_id;
      $this->tokenAuthorization->setId($id);
    }
    $params['accountnumber'] = $this->utilsPayment->getAccountNumberForPaymentGatewayFromToken($business_unit, $id);
    // Get enrollment email.
    $response_enrollments = $this->getEnrollmentsByParams($business_unit, $id_type, $id, $params['accountnumber']);
    if (isset($response_enrollments->body)
      && $response_enrollments->body->id) {
      $this->params['email'] = $response_enrollments->body->trace->email;
    }
    $this->params['email'] = (empty($this->tokenAuthorization->getEmail()) && isset($this->params['email'])) ?
      $this->params['email'] : $this->tokenAuthorization->getEmail();

    $aws_service = $config["setting_app_payment"]["aws_service"] ?? 'payment';
    $delete_enrollment = $this->awsManager->callAwsEndpoint('oneapp_convergent_payment_gateway_v2_0_delete_enrollment_by_params_endpoint', $aws_service, [], $params, [], []);

    $mail_service = \Drupal::service('oneapp_convergent_payment_gateway.v2_0.email_callbacks_service');
    $module_config_mails = $this->tokenAuthorization->getApplicationSettings('configuration_mail_recurring');
    $tokens = [
      'username' => $this->tokenAuthorization->getGivenNameUser() . ' ' . $this->tokenAuthorization->getFirstNameUser(),
      'mail_to_send' => $this->params['email'],
      'accountId' => $id,
    ];
    if ($module_config_mails['cc_mail'] !== '') {
      $tokens['cc_mail'] = $module_config_mails['cc_mail'];
    }
    $config_mail = [
      'subject' => $module_config_mails['delete_payment']['subject'],
      'body' => $module_config_mails['delete_payment']['body']['value'],
    ];
    $activate_send_mail = isset($module_config_mails['active_send_mail']) ? (bool) $module_config_mails['active_send_mail'] : FALSE;
    if ($activate_send_mail) {
      $mail_service->apiPaymentSendMail($tokens, $config_mail);
    }
    return (array) $delete_enrollment;
  }

}
