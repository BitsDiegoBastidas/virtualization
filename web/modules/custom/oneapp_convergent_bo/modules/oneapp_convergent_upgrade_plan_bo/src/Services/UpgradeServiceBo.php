<?php

namespace Drupal\oneapp_convergent_upgrade_plan_bo\Services;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Utility\Token;
use Drupal\oneapp_convergent_upgrade_plan\Services\UpgradeService;

/**
 * Class UpgradeServiceBo.
 *
 * @package Drupal\oneapp_convergent_upgrade_plan_bo\Services;
 */
class UpgradeServiceBo extends UpgradeService {

  /**
   * @var array|object
   */
  protected $DarInfo;

  /**
   * {@inheritdoc}
   */
  public function getValidationPlanCode($billing_id) {
    return $this->validateRules($billing_id);
  }

  /**
   * {@inheritdoc}
   */
  public function validateRules($id) {
    try {
      return $this->manager
        ->load('oneapp_convergent_upgrade_plan_v2_0_rule_validate_endpoint')
        ->setParams(['id' => $id])
        ->setHeaders([])
        ->setQuery([])
        ->sendRequest();
    }
    catch (\Exception $e) {
      return $e;
    }
  }

  public function getClientEmailAddress() {
    $adf_jwt_service = $this->adfSimpleAuth;
    $email_to = $adf_jwt_service->getEmail();

    return $email_to ?? '';
  }

  public function sendEmailHome($data = [], $type = null, $email_to = null) {
    $adf_jwt_service = $this->adfSimpleAuth;

    if (is_null($email_to)) {
      $email_to = $this->getClientEmailAddress();
    }

    if (!empty($email_to)) {
      $email_setting = (!empty($this->configBlock['emailSetting'])) ?
        $this->configBlock['emailSetting'] : [];

      if (empty($email_setting)) {
        $email_setting = \Drupal::service("adf_block_config.config_block");
        $email_setting = $email_setting->getDefaultConfigBlock('oneapp_convergent_upgrade_plan_v2_0_upgrade_block');
        $email_setting = $email_setting['emailSetting'];
      }

      if (isset($email_setting['config']['enableEmailSend']) && !$email_setting['config']['enableEmailSend']) {
        return;
      }

      $from_name = (!empty($email_setting['config']['fromname'])) ?
        $email_setting['config']['fromname'] : '';
      $from_email = (!empty($email_setting['config']['from'])) ?
        $email_setting['config']['from'] : '';

      $token_service = $this->token;

      switch ($type) {
        case 'eml_success':
        case 'eml_success_wo':
        case 'eml_unsuccess_wo':
          $tokens = [
            'newPlan'     => $data['new_plan_name'],
            'currentPlan' => $data['current_plan_name'],
            'date'        => $data['date'],
            'price'       => $data['price'],
            'userName'    => $data['user_name'],
          ];
          break;
        case 'eml_success_sch':
          $tokens = [
            'newPlan'     => $data['new_plan'],
            'currentPlan' => $data['current_plan'],
            'dateStart'   => $data['date_start'],
            'dateEnd'     => $data['date_end'],
            'userName'    => $data['user_name'],
          ];
          break;
        case 'eml_unsuccess_sch':
          $tokens = [
            'newPlan'     => $data['new_plan'],
            'currentPlan' => $data['current_plan'],
            'userName'    => $data['user_name'],
          ];
          break;
        case 'eml_unsuccess':
          $tokens = [
            'newPlan'     => $data['new_plan_name'],
            'currentPlan' => $data['current_plan_name'],
            'price'       => $data['price'],
            'userName'    => $data['user_name'],
          ];
          break;
      }

      $subject = (!empty($email_setting[$type]['subject'])) ?
        $email_setting[$type]['subject'] : '';

      $body = (!empty($email_setting[$type]['body']['value'])) ?
        $email_setting[$type]['body']['value'] : '';

      $html_body = $token_service->replace($body, $tokens, [], new BubbleableMetadata());

      try {
        $params = [
          'from_name'  => $from_name,
          'from_email' => $from_email,
          'to'         => $email_to,
          'subject'    => $subject,
          'body'       => $html_body,
        ];

        $mail_service = $this->oneappMailerSend;
        $mail_service->sendMail(
          $params['from_name'],
          $params['from_email'],
          $params['to'],
          $params['subject'],
          $params['body'],
          'email'
        );
      }
      catch (\Exception $exception) {
        return $exception;
      }
    }
  }

  /**
   * No usar este método, se mantiene solo por retrocompatibilidad.
   * Usar getMasterAccount()
   * @param string $billing_account_id
   * @return mixed
   * @deprecated No usar este método, en su lugar usar getMasterAccount()
   */
  public function getDarInfo($billing_account_id = '') {
    return $this->getMasterAccount($billing_account_id, 'billingaccounts');
  }

  /**
   * {@inheritdoc}
   */
  public function getRecommendProductsData($id, $count_records = FALSE) {
    $recommend_products = $this->getRecommendProductsApi($id);

    if ($count_records) {
      if (!empty($recommend_products->productOfferingsList)) {
        return TRUE;
      }
      return [];
    }

    if (!empty($recommend_products->productOfferingsList)) {
      return $recommend_products->productOfferingsList;
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRecommendProductsApi($id, array $query = []) {
    try {
      return $this->manager
        ->load('oneapp_convergent_upgrade_plan_v2_0_recommended_products_endpoint')
        ->setParams([])
        ->setHeaders([
          'crmSystem' => 'home',
          'x-language' => 'spa',
          'x-channel' => 'DIGITAL'
        ])
        ->setQuery([
          'eligibility-contexts.asset.customerId' => $id,
          'eligibility-contexts.asset.agreementId' => $id
        ])
        ->sendRequest();

    } catch (\Exception $e) {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getProductList($id) {
    try {
      return $this->manager
        ->load('oneapp_convergent_upgrade_plan_v2_0_portfolio_products_endpoint')
        ->setParams(['id' => $id])
        ->setHeaders([])
        ->setQuery([])
        ->sendRequest();
    }
    catch (\Exception $e) {
     return [];
    }
  }

  /**
   * @param object $customer_account_list
   * @return array
   */
  public function formatCustomerAccountList($customer_account_list) {
    return [
      'customerAccountId' => $customer_account_list[0]->customerAccountId,
      'country' => $customer_account_list[0]->country,
      'partyOwnerType' => $customer_account_list[0]->partyOwner->partyType,
      'fullName' => $customer_account_list[0]->partyOwner->formattedName,
      'documentType' => $customer_account_list[0]->partyOwner->identificationPartyOwner->documentType,
      'documentNumber' => $customer_account_list[0]->partyOwner->identificationPartyOwner->documentNumber,
      'phone' => $customer_account_list[0]->partyOwner->contactMediumPartyOwner->phoneList[0]->phone ?? '',
      'email' => $customer_account_list[0]->partyOwner->contactMediumPartyOwner->emailList[0]->email ?? '',
      'billingAccountId' => $customer_account_list[0]->accountList[0]->billingAccountId ?? '',
      'businessUnit' => $customer_account_list[0]->accountList[0]->businessUnit ?? '',
      'billingType' => $customer_account_list[0]->accountList[0]->billingType ?? '',
      'primarySubscriberId' => $customer_account_list[0]->accountList[0]->primarySubscriberId ?? '',
      'msisdn' => $customer_account_list[0]->accountList[0]->subscriptionList[0]->msisdnList[0]->msisdn ?? '',
      'displayId' => $customer_account_list[0]->accountList[0]->displayId ?? '',
      'agreementId' => $customer_account_list[0]->accountList[0]->subscriptionList[0]->agreementId ?? '',
      'serviceAddress' => $customer_account_list[0]->accountList[0]->serviceAddress ?? '',
      'isActive' => $customer_account_list[0]->lifecycle->isActive,
      'status' => $customer_account_list[0]->lifecycle->status,
    ];
  }

  /**
   * No usar este método, se mantiene sólo por retrocompatibilidad
   * @param $billing_account_id
   * @return object|null
   * @deprecated No user este método, usar getCustomerAccountList()
   */
  public function getCustomerAccountByBillingAccountId($billing_account_id) {
    $customer_account_list = null;
    $dar_info = $this->getMasterAccount($billing_account_id, 'billingaccounts');
    if (!empty($dar_info)) {
      $customer_account_list = $dar_info->customerAccountList;
    }
    return $customer_account_list;
  }

  /**
   * No usar este método, se mantiene sólo por retrocompatibilidad
   * @param $subscriber_id
   * @return object|null
   * @deprecated No user este método, usar getCustomerAccountList()
   */
  public function getCustomerAccountBySubscriberId($subscriber_id) {
    $customer_account_list = null;
    $response = $this->getMasterAccount($subscriber_id);
    if (!empty($response)) {
      $customer_account_list = $response->customerAccountList;
    }
    return $customer_account_list;
  }

  /**
   * {@inheritdoc}
   */
  public function updateClientCurrentPlanApi($body) {
    return $this->manager
      ->load('oneapp_convergent_upgrade_plan_v2_0_update_client_current_plan_endpoint')
      ->setParams([])
      ->setHeaders(['crmSystem' => 'home','Content-Type' => 'application/json'])
      ->setQuery([])
      ->setBody($body)
      ->sendRequest();
  }

}
