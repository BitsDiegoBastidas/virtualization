<?php

namespace Drupal\oneapp_convergent_accounts_bo\Services\v2_0;

use Drupal\oneapp_convergent_accounts\Services\v2_0\AccountsService;

/**
 * Class AccountsServiceBo.
 */
class AccountsServiceBo extends AccountsService {

  /**
   * Get formatted user accounts list from the master account record.
   */
  public function getAccountListByTokenPayload($payload, $read_only = TRUE) {
    $one_app_settings = \Drupal::config('oneapp_endpoints.settings');
    $country_iso = $one_app_settings->get('country_iso');
    $mobile_utils = \Drupal::service('oneapp.mobile.utils');
    $user_account_list = [];
    $account_info = [];
    $response = $this->getMasterAccountRecord($payload, $read_only);
    if (isset($response) && isset($response->body)) {
      $master_account_record = $response->body;
      // Set user info.
      if (isset($master_account_record[0]->digitalIdentity)) {
        $digital_identity = $master_account_record[0]->digitalIdentity;
        $user_account_list['uuid'] = $digital_identity->uuid;
        $user_account_list['email'] = $digital_identity->email;
        $user_account_list['emailVerified'] = $digital_identity->emailVerified;
        $user_account_list['phone'] = $digital_identity->phone;
        $user_account_list['phoneVerified'] = $digital_identity->phoneVerified;
        $user_account_list['hasPassword'] = $digital_identity->hasPassword;
        if (isset($digital_identity->party)) {
          $user_account_list['givenName'] = $digital_identity->party->givenName;
          $user_account_list['familyName'] = $digital_identity->party->familyName;
        }
        if (isset($digital_identity->identificationList) && !empty($digital_identity->identificationList)) {
          $identification_list = $digital_identity->identificationList;
          foreach ($identification_list as $identificationInfo) {
            if ($country_iso == $identificationInfo->documentAppCountry) {
              $user_account_list['documentType'] = $identificationInfo->documentType;
              $user_account_list['documentNumber'] = $identificationInfo->documentNumber;
              $user_account_list['documentVerified'] = $identificationInfo->documentVerified;
            }
          }
        }
        if (isset($payload)) {
          $user_account_list['secureLogin'] = isset($payload->secLogin) ? (bool) $payload->secLogin : '';
          $user_account_list['authTime'] = isset($payload->auth_time) ? (string) $payload->auth_time : '';
          $user_account_list['exp'] = isset($payload->exp) ? (string) $payload->exp : '';
          $user_account_list['iat'] = isset($payload->iat) ? (string) $payload->iat : '';
        }
      }
      // Set accounts info.
      $this->userAccountInfo = $user_account_list;
      // Set account list.
      if (isset($master_account_record[0]->customerAccountList)) {
        $user_account_list['accountList'] = [];
        foreach ($master_account_record[0]->customerAccountList as $customerAccount) {
          // Filter the country accounts.
          if (isset($customerAccount->accountList) && isset($customerAccount->country) && $country_iso == $customerAccount->country) {
            $owner_party_type = (!empty($customerAccount->partyOwner->partyType)) ? $customerAccount->partyOwner->partyType : '';
            $party_type = ($owner_party_type == 'business') ? $owner_party_type : 'individual';
            $segment = (!empty($customerAccount->partyOwner->segment)) ? $customerAccount->partyOwner->segment : NULL;
            foreach ($customerAccount->accountList as $account) {
              if (isset($account->subscriptionList) && !empty($account->subscriptionList)) {
                if (isset($account->businessUnit) && $account->businessUnit != 'mobile') {
                  $convergent_account_info = [
                    'billingAccountId' => $account->billingAccountId,
                    'customerAccountId' => $customerAccount->customerAccountId,
                    'partyRole' => isset($account->partyRole) ? $account->partyRole : '',
                    'organizationRole' => isset($account->organizationRole) ? $account->organizationRole : '',
                    'billingType' => isset($account->billingType) ? $account->billingType : '',
                    'displayId' => isset($account->displayId) ? $account->displayId : '',
                    'displayLabel' => isset($account->displayLabel) ? $account->displayLabel : '',
                    'serviceAddress' => isset($account->serviceAddress) ? $account->serviceAddress : '',
                    'businessUnit' => (isset($account->businessUnit) && $account->businessUnit == 'convergent') ?
                      'home' : $account->businessUnit,
                    'sourceSystemId' => isset($account->sourceSystemId) ? $account->sourceSystemId : '',
                    'partyType' => $party_type,
                    'segment' => (!empty($segment)) ? $segment : '',
                  ];
                  $this->b2bUserRoleAttr($payload, $convergent_account_info);
                  $user_account_list['accountList'][] = $convergent_account_info;
                  unset($convergent_account_info);
                  // Count B2B lines
                  $this->validateB2bAccountAccess($owner_party_type, $account, $user_account_list, $segment);
                }
                foreach ($account->subscriptionList as $subscription) {
                  $account_info['planId'] = $subscription->planId;
                  $account_info['planName'] = $subscription->planName;
                  $account_info['agreementId'] = $subscription->agreementId;
                  $account_info['subscriptionType'] = $subscription->subscriptionType;
                  if (isset($subscription->msisdnList) && !empty($subscription->msisdnList)) {
                    foreach ($subscription->msisdnList as $mobileLine) {
                      $display_id = $mobile_utils->getFormattedMsisdn($mobileLine->msisdn);
                      $account_info['billingAccountId'] = $account->billingAccountId;
                      $account_info['customerAccountId'] = $customerAccount->customerAccountId;
                      $account_info['partyRole'] = isset($account->partyRole) ? $account->partyRole : '';
                      $account_info['organizationRole'] = isset($account->organizationRole) ? $account->organizationRole : '';
                      $account_info['billingType'] = isset($account->billingType) ? $account->billingType : '';
                      $account_info['displayId'] = isset($display_id) ? $display_id : '';
                      $account_info['displayLabel'] = isset($account->billingType) ?
                        $mobile_utils->getBillingType($account->billingType) : '';
                      $account_info['businessUnit'] = 'mobile';
                      $account_info['sourceSystemId'] = isset($account->sourceSystemId) ? $account->sourceSystemId : '';
                      $account_info['msisdn'] = $mobileLine->msisdn;
                      $account_info['billingType'] =
                        (isset($account_info['subscriptionType']) && ($account_info['subscriptionType'] == 'hybrid')) ?
                          $account_info['subscriptionType'] : $account_info['billingType'];
                      if (isset($account->businessUnit) && $account->businessUnit == 'convergent') {
                        $account_info['linkedAccountId'] = $account->billingAccountId;
                        $account_info['planType'] = $subscription->subscriptionType;
                      }
                      $account_info['partyType'] = $party_type;
                      $account_info['segment'] = (!empty($segment)) ? $segment : '';
                      $this->b2bUserRoleAttr($payload, $account_info);
                      $user_account_list['accountList'][] = $account_info;
                      unset($account_info['linkedAccountId']);
                      unset($account_info['planType']);
                      // Count B2B lines
                      $this->validateB2bAccountAccess($owner_party_type, $account, $user_account_list, $segment);
                    }
                  }
                }

              }
              else {
                $account_info = [
                  'billingAccountId' => $account->billingAccountId,
                  'customerAccountId' => $customerAccount->customerAccountId,
                  'partyRole' => isset($account->partyRole) ? $account->partyRole : '',
                  'organizationRole' => isset($account->organizationRole) ? $account->organizationRole : '',
                  'billingType' => isset($account->billingType) ? $account->billingType : '',
                  'displayId' => isset($account->displayId) ? $account->displayId : '',
                  'displayLabel' => isset($account->displayLabel) ? $account->displayLabel : '',
                  'serviceAddress' => isset($account->serviceAddress) ? $account->serviceAddress : '',
                  'businessUnit' => isset($account->businessUnit) ? $account->businessUnit : '',
                  'sourceSystemId' => isset($account->sourceSystemId) ? $account->sourceSystemId : '',
                  'partyType' => $party_type,
                  'segment' => (!empty($segment)) ? $segment : '',
                ];
                $this->b2bUserRoleAttr($payload, $account_info);
                $user_account_list['accountList'][] = $account_info;
                // Count B2B lines
                $this->validateB2bAccountAccess($owner_party_type, $account, $user_account_list, $segment);
              }
            }
          }
        }
      }
    }

    // Mostrar solo, cuentas que hagan match con el token.
    if ($payload->allAcc == 'true' && !empty($user_account_list['accountList'])) {
      $token_account_list = $this->getTokenAccountList($payload);
      $subscriber_ids = array_column($token_account_list, 'subscriberId');
      $account_list = array_filter($user_account_list['accountList'], function($item, $k) use ($subscriber_ids) {
        //Ajustar subscriber_id segun el pais.
        $subscriber_id = $item["businessUnit"] == 'home' ? $item["billingAccountId"] : $item["msisdn"];
        return in_array($subscriber_id, $subscriber_ids);
      }, ARRAY_FILTER_USE_BOTH);
      if (!empty($account_list)) {
        $user_account_list['accountList'] = array_values($account_list);
      }
    }

    // Validate B2B lines
    $this->validateB2bAccountAccess(FALSE, FALSE, $user_account_list);

    return $user_account_list;
  }

}
