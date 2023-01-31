<?php

namespace Drupal\oneapp_convergent_upgrade_plan_bo\Services\v2_0;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Exception;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use Drupal\oneapp_convergent_upgrade_plan\Services\v2_0\UpgradePlanSendRestLogic;
use Drupal\oneapp_convergent_upgrade_plan_bo\Services\UpgradePlanLogBoModelService;
use InvalidArgumentException;
use UnexpectedValueException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Class UpgradePlanSendRestLogicBO
 */
class UpgradePlanSendRestLogicBo extends UpgradePlanSendRestLogic {

  /**
   * {@inheritdoc}
   * @throws Exception
   */
  public function updateClientCurrentPlan($data) {
    $body = $this->getUpdateClientCurrentPlanBody($data);
    if (!$this->configBlock['test_config']['emulate_upgrade']) {
      return $this->service->updateClientCurrentPlanApi($body);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdateClientCurrentPlanBody($data) {
    $date = date_timestamp_get(date_create());
    return [
      'input' => [
        'included' => [
          'newInstance' => [
            'attributes' => [
              'referenceNumber' => "" . $date . "",
              'salesInfo' => [
                'channel' => "DIGITAL",
              ],
            ],
            "relationships" => [
              "customerAccountId" => "" . $data['billingAccountId'] . "",
            ],
            'included' => [
              'orderItems' => [
                [
                  'attributes' => [
                    "action" => "create",
                    "quantity" => 1,
                    "targetAgreementId" => "",
                  ],
                  'included' => [
                    'orderProduct' => [
                      'attributes' => [
                        'inputtedCharacteristics' => [
                          [
                            'key' => 'CH_New_Plan_Offer_ID',
                            'value' => "" . $data['planId'] . "",
                          ],
                          [
                            'key' => 'CH_Parent_ID',
                            'value' => "" . $data['current_plan_id'] . "",
                          ],
                        ],
                      ],
                      "relationships" => [
                        "productOfferingId" => "PO_Change_Plan",
                      ],
                    ],
                  ],

                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Activate the beneficiary line
   *
   * @param array $data
   *  The array to grab the data from
   *
   * @return string
   *  The number of the activated beneficiary line, empty string otherwise
   */
  public function activateBeneficiary($data) {
    $response = $this->service->getBeneficiary($data['billingAccountId']);
    $activated_beneficiary_line = $response->customerAccount->agreements[0]->agreementsItems[0]->products[0]->resources[0]->primaryId ?? '';
    if ($data['beneficiaryLine'] <> $activated_beneficiary_line) {
      $activated_beneficiary_line = '';
      $body = $this->getBeneficiaryBody($data);
      $attempts = isset($this->configBlock['recommendedOffers']['verification']['beneficiaryLineFields']['attempts'])
                    ? $this->configBlock['recommendedOffers']['verification']['beneficiaryLineFields']['attempts']
                    : 3;
      do {
        $response = $this->service->setBeneficiary($body);
        $attempts--;
        usleep(200);
      }
      while ($attempts && empty($response));
      if (!empty($response->rawMessage)
          && stripos($response->rawMessage, 'Beneficio:SI') !== FALSE) {
        $activated_beneficiary_line = $data['beneficiaryLine'];
      }
    }
    return $activated_beneficiary_line;
  }

  /**
   * Generate the beneficiary body array
   *
   * @param array $data
   *  The array to grab the data from
   *
   * @return array
   */
  public function getBeneficiaryBody($data = []) {
    /** @var \Drupal\Component\Uuid\Php $uuid_service */
    $uuid_service = \Drupal::service('uuid');
    $uuid_a = $uuid_service->generate();
    $uuid_b = $uuid_service->generate();
    $date = date_timestamp_get(date_create());
    return [
      'input' => [
        'included' => [
          'newInstance' => [
            'attributes' => [
              'referenceNumber' => $date,
              'salesInfo' => [
                'channel' => 'DIGITAL',
                ],
              ],
            'relationships' => [
                'customerAccountId' => $data['billingAccountId'],
              ],
            'included' => [
              'orderItems' => [
                'id' => $uuid_a,
                'action' => 'activate',
                'orderProduct' => [
                  'id' => $uuid_a,
                  'productOffering' => [
                    'id' => $uuid_a,
                    'name' => 'PO_SetBeneficiary',
                    ],
                  'realizingResources' => [
                    'edges' => [
                      0 => [
                        'id' => $uuid_b,
                        'resourceId' => $data['beneficiaryLine'],
                        'resourceType' => 'msisdn',
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function getData($data) {
    $config = (!empty($this->configBlock['confirmationUpgradePlan'])) ?
      $this->configBlock['confirmationUpgradePlan'] : [];

    $needs_wo = '';
    if (isset($data['needsWO']) && $data['needsWO']) {
      $needs_wo = 'WO';
    }

    $config_details = (!empty($config['cardDetail']['fields']))
      ? $config['cardDetail']['fields']
      : [];

    $date = new \DateTime('now');
    $format_date = (!empty($config_details['activateDate']['formatDate'])) ? $config_details['activateDate']['formatDate'] : 'short';

    $adf_jwt_service = $this->adfSimpleAuth;
    $email = $adf_jwt_service->getEmail();

    if (empty($email)) {
      $email = FALSE;
    }

    $config_result = (!empty($config['cardConfirmation']['fields']))
      ? $config['cardConfirmation']['fields']
      : [];
    $result = [
      'label' => (!empty($config_result['title'.$needs_wo]['label'])) ? $config_result['title'.$needs_wo]['label'] : '',
      'formattedValue' => (!empty($config_result['desc'.$needs_wo]['label'])) ? $config_result['desc'.$needs_wo]['label'] : '',
      'show' => (!empty($config_result['title'.$needs_wo]['show'])) ? TRUE : FALSE,
    ];

    $error = false;
    if (isset($data["error"]) && $data["error"]) {
      $error = true;
      $config_error = (isset($config['error']['fields'])) ? $config['error']['fields'] : [];
      $result = [
        'label' => (!empty($config_error['title']['label'])) ? $config_error['title']['label'] : '',
        'formattedValue' => (!empty($config_error['desc']['label'])) ? $config_error['desc']['label'] : '',
        'show' => (!empty($config_error['title']['show'])) ? TRUE : FALSE,
      ];
    }

    $response['result'] = [
        'label' => $result['label'],
        'formattedValue' => $result['formattedValue'],
        'value' => !$error,
        'email' => $email,
        'show' => $result['show'],
    ];

    if (!$error) {
      $response['confirmationDetails'] = [
        'title' => [
          'value' => (!empty($config_details['title']['label'])) ? $config_details['title']['label'] : '',
          'show' => (!empty($config_details['title']['show'])) ? TRUE : FALSE,
        ],
        'plan' => [
          'label' => (!empty($config_details['plan']['label'])) ? $config_details['plan']['label'] : '',
          'value' => $this->upgradeUtils->getFormatLowerCase($data['planName'], TRUE),
          'show' => (!empty($config_details['plan']['show'])) ? TRUE : FALSE,
        ],
        'previousPlan' => [
          'label' => (!empty($config_details['previousPlan']['label'])) ? $config_details['previousPlan']['label'] : '',
          'value' => $this->upgradeUtils->getFormatLowerCase($data['current_plan_name'], TRUE),
          'show' => (!empty($config_details['previousPlan']['show'])) ? TRUE : FALSE,
        ],
        'account' => [
          'label' => (!empty($config_details['account']['label'])) ? $config_details['account']['label'] : '',
          'value' => $this->upgradeUtils->getFormatAccount($data['displayId']),
          'show' => (!empty($config_details['account']['show'])) ? TRUE : FALSE,
        ],
        'price' => [
          'label' => (!empty($config_details['price']['label'])) ? $config_details['price']['label'] : '',
          'value' => $data['productsPrice'],
          'formattedValue' => $this->utils->formatCurrency($data['productsPrice'], TRUE),
          'show' => (!empty($config_details['price']['show'])) ? TRUE : FALSE,
        ],
        'activateDate' => [
          'label' => (!empty($config_details['activateDate']['label'])) ? $config_details['activateDate']['label'] : '',
          'value' => $this->homeUtils->formatDate($date->getTimestamp(), $format_date),
          'show' => (!empty($config_details['activateDate']['show'])) ? TRUE : FALSE,
        ],
        'footer' => [
          'value' => (!empty($config_details['footer']['label'])) ? $config_details['footer']['label'] : '',
          'show' => (!empty($config_details['footer']['show'])) ? TRUE : FALSE,
        ],
      ];
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validationData($data) {
    if (empty($data['newPlanId'])) {
      throw new \Exception("El campo newPlanId es requerido");
    }
    if (!isset($data['inmediate'])) {
      throw new \Exception("El campo inmediate es requerido");
    }
    if (!empty($data['confirmIdentity']['status'])) {
      $error_msg = (!empty($this->configBlock['confirmationUpgradePlan']['fieldErrorMsg'])) ?
        $this->configBlock['confirmationUpgradePlan']['fieldErrorMsg'] : [];
      if (empty($data['confirmIdentity']['identificationType'])) {
        $identification_type_msg = (!empty($error_msg['identificationType'])) ?
          $error_msg['identificationType'] : t('El tipo documento es requerido.');
        throw new \Exception($identification_type_msg);
      }
      if (empty($data['confirmIdentity']['identificationNumber'])) {
        $identification_number_msg = (!empty($error_msg['identificationNumber'])) ?
          $error_msg['identificationNumber'] : t('El número de documento es requerido.');
        throw new \Exception($identification_number_msg);
      }
    }
  }

  /**
   * Checks if ownership is valid
   *
   * @param string $id
   *  The account identifier
   * @param mixed $data
   *  The data containing the ownership information
   * @param mixed $id_type
   *  The type of the account
   *
   * @return bool|array
   *  True if ownership validation is successful, false otherwise
   *
   * @throws Exception
   */
  public function isOwnershipValid($id, $data, $id_type) {
    try {
      $customer_account = $this->service->getCustomerAccount($id_type, $id);
      $oneapp_settings = $this->service->getconfigFactoryService('oneapp_endpoints.settings');
      $country_iso = $oneapp_settings->get('country_iso');
      $document_type = $customer_account->partyOwner->identificationPartyOwner->documentType ?? '';
      $document_number = $customer_account->partyOwner->identificationPartyOwner->documentNumber ?? '';
      $issuing_country = $customer_account->partyOwner->identificationPartyOwner->issuingCountry ?? '';
      if ($document_type == $data['confirmIdentity']['identificationType']
      && $document_number == $data['confirmIdentity']['identificationNumber']
      && strtolower($issuing_country) == strtolower($country_iso)) {
        return TRUE;
      }
    } catch (\Exception $e) {
      return FALSE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   * @throws Exception
   */
  public function updateCurrentPlanHome($id, $data, $id_type) {
    $this->upgradeRecommended->setConfig($this->configBlock);
    $get_recommended_plan = $this->upgradeRecommended->get($id, $data['newPlanId']);
    if (empty($get_recommended_plan) || !empty($get_recommended_plan['noData']['value'])) {
      throw new \Exception(t('Can not upgrade to plan ' . $data['newPlanId']),400);
    }
    if (!empty($data['confirmIdentity']['status'])) {
      if ($this->isOwnershipValid($id, $data, $id_type)) {
        $this->documentUpdateDAR($id, $data, $id_type);
      } else {
        return [
          'isValidate' => FALSE,
        ];
      }
    }

    $log_data = [];
    $email_type = 'eml_success';
    $upgrade_status = UpgradePlanLogBoModelService::UPGRADE_STATUS_PENDING;
    $order_status = UpgradePlanLogBoModelService::ORDER_STATUS_NOT_REQUIRED;
    $customer_account_info = $this->service->getCustomerAccount($id_type, $id);
    $customer_account_info->request = (object) $data;
    $data['customerAccountId'] = $customer_account_info->customerAccountId;
    $data['billingAccountId'] = $customer_account_info->accountList[0]->billingAccountId;
    $account_info_formatted = $this->service->formatCustomerAccountList([$customer_account_info]);
    $get_recommended_plan[$data['newPlanId']] = array_merge($get_recommended_plan[$data['newPlanId']], $account_info_formatted);
    $adf_jwt_service = $this->adfSimpleAuth;
    $zendesk_data = [
      'id' => $id,
      'name' => "{$adf_jwt_service->getGivenNameUser()} {$adf_jwt_service->getFirstNameUser()}",
      'document_number' => $this->service->getDocumentDARByBillingaccountId($id) ?? '',
      'contract_id' => $this->homeUtils->getContractIdByBillingAccount($id) ?? $id,
      'current_plan_name' => ucwords(strtolower($get_recommended_plan[$data['newPlanId']]['current_plan_name'])),
      'recommended_plan_name' => $get_recommended_plan[$data['newPlanId']]['planName'],
      'email' => $this->service->getClientEmailAddress(),
    ];
    try {
      if ($this->configBlock['test_config']['emulate_upgrade_exception']) {
        throw new Exception('Excepción de prueba al hacer el upgrade');
      }
      $this->updateClientCurrentPlan($get_recommended_plan[$data['newPlanId']]);
      $upgrade_status = UpgradePlanLogBoModelService::UPGRADE_STATUS_DONE;
      $beneficiary_line = (isset($this->configBlock['test_config']['emulate_beneficiary_line_failure'])
        && $this->configBlock['test_config']['emulate_beneficiary_line_failure'])
        ? ''
        : $this->activateBeneficiary($data);
      if (empty($beneficiary_line)) {
        $zendesk_response = $this->
          sendZendeskTicket($zendesk_data, 'subj_beneficiary_line', ['Beneficiary Line' => $data['beneficiaryLine'] ?? '']);
      }
      else {
        $log_data['beneficiary_line'] = $beneficiary_line;
      }
      $response_data = $this->getData($get_recommended_plan[$data['newPlanId']]);
      if ((isset($get_recommended_plan[$data['newPlanId']]['needsWO'])
        && $get_recommended_plan[$data['newPlanId']]['needsWO'])) { // the new plan requires Working Order
        $email_type = 'eml_success_wo';
        $order_status = UpgradePlanLogBoModelService::ORDER_STATUS_NOT_GENERATED;
      }
    }
    catch (Exception $e) {
      $response_data = $this->getData(["error" => true]);
      $zendesk_response = $this->sendZendeskTicket($zendesk_data, 'subj_update_error');
      $email_type = 'eml_unsuccess';
    }

    $fields_to_log = [
      'client_name' => $account_info_formatted['fullName'] ?? 'Error',
      'service_number' => $account_info_formatted['billingAccountId'] ?? 'Error',
      'id_plan' => $get_recommended_plan[$data['newPlanId']]['planId'] ?? '',
      'name_plan' => $get_recommended_plan[$data['newPlanId']]['planName'] ?? '',
      'data' => json_encode($log_data, JSON_PRETTY_PRINT),
      'contract_id' => $zendesk_data['contract_id'],
      'billing_account' => $id ?? 'Error',
      'order_status' => $order_status,
      'upgrade_status' => $upgrade_status,
      'id_ticket_zendesk' => $zendesk_response['id'] ?? 0,
      'ticket_zendesk' => $zendesk_response['status'] ?? 'No response',
      'client_adf_name' => $zendesk_data['name'],
      'document_number' => $zendesk_data['document_number'],
      'current_plan_name' => $zendesk_data['current_plan_name'],
    ];
    $this->addLog($fields_to_log);
    $email_data = [
      'new_plan_name'     => $fields_to_log['name_plan'],
      'current_plan_name' => $fields_to_log['current_plan_name'],
      'date'              => isset($response_data['confirmationDetails']['activateDate']['value'])
                              ? trim(explode('-', $response_data['confirmationDetails']['activateDate']['value'])[0])
                              : '',
      'price'             => isset($get_recommended_plan[$data['newPlanId']]['productsPrice'])
                              ? $this->utils->formatCurrency($get_recommended_plan[$data['newPlanId']]['productsPrice'], TRUE)
                              : '',
      'user_name'         => $fields_to_log['client_adf_name'],
    ];
    $this->service->sendEmailHome($email_data, $email_type);
    return $response_data;
  }

  /**
   *
   * @param string $id
   *  The account identifier
   * @param mixed $data
   *  The data containing the ownership information
   * @param mixed $id_type
   *  The type of the account
   *
   * @return array|bool
   * array with error result, true otherwise
   *
   * @throws Exception
   */
  public function documentUpdateDAR($id, $data, $id_type) {
    $customer_account_info = $this->service->getCustomerAccount($id_type, $id);
    $customer_account_info->request = (object) $data;
    $adf_simple_auth = $this->service->getAdfSimpleAuthMethod();
    $sec_login = $adf_simple_auth->getSecLogin();
    $enable_dar = $this->upgradeUtils->getFieldConfigValue($this->configBlock['generalConfig']['enableDocumentUpdateDAR'], NULL, 0);
    if ($sec_login && $enable_dar) {
      $document_verified = $this->upgradeUtils->getFieldConfigValue($this->configBlock['generalConfig']['documentVerified'], NULL, 0);
      if ($document_verified) {
        $sec_login = FALSE;
      }
      $body = [
        'identification' => [
          'documentType' => $customer_account_info->partyOwner->identificationPartyOwner->documentType,
          'documentNumber' => $customer_account_info->partyOwner->identificationPartyOwner->documentNumber,
          'documentAppCountry' => $customer_account_info->partyOwner->identificationPartyOwner->issuingCountry,
          'issuingCountry' => $customer_account_info->partyOwner->identificationPartyOwner->issuingCountry,
          'documentVerified' => $sec_login,
        ],
        'trace' => [
          'creationChannel' => 'oneapp',
          'creationChannelType' => 'upselling',
        ],
      ];
      if (!empty($sec_login)) {
        $body['identification']['documentVerificationType'] =
          (!empty($this->configBlock['generalConfig']['documentVerificationType'])) ?
            $this->configBlock['generalConfig']['documentVerificationType'] : '';
      }
      $master_updated_document = $this->service->sendUpdateDocumentDARApi($body);
      if (!isset($master_updated_document->success) || empty($master_updated_document->success)) {
        $config = (!empty($this->configBlock['confirmationUpgradePlan']))
          ? $this->configBlock['confirmationUpgradePlan']
          : [];
        $config_error = (isset($config['error']['fields'])) ? $config['error']['fields'] : [];
        return [
          'result' => [
            'label' => (!empty($config_error['title']['label'])) ? $config_error['title']['label'] : '',
            'formattedValue' => (!empty($config_error['desc']['label'])) ? $config_error['desc']['label'] : '',
            'value' => FALSE,
            'show' => (!empty($config_error['title']['show'])) ? TRUE : FALSE,
          ],
        ];
      }
    }
    return true;
  }

  /**
   * Generates Zendesk ticket
   *
   * @param array $data
   * @param string $subject_id
   * @param array $comment_array
   *
   * @return array|object
   */
  public function sendZendeskTicket($data, $subject_id, $comment_array = []) {
    $comment_array = array_merge([
      'Nombre completo' => $data['name'],
      'DUI' => $data['document_number'],
      'Cuenta de facturación' => $data['id'],
      'Código cliente' => $data['contract_id'],
      'Plan actual' => $data['current_plan_name'],
      'Paquete nuevo aceptado' => $data['recommended_plan_name'],
    ], $comment_array);

    $status_zendesk = (object)[
      'id' => 0,
      'status' => FALSE,
    ];

    if (!$this->configBlock) {
      $adf_block_cofig_service = \Drupal::service("adf_block_config.config_block");
      $this->configBlock = $adf_block_cofig_service->getDefaultConfigBlock('oneapp_convergent_upgrade_plan_v2_0_upgrade_block');
    }
    if (isset($this->configBlock['zendesk'])) {
      $custom_fields = [];
      $count_custom_fields = $this->configBlock["zendesk"]["custom_fields"];
      for ($i = 1; $i <= $count_custom_fields; ++$i) {
        $type = 'home';
        if (!empty($this->configBlock["zendesk"]['fields'][$type][$i]['id'])) {
          $element = [
            'id' => $this->configBlock["zendesk"]['fields'][$type][$i]['id'],
            'value' => $this->configBlock["zendesk"]['fields'][$type][$i]['value'],
          ];
          $custom_fields[] = $element;
        }
      }

      $subject = str_replace('@plan', $data['recommended_plan_name'], $this->configBlock["zendesk"][$subject_id]);
      $parametros_zendesk = [
        'name' => $data['name'],
        'email' => $data['email'],
        'subject' => $subject,
        'body' => $this->arrayToCommentForZendesk($comment_array),
        'tags' => (strpos($this->configBlock["zendesk"]["tags"], ',') !== FALSE) ?
          explode(",", $this->configBlock["zendesk"]["tags"]) : $this->configBlock["zendesk"]["tags"],
        'brand_id' => $this->configBlock["zendesk"]["brand_id"],
        'ticket_form_id' => $this->configBlock["zendesk"]["ticket_form_id"],
        'fields' => $custom_fields,
      ];

      $zendesk = $this->oneappZendesk;
      try {
        if ($this->configBlock["zendesk"]['enableZendesk']) {
          $ticket_response = $zendesk->createZendeskTicket($parametros_zendesk);
        }
        else {
          $ticket_response = new \stdClass();
          $ticket_response->ticket = new \stdClass();
          $ticket_response->ticket->id = 'test-001';
        }
        \Drupal::logger('mi_log')->warning('generacion_ticket', ['parametros' => $parametros_zendesk]);
        if (isset($ticket_response->ticket->id)) {
          $status_zendesk = [
            'id' => $ticket_response->ticket->id,
            'status' => TRUE,
          ];
        }
      } catch (\Exception $e) {
        $ticket_response = $e->getMessage();
        $this->loggerFactory->get('upgrade_plan_zendesk')->debug($ticket_response);
      }
    }
    else {
      $this->loggerFactory->get('upgrade_plan_zendesk')->debug("No existe configuracion de zendesk");
    }

    return $status_zendesk;
  }

  /**
   * Stores the current transaction log in the database
   *
   * @param array $fields
   *
   * @return StatementInterface|null|false|void
   */
  public function addLog($fields) {
    // default fields
    $default_fields = [
      'order_id' => NULL, // The visit id, in the next step
      'client_email' => $this->service->getClientEmailAddress(),
      'date_visit' => NULL,
      'scheduling_attempts' => 0,
      'date' => date('Y-m-d H:i:s'),
      'business_unit' => 'HOME',
    ];

    // replace empty fields with default values
    foreach ($default_fields as $d_key => $d_val) {
      if (!array_key_exists($d_key, $fields)) {
        $fields[$d_key] = $d_val;
      }
    }

    try {
      $return = \Drupal::database()
        ->insert('oneapp_convergent_upgrade_plan_bo_log')
        ->fields($fields)
        ->execute();

      if ($return) {
        $this->enqueue((int)$return);
      }
      return $return;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }


  /**
   *
   * @param int $upgrade_id
   *
   * @return void
   *
   * @throws ContainerNotInitializedException
   * @throws ServiceCircularReferenceException
   * @throws ServiceNotFoundException
   */
  public function enqueue(int $upgrade_id) {
    /** @var QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    /** @var QueueInterface $queue */
    $queue = $queue_factory->get('upgrade_plan_bo_home_queue');
    $data = (object)[
      "upgrade_id" => $upgrade_id,
      "time" => \Drupal::time()->getCurrentTime() + 31,
    ];
    $queue->createItem($data);
  }

  /**
   * get the last appointment scheduled by the account Id
   *
   * @param string $id
   *  The account Id
   *
   * @return object
   *  The last appointment scheduled for the provided account Id
   */
  public function getLastAppointmentScheduledById($id) {
    $last_appointment = null;
    /** @var \Drupal\oneapp_home_scheduling_bo\Services\SchedulingServiceBo $schedule_service */
    $schedule_service = \Drupal::service('oneapp_home_scheduling.v2_0.scheduling_service');
    $appoiments = $schedule_service->getScheduledVisitsById($id);

    $ordered_appointments = [];
    if (is_array($appoiments) && !empty($appoiments)) {
      foreach ($appoiments as $appointment) {
        $appointment_date = \Drupal\Core\Datetime\DrupalDateTime::createFromFormat('Y-m-d\TH:i:s', $appointment->createdAt);
        $ordered_appointments[$appointment_date->getTimestamp()] = $appointment;
      }
    }
    if (!empty($ordered_appointments)) {
      krsort($ordered_appointments);
      $last_appointment = array_shift($ordered_appointments);
    }

    return $last_appointment;
  }

  /**
   * Get available scheduling for the given account Id
   *
   * @param string $id
   * @param object $appointment
   *
   * @return object
   */
  public function getAvailableRescheduleDates($id, $appointment) {
    $available_dates = null;
    $schedule_service = \Drupal::service('oneapp_home_scheduling.v2_0.scheduling_service');

    $schedule_settings = \Drupal::service('adf_block_config.config_block')
      ->getDefaultConfigBlock('oneapp_home_scheduling_v2_0_visit_reschedule_block');
    $range_date = intval($schedule_settings['others']['confReschedule']['days'] ?? NULL) + 1;
    $format_date = $schedule_settings['others']['dateTimeForRescheduling']['format'];
    $date_types = DateFormat::loadMultiple();
    foreach ($date_types as $name => $format) {
      if ($name == $format_date) {
        $format_date = $format->getPattern();
        break;
      }
    }
    $system_date = DrupalDateTime::createFromTimestamp(\Drupal::time()->getCurrentTime());
    $time_zone = ($schedule_settings['others']['confReschedule']['timeZone'] ?? '') ?: $system_date->format('P');
    $start_date = strtotime("{$system_date->format('Y-m-d')} + 1 days");
    $end_date = strtotime("+ {$range_date} days", $start_date);
    /** @var \Drupal\Core\Datetime\DateFormatter $formatter */
    $date_formatter = \Drupal::service('date.formatter');
    $end_date = $date_formatter->format($end_date, "custom", $format_date, $time_zone);
    $start_date = $date_formatter->format($start_date, "custom", $format_date, $time_zone);
    $available_dates = (object) $schedule_service
      ->retrieveAvailableDatesByRange($id, $appointment->id, $appointment->attributes->{'sub-id'}, $start_date, $end_date);

    return $available_dates;
  }

  /**
   * Sets the new schedule for the $appointment
   *
   * @param object $available_dates
   * @param string $id
   * @param object $appointment
   *
   * @return object
   */
  public function rescheduleAppointment($available_dates, $id, $appointment) {
    $scheduled = null;
    $schedule_service = \Drupal::service('oneapp_home_scheduling.v2_0.scheduling_service');

    $visit_date = $available_dates->availableTimeslots[0]->validFor;
    $visit_start_date = $visit_date->startDatetime;
    $visit_end_date = $visit_date->endDatetime;
    $params = [
      'id' => $id,
      'appointmentId' => $appointment->id,
      'externalId' => $appointment->attributes->{'sub-id'},
    ];
    $query = [
      'startDateTime' => $visit_start_date,
      'endDateTime' => $visit_end_date,
    ];
    $headers = [];
    $scheduled = $schedule_service->sendRescheduleVisitEndpoint($params, $query, $headers);
    if (isset($scheduled->id)) {
      $scheduled->dates = $query;
    }

    return $scheduled;
  }

  /**
   * This is the method invoked by the cron hook
   *
   * @return void
   */
  public function processPendingScheduleUpgrades() {
    if (!$this->configBlock) {
      $adf_block_cofig_service = \Drupal::service("adf_block_config.config_block");
      $this->configBlock = $adf_block_cofig_service->getDefaultConfigBlock('oneapp_convergent_upgrade_plan_v2_0_upgrade_block');
    }
    if (isset($this->configBlock['async_config']['cron'])) {
      if (!$this->configBlock['async_config']['cron']['enable']) {
        return;
      }
      $cron_config = [
        'pending_upgrade_process_limit' => $this->configBlock['async_config']['cron']['pending_upgrade_process_limit'],
        'max_retries'                   => $this->configBlock['async_config']['cron']['max_retries'],
      ];
    }
    else { // default config if there is not configuration found
      $cron_config = [
        'pending_upgrade_process_limit' => 10,
        'max_retries' => 3,
      ];
    }
    // obtain pendindg upgrade records
    $pending_upgrades = UpgradePlanLogBoModelService::getPendingScheduleUpdates(
      $cron_config['max_retries'],
      $cron_config['pending_upgrade_process_limit']
    );

    // if there aren't pending upgrades
    if (empty($pending_upgrades)) {
      return;
    }

    foreach ($pending_upgrades as $pending_upgrade) {
      $pending_upgrade_processed = $this->processPendingUpgrade($pending_upgrade, (int)$cron_config['max_retries']);
      if (!$pending_upgrade_processed) { // if wasn't processed, add it to the queue again
        $this->enqueue((int)$pending_upgrade->id);
      }
    }
  }

  /**
   * Updates the current transaction data in the database
   *
   * @param object $upgrade
   *  The object representing the current log data in the database
   * @param array $log_data
   *  The data to log
   *
   * @return void
   */
  public function updateLog($upgrade, $log_data = []) {
    $fields = (array)$upgrade;
    $data = (array)json_decode($upgrade->data);
    if (!empty($log_data)) {
      $data = $data + $log_data;
    }
    $fields['data'] = json_encode($data, JSON_PRETTY_PRINT);
    UpgradePlanLogBoModelService::updateLog($fields);
  }


  /**
   * Process individual pending upgrades
   *
   * @param object $pending_upgrade
   *  The pending upgrade object to process
   * @param int $max_retries
   *  Amount of retries
   *
   * @return boolean
   *  True if the upgrade must be retried, false otherwise
   *
   * @throws InvalidArgumentException
   * @throws UnexpectedValueException
   * @throws ContainerNotInitializedException
   * @throws ServiceCircularReferenceException
   * @throws ServiceNotFoundException
   * @throws PluginNotFoundException
   * @throws InvalidPluginDefinitionException
   */
  public function processPendingUpgrade(Object $pending_upgrade, int $max_retries = 3) {
    $log_data = [];
      $email_data = [
        'new_plan'     => $pending_upgrade->name_plan,
        'current_plan' => $pending_upgrade->current_plan_name,
        'user_name'    => $pending_upgrade->client_adf_name,
        'date_start'   => '',
        'date_end'     => '',
      ];
      $date = new DrupalDateTime();
      $date = $date->format('d/m/Y H:i:s O');
      $zendesk_data = [
        'id'                    => $pending_upgrade->billing_account,
        'name'                  => $pending_upgrade->client_adf_name,
        'document_number'       => $pending_upgrade->document_number,
        'contract_id'           => $pending_upgrade->contract_id,
        'current_plan_name'     => $pending_upgrade->current_plan_name,
        'recommended_plan_name' => $pending_upgrade->name_plan,
        'email'                 => $pending_upgrade->client_email,
      ];

      $last_appointment = $this->getLastAppointmentScheduledById($pending_upgrade->billing_account);
      if (!$last_appointment) {
        $retry = true;
        $pending_upgrade->scheduling_attempts++;
        if ($pending_upgrade->scheduling_attempts >= $max_retries) {
          $wo_not_found = 'No se encontró Orden de trabajo para el UpgradePlan';
          $log_data['wo_not_found'] = $wo_not_found;
          $zendesk_response = $this->sendZendeskTicket($zendesk_data, 'subj_wo_not_found');
          $pending_upgrade->id_ticket_zendesk = $zendesk_response['id'];
          $pending_upgrade->ticket_zendesk = $zendesk_response['status'];
          $this->service->sendEmailHome($email_data, 'eml_unsuccess_wo', $pending_upgrade->client_email);
          $retry = false;
        }
        $log_data['retry_'.$pending_upgrade->scheduling_attempts] = 'Reintento no exitoso ' . $date;
        $this->updateLog($pending_upgrade, $log_data);
        return $retry;
      }

      $pending_upgrade->order_status = UpgradePlanLogBoModelService::ORDER_STATUS_NOT_SCHEDULED;
      $log_data['orded_generated'] = 'Orden asignada ' . $date;
      $available_dates = $this->getAvailableRescheduleDates($pending_upgrade->billing_account, $last_appointment);
      if (is_null($available_dates) || !is_object($available_dates)
        || (isset($available_dates->error) && $available_dates->error)) {
        $no_dates_available = 'No se encontró agenda disponible';
        $log_data['dates_available'] = $no_dates_available;
        $zendesk_response = $this->sendZendeskTicket($zendesk_data, 'subj_no_dates_available');
        $pending_upgrade->id_ticket_zendesk = $zendesk_response['id'];
        $pending_upgrade->ticket_zendesk = $zendesk_response['status'];
        $this->service->sendEmailHome($email_data, 'eml_unsuccess_sch', $pending_upgrade->client_email);
        $this->updateLog($pending_upgrade, $log_data);
        return false;
      }

      $scheduled = $this->rescheduleAppointment($available_dates, $pending_upgrade->billing_account, $last_appointment);
      if (is_null($scheduled) || !is_object($scheduled)
        || (isset($scheduled->error) && $scheduled->error)) {
        $zendesk_response = $this->sendZendeskTicket($zendesk_data, 'subj_reschedule_error');
        $pending_upgrade->id_ticket_zendesk = $zendesk_response['id'];
        $pending_upgrade->ticket_zendesk = $zendesk_response['status'];
        $this->service->sendEmailHome($email_data, 'eml_unsuccess_sch', $pending_upgrade->client_email);
        $log_data['rescheduled_visit'] = "No se encontró agenda disponible " . $date;
        $this->updateLog($pending_upgrade, $log_data);
        return false;
      }

      $log_data['order_scheduled'] = 'Orden agendada ' . $date;
      if (isset($scheduled->id)) {
        $pending_upgrade->order_id = $scheduled->id;
        if (isset($scheduled->dates['startDateTime'])) {
          $pending_upgrade->date_visit = $scheduled->dates['startDateTime'];
          $log_data['order_scheduled'] = 'Orden agendada: ' . $pending_upgrade->date_visit . " | " . $date;
          $email_data['date_start'] = $scheduled->dates['startDateTime'];
        }
        if (isset($scheduled->dates["endDateTime"])) {
          $email_data['date_end'] = $scheduled->dates["endDateTime"];
        }
      }
      $pending_upgrade->order_status = UpgradePlanLogBoModelService::ORDER_STATUS_SCHEDULED;
      $this->service->sendEmailHome($email_data, 'eml_success_sch', $pending_upgrade->client_email);
      $this->updateLog($pending_upgrade, $log_data);
      return false;
  }

}
