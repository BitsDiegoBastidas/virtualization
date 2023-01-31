<?php

namespace Drupal\oneapp_mobile_upselling_bo\Services\v2_0;

use Drupal\oneapp\Exception\HttpException;
use Drupal\oneapp_mobile_upselling\Services\v2_0\PacketsOrderDetailsRestLogic;

/**
 * Class PacketsOrderDetailsRestLogic.
 */
class PacketsOrderDetailsRestLogicBo extends PacketsOrderDetailsRestLogic {

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $balance;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $isPostpaid;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $myNumber;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $primaryNumber;

  /**
   * Target Number.
   *
   * @var mixed
   */
  protected $targetNumber;

  /**
   * Target Number.
   *
   * @var string
   */
  protected $hasStatusInvalid;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $data;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $isAutopack;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $configAutopack;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $offer;

  /**
   * {@inheritdoc}
   * @throws \ReflectionException
   */
  public function get($id, $package_id, $target_msisdn = FALSE) {
    $this->primaryNumber['accountId'] = $id;
    $this->targetNumber['accountId'] = $target_msisdn;
    $this->myNumber = FALSE;
    $this->isAutopack = FALSE;
    $this->hasStatusInvalid = FALSE;
    $this->getTypeLine();

    if (! isset($this->tigoInvalido['value'])
      && ! $this->hasStatusInvalid &&
      ($this->myNumber || $this->getPossibilityToGift()['value'])) {

      $this->offer = $this->getOffer($this->targetNumber['accountId'], $package_id);
    }

    $bug = $this->bugMapping();
    if ($bug['value']) {
      return [
        'data' => $this->getDataBo($this->targetNumber['accountId']),
        'config' => $this->getConfigurationsBugs($bug),
      ];
    }
    else {
      $this->getCoreBalance();
      return [
        'data' => $this->getDataBo($this->targetNumber['accountId']),
        'config' => $this->getConfigurationsResponseBo(),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function verifyIdDoubleCharge($offer) {
    if ($offer) {
      $ids = $this->configBlock['config']['actions']['emergencyLoan']['offerIds'];
      $haystack = strtolower(str_replace(' ', '', $ids));
      $offer = strtolower($offer);
      $haystack = explode(',', $haystack);
      return in_array($offer, $haystack);
    }
    return FALSE;
  }

  /**
   * Implements getOffer.
   *
   * @param string $msisdn
   *   Msisdn value.
   * @param string $package_id
   *   Package Id.
   *
   * @return mixed
   *   Object with information of the offer.
   *
   * @throws \Drupal\oneapp\Exception\HttpException
   * @throws \ReflectionException
   */
  protected function getOffer($msisdn, $package_id) {
    $service = \Drupal::service('oneapp_mobile_upselling.v2_0.offer_details_rest_logic');

    if ($msisdn == $this->primaryNumber['accountId']) {
      $this->offer = $service->get($msisdn, $package_id);
    }
    elseif ($msisdn == $this->targetNumber['accountId']) {
      if ($this->primaryNumber['isQvantel'] && $this->targetNumber['isQvantel']) {
        $this->offer = $service->get($msisdn, $package_id);
      }
      elseif ($this->primaryNumber['isQvantel']) {
        $this->offer = $service->get(
          $msisdn,
          $this->getIdOfferBySystemOfferId($package_id)
        );
      }
      elseif ($this->targetNumber['isQvantel']) {
        $this->offer = $service->get(
          $msisdn,
          $this->getSystemOfferIdByIdOffer($package_id)
        );
      }
      else {
        $this->offer = $service->get($msisdn, $package_id);
      }
    }

    if (isset($this->offer['error'])) {
      $this->offer['accountId'] = $msisdn;
    }

    return $this->offer;
  }

  /**
   * Implements getCoreBalance.
   *
   * @param string $msisdn
   *   Msisdn value.
   *
   * @return array
   *   The HTTP response object.
   *
   * @throws \Drupal\oneapp\Exception\HttpException
   * @throws \ReflectionException
   */
  public function getBalances($msisdn) {
    $unit = $this->utils->getCurrencyCode(FALSE);
    if ($this->primaryNumber['info'] == 'postpaid') {
      $this->balance = [
        'currencyId' => $unit,
        'value' => 0,
        'formattedValue' => $this->utils->formatCurrency(0, TRUE),
      ];
    }
    else {
      try {
        $balances = $this->manager
          ->load('oneapp_mobile_upselling_v2_0_core_balance_endpoint')
          ->setHeaders(['bypass-cache' => "false"])
          ->setQuery([])
          ->setParams(['msisdn' => $msisdn])
          ->sendRequest();

        $available_balance = isset($balances->balances[0]->balanceAmount) ? $balances->balances[0]->balanceAmount : 0;
        $this->balance = [
          'currencyId' => $unit,
          'value' => $available_balance,
          'formattedValue' => $this->utils->formatCurrency($available_balance, TRUE),
        ];
      }
      catch (HttpException $exception) {
        $this->balance = [
          'currencyId' => '',
          'value' => '',
          'formattedValue' => '',
        ];
      }
    }
    return $this->balance;
  }

  /**
   * Implements getCurrentPlan.
   *
   * @param string $msisdn
   *   Msisdn value.
   *
   * @return mixed
   *   Msisdn value.
   *
   * @throws \ReflectionException
   */
  protected function getPlan($msisdn) {
    $config = $this->configBlock;
    $message_error = $config['config']['messages']['number_error']['label'];
    try {
      return $this->manager
        ->load('oneapp_mobile_plans_v2_0_current_endpoint')
        ->setHeaders([])
        ->setQuery([])
        ->setParams(['msisdn' => $msisdn])
        ->sendRequest();
    }
    catch (HttpException $exception) {
      $message = $message_error;
      $reflected_object = new \ReflectionClass(get_class($exception));
      $property = $reflected_object->getProperty('message');
      $property->setAccessible(TRUE);
      $property->setValue($exception, $message);
      $property->setAccessible(FALSE);
      throw $exception;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSystemOfferIdByIdOffer($package_id) {
    $system_offer_id = '';
    if ($package_id) {
      $ids = \Drupal::entityQuery('paquetigos_entity')->execute();
      $paquetigos = \Drupal::entityTypeManager()
        ->getStorage('paquetigos_entity')
        ->loadMultiple($ids);
      foreach ($paquetigos as $paquetigo) {
        $id_packet = $paquetigo->getIdOffer();
        if ($id_packet == $package_id) {
          $system_offer_id = $paquetigo->getSystemOfferId();
          break;
        }
      }
      return $system_offer_id;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdOfferBySystemOfferId($package_id) {
    $id_packet = '';
    if ($package_id) {
      $ids = \Drupal::entityQuery('paquetigos_entity')->execute();
      $paquetigos = \Drupal::entityTypeManager()
        ->getStorage('paquetigos_entity')
        ->loadMultiple($ids);
      foreach ($paquetigos as $paquetigo) {
        $system_offer_id = $paquetigo->getSystemOfferId();
        if ($system_offer_id == $package_id) {
          $id_packet = $paquetigo->getIdOffer();
          break;
        }
      }
      return $id_packet;
    }
    return FALSE;
  }

  /**
   * Implements throwException.
   *
   * @throws \ReflectionException
   */
  protected function throwException(HttpException $exception) {
    $messages = $this->configBlock['config']['response']['getInfo'];
    $title = !empty($this->configBlock['label']) ? $this->configBlock['label'] . ': ' : '';
    $message = ($exception->getCode() == '404') ? $title . $messages['notFound'] : $title . $messages['error'];

    $reflected_object = new \ReflectionClass(get_class($exception));
    $property = $reflected_object->getProperty('message');
    $property->setAccessible(TRUE);
    $property->setValue($exception, $message);
    $property->setAccessible(FALSE);

    throw $exception;
  }

  /**
   * {@inheritdoc}
   */
  public function getGeneralInfoByMsisdn($msisdn) {
    try {
      return $this->manager
        ->load('oneapp_mobile_v2_0_client_account_general_info_endpoint')
        ->setParams(['id' => $msisdn])
        ->setHeaders([])
        ->setQuery(['searchType' => 'MSISDN', 'documentType' => 1])
        ->sendRequest();
    }
    catch (HttpException $exception) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    $status = $this->getGeneralInfoByMsisdn($this->primaryNumber['accountId']);
    foreach ($status->TigoApiResponse->response->contracts->ContractType->accounts->AssetType as $assetType) {
      if (isset($assetType->accountState) && ($assetType->accountState === 'SR' || $assetType->accountState === 'MO')) {
        $this->hasStatusInvalid = TRUE;
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfoByClientAccountGeneralInfo() {
    $info = $this->getGeneralInfoByMsisdn($this->targetNumber['accountId']);
    $this->targetNumber['isQvantel'] = $this->mobileUtils->isQvantel($this->targetNumber['accountId']);

    if ($info->TigoApiResponse->status == "ERROR") {
      $this->targetNumber['info'] = FALSE;
    }
    else {
      foreach ($info->TigoApiResponse->response->contracts->ContractType->accounts->AssetType as $assetType) {
        $this->targetNumber['info'] = $this->translatePlantype($assetType->plans->PlanType->planType);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDataBo($msisdn) {
    $msisdn = $this->getMsisdn($msisdn);
    $this->data['msisdn'] = [
      'label' => $this->configBlock['fields']['msisdn']['label'],
      'value' => $msisdn,
      'formattedValue' => $msisdn,
      'show' => (bool) $this->configBlock['fields']['msisdn']['show'],
    ];
    if ($this->offer && !isset($this->offer['error']) && !$this->isPostpaid && !$this->hasStatusInvalid) {
      $formatted_price = $this->utils->formatCurrency($this->offer['cost'][0]['amount'], TRUE, TRUE);
      $this->data['amount'] = [
        'label' => $this->configBlock['fields']['price']['label'],
        'value' => [$this->offer['cost'][0]],
        'formattedValue' => $formatted_price,
        'show' => (bool) $this->configBlock['fields']['price']['show'],
      ];
      $this->data['detail'] = [
        'label' => $this->configBlock['fields']['description']['label'],
        'formattedValue' => $this->offer['description'],
        'show' => (bool) $this->configBlock['fields']['description']['show'],
      ];
      $this->data['period'] = [
        'label' => $this->configBlock['fields']['period']['label'],
        'formattedValue' => isset($this->offer['validity']) ? $this->offer['validity'] : '',
        'show' => (bool) $this->configBlock['fields']['period']['show'],
      ];
    }
    return $this->data;
  }

  /**
   * Returns planType for msisdn.
   */
  public function getTypeLine() {
    try {
      $info_by_token = $this->mobileUtils->getInfoTokenByMsisdn($this->primaryNumber['accountId']);
      if (!$info_by_token['subscriptionType']) {
        $info_by_token['subscriptionType'] = $info_by_token['billingType'];
      }
      $this->primaryNumber['info'] = $this->translatePlantype($info_by_token['subscriptionType']);
      $this->isPostpaid = $this->primaryNumber['info'] == 'postpaid' ? TRUE : FALSE;
      $this->primaryNumber['isQvantel'] = $this->mobileUtils->isQvantel($this->primaryNumber['accountId']);
      if ($this->primaryNumber['info'] == 'hybrid') {
        $this->getStatus();
      }

      if (!$this->primaryNumber['info']) {
        $this->tigoInvalido['accountId'] = $this->primaryNumber['accountId'];
        $this->tigoInvalido['value'] = TRUE;
      }
      elseif (str_replace(' ', '', $this->primaryNumber['accountId']) === str_replace(' ', '', $this->targetNumber['accountId'])) {
        $this->myNumber = TRUE;
        $this->targetNumber['info'] = $this->primaryNumber['info'];
      }
      else {
        $this->getInfoByClientAccountGeneralInfo();
        if (!$this->targetNumber['info']) {
          $this->tigoInvalido['accountId'] = $this->targetNumber['accountId'];
          $this->tigoInvalido['value'] = TRUE;
        }
      }
    }
    catch (HttpException $exception) {
      if (isset($this->primaryNumber['info'])) {
        $this->tigoInvalido['accountId'] = $this->targetNumber['accountId'];
      }
      else {
        $this->tigoInvalido['accountId'] = $this->primaryNumber['accountId'];
      }
      $this->tigoInvalido['value'] = TRUE;
    }
  }

  /**
   * Returns if its posibly gift packets.
   */
  public function getPossibilityToGift() {
    if (!$this->myNumber) {
      switch ($this->primaryNumber['info']) {
        case 'prepaid':
        case 'hybrid':
          $array = ['prepaid', 'hybrid'];
          break;

        default:
          $array = [];
          break;
      }
      $this->allowedGift['value'] = in_array($this->targetNumber['info'], $array);
      if (!$this->allowedGift['value']) {
        $this->allowedGift['accountId'] = $this->targetNumber['accountId'];
      }
      return $this->allowedGift;
    }
  }

  /**
   * Mapping errors.
   */
  protected function bugMapping() {
    $rows = [];
    if (isset($this->tigoInvalido['value']) && $this->tigoInvalido['value']) {
      $rows = [
        'label' => $this->configBlock['config']['messages']['number_error']['label'],
        'show' => (bool) $this->configBlock['config']['messages']['number_error']['show'],
      ];
    }
    elseif ($this->isPostpaid) {
      $rows = [
        'label' => $this->configBlock['config']['messages']['postpaid_invalid']['label'],
        'show' => (bool) $this->configBlock['config']['messages']['postpaid_invalid']['show'],
      ];
    }
    elseif ($this->hasStatusInvalid) {
      $rows = [
        'label' => $this->configBlock['config']['messages']['hasStatusInvalid']['label'],
        'show' => (bool) $this->configBlock['config']['messages']['hasStatusInvalid']['show'],
      ];
    }
    elseif (empty($this->offer) || (! empty($this->offer) && isset($this->offer['error']))) {
      $rows = [
        'label' => $this->configBlock['config']['messages']['offer_error']['label'],
        'show' => (bool) $this->configBlock['config']['messages']['offer_error']['show'],
      ];
    }
    elseif (!$this->myNumber && isset($this->allowedGift['value']) && !$this->allowedGift['value']) {
      $rows = [
        'label' => $this->configBlock['config']['messages']['gift_invalid']['label'],
        'show' => (bool) $this->configBlock['config']['messages']['gift_invalid']['show'],
      ];
    }

    if (empty($rows)) {
      return [
        'value' => false,
      ];
    }
    return [
      'value' => true,
      'bug' => $rows,
    ];
  }

  /**
   * Returns configurations for actions.
   */
  public function getConfigurationsBugs($bug) {
    $actions = $this->getActions($bug['value']);
    $actions['paymentMethods'] = $bug['bug'];
    $dataconfig['titleDetails'] = [
      'label' => $this->configBlock['fields']['title']['label'],
      'show' => (bool) $this->configBlock['fields']['title']['show'],
    ];
    $dataconfig['paymentMethods'] = [
      'label' => $this->configBlock['config']['actions']['paymentMethodsTitle']['value'],
      'show' => (bool) $this->configBlock['config']['actions']['paymentMethodsTitle']['show'],
    ];
    return [
      'actions' => $actions,
      'dataconfig' => $dataconfig,
    ];
  }

  /**
   * Get Msisdn.
   */
  public function getMsisdn($msisdn) {
    if (isset($this->tigoInvalido['accountId'])) {
      return $this->tigoInvalido['accountId'];
    }
    elseif (isset($this->allowedGift['accountId'])) {
      return $this->allowedGift['accountId'];
    }
    elseif (isset($this->offer['error'])) {
      return $this->offer['accountId'];
    }
    else {
      return $msisdn;
    }
  }

  /**
   * Validate if packageId is autopacket.
   */
  public function getValidAutoPacket($packet_id) {
    $this->configAutopack = FALSE;
    $is_type_client_allowed = $this->isTypeClientAllowed($this->primaryNumber['info']);
    if ($is_type_client_allowed) {
      if (\Drupal::hasService('oneapp_mobile_payment_gateway_autopackets.v2_0.autopackets_services')) {
        $autopacks_service = \Drupal::service('oneapp_mobile_payment_gateway_autopackets.v2_0.autopackets_services');
        $this->isAutopack = $autopacks_service->isValidAutoPacket($packet_id, $this->offer['cost'][0]['amount']);
      }
      return $this->isAutopack;
    }
  }

  /**
   * Returns configurations for actions.
   */
  public function getActions($bug = FALSE) {
    $config = $this->configBlock['config']['actions'];

    $changeMsisdn_show = ($bug) ? FALSE : (bool) $config['changeMsisdn']['show'];
    if (str_contains($_GET['packetId'],'tp,')) {
      $changeMsisdn_show = FALSE;
    }
    $actions['changeMsisdn'] = [
      'label' => $config['changeMsisdn']['label'],
      'url' => $config['changeMsisdn']['url'],
      'type' => $config['changeMsisdn']['type'],
      'show' => $changeMsisdn_show,
    ];
    $actions['fullDescription'] = [
      'label' => $config['fulldescription']['label'],
      'url' => $config['fulldescription']['url'],
      'type' => $config['fulldescription']['type'],
      'show' => ($bug) ? FALSE : (bool) $config['fulldescription']['show'],
    ];
    $actions['rechargeMessage'] = [
      'label' => $config['rechargeMessage']['label'],
      'url' => $config['rechargeMessage']['url'],
      'externalUrl' => $config['rechargeMessage']['externalUrl'],
      'type' => $config['rechargeMessage']['type'],
      'show' => (bool) $config['rechargeMessage']['show'],
    ];
    if ($this->isAutopack && !$bug && !str_contains($_GET['packetId'],'tp,')) {
      $actions += $this->getPurchaseFrecuency();
    }
    return $actions;
  }

  /**
   * Get frecuency options.
   */
  public function getPurchaseFrecuency() {
    $data_config = [];
    if ($this->isAutopack) {
      $frecuency_msg = ($this->myNumber) ? $this->configAutopack["frecuency"]['recurrentDescription'] :
        $this->configAutopack["frecuency"]['onceDescription'];
      $data_config['purchaseFrecuency'] = [
        "show"  => (bool) $this->configAutopack["frecuency"]['show'],
        "label" => $this->configAutopack["frecuency"]['label'],
        "description"  => $frecuency_msg,
      ];
      $default_onces = ($this->configAutopack["frecuency"]['actions']['type'] == 'once') ? TRUE :
        FALSE;
      $default_recurrent = ($this->configAutopack["frecuency"]['actions']['type'] == 'recurrent') ? TRUE :
        FALSE;
      $data_config['purchaseFrecuency']['options']['once'] = [
        "value"  => ($this->myNumber) ? $default_onces : TRUE,
        "label" => $this->configAutopack["frecuency"]['once']['label'],
        "type"  => 'radio',
        'show'  => TRUE,
      ];
      $data_config['purchaseFrecuency']['options']['recurrent'] = [
        "value"  => ($this->myNumber) ? $default_recurrent : FALSE,
        "label" => $this->configAutopack["frecuency"]['recurrent']['label'],
        "type"  => 'radio',
        'show'  => ($this->myNumber) ? TRUE : FALSE,
      ];
    }
    return $data_config;
  }

  /**
   * Returns corebalance for msisdn.
   */
  public function getCoreBalance() {
    $unit = $this->utils->getCurrencyCode(FALSE);
    if ($this->primaryNumber['info'] == 'postpaid') {
      $this->balance = [
        'currencyId' => $unit,
        'value' => 0,
        'formattedValue' => $this->utils->formatCurrency(0, TRUE),
      ];
    }
    else {
      try {
        $available_balance = 0;
        $balances = $this->manager
          ->load('oneapp_mobile_upselling_v2_0_core_balance_endpoint')
          ->setHeaders(['bypass-cache' => "false"])
          ->setQuery([])
          ->setParams(['msisdn' => $this->primaryNumber['accountId']])
          ->sendRequest();
        foreach ($balances->balances as $balance) {
          if (strtolower($balance->unit) == 'bs' && (strtolower($balance->wallet) == 'credito' || strtolower($balance->wallet) == 'saldo total')) {
            $available_balance = $balance->balanceAmount;
          }
        }
        $config_manager = \Drupal::service('adf_block_config.config_block');
        $block_config = $config_manager->getDefaultConfigBlock('oneapp_mobile_upselling_v2_0_balances_block');
        if (isset($block_config["general"]["label_balances"]["label"]) && !empty($block_config["general"]["label_balances"]["label"])) {
          $wallet = explode('|', $block_config["general"]["label_balances"]["label"]);
          $core = \Drupal::service('oneapp_mobile_upselling.v2_0.core_balances_services');
          $available_balance = $core->getAmount($balances, $wallet);
        }
        $this->balance = [
          'currencyId' => $unit,
          'value' => $available_balance,
          'formattedValue' => $this->utils->formatCurrency($available_balance, TRUE),
        ];
      }
      catch (HttpException $exception) {
        $this->balance = [
          'currencyId' => '',
          'value' => '',
          'formattedValue' => '',
        ];
      }
    }
    return $this->balance;
  }

  /**
   * Returns configurations for json response.
   */
  public function getConfigurationsResponseBo() {
    $config = $this->configBlock['config']['actions'];
    $actions = $this->getActions();
    $dataconfig['titleDetails'] = [
      'label' => $this->configBlock['fields']['title']['label'],
      'show' => (bool) $this->configBlock['fields']['title']['show'],
    ];
    $dataconfig['paymentMethods'] = [
      'label' => $this->configBlock['config']['actions']['paymentMethodsTitle']['value'],
      'show' => (bool) $this->configBlock['config']['actions']['paymentMethodsTitle']['show'],
    ];
    if ($this->isAutopack && $this->myNumber) {
      $payment_methods_autopacks = $this->getPaymentMethodsAutoPacksBo();
      if ($payment_methods_autopacks != []) {
        $row = $payment_methods_autopacks;
      }
    }

    // Se recorren las configuraciones del bloque de configuración.
    foreach ($config as $id => $field) {
      if (($id == 'coreBalance' && !isset($row['coreBalance'])) ||
        ($id == 'coreBalance' && isset($row['coreBalance']) && $row['coreBalance']['show'] === FALSE)) {
        $row[$id] = [
          'paymentMethodName' => $field['title'],
          'label' => $field['label'],
          'url' => $field['url'],
          'type' => $field['type'],
          'show' => FALSE,
          'isRecurrent' => FALSE,
          'weight' => $field['weight'],
        ];
        if ($this->myNumber) {
          if ($this->primaryNumber['info'] == 'prepaid' || $this->primaryNumber['info'] == 'hybrid') {
            $row[$id]['show'] = $this->verifyCoreBalanceMethod();
            if ($row[$id]['show']) {
              $row[$id]['description'] = $this->getDescriptionResponse();
              $row[$id]['confirmation'] = $this->confirmationCoreBalanceResponseBo($row[$id]['description']);
            }
          }
        }
      }
      elseif (($id == 'creditCard' && !isset($row['creditCard'])) ||
        ($id == 'creditCard' && isset($row['creditCard']) && $row['creditCard']['show'] === FALSE)) {
        $show_credit = FALSE;
        $row[$id] = [
          'paymentMethodName' => $field['title'],
          'label' => $field['label'],
          'url' => $field['url'],
          'type' => $field['type'],
          'show' => $show_credit,
          'isRecurrent' => FALSE,
          'description' => [
            'label' => $field['description'] ?? '',
            'show' => !empty($field['description']),
          ],
          'weight' => $field['weight'],
        ];
        if ($this->targetNumber['info'] == 'prepaid' || $this->targetNumber['info'] == 'hybrid') {
          $row[$id]['show'] = $this->verifyCreditCardMethod();
        }
      }
      elseif (($id == 'Async_TigoMoney' && !isset($row['Async_TigoMoney'])) ||
        ($id == 'Async_TigoMoney' && isset($row['Async_TigoMoney']) && $row['Async_TigoMoney']['show'] === FALSE)) {
        $row[$id] = [
          'paymentMethodName' => $field['title'],
          'label' => $field['label'],
          'url' => $field['url'],
          'type' => $field['type'],
          'show' => FALSE,
          'isRecurrent' => FALSE,
          'description' => [
            'label' => $field['description'] ?? '',
            'show' => !empty($field['description']),
          ],
          'weight' => $field['weight'],
        ];
        if ($this->myNumber) {
          if ($this->primaryNumber['info'] == 'prepaid' || $this->primaryNumber['info'] == 'hybrid') {
            $row[$id]['show'] = $this->verifyTigomoneyMethod();
          }
        }
      }
      elseif ($id == 'emergencyLoan') {
        $show_loans = (!$this->verifyIdDoubleCharge($this->offer['offerId'])) ? (bool) $field['show'] : FALSE;
        $row[$id] = [
          'paymentMethodName' => $field['title'],
          'label' => $field['label'],
          'url' => $field['url'],
          'type' => $field['type'],
          'show' => FALSE,
          'isRecurrent' => FALSE,
          'description' => [
            'label' => $field['description'] ?? '',
            'show' => !empty($field['description']),
          ],
          'weight' => $field['weight'],
        ];
        if ($this->myNumber) {
          $row[$id]['show'] = $show_loans;
        }
      }
      elseif ($id == 'qrPayment' && !isset($this->offer['additionalData']['show_only_loan_method'])) {
        $row[$id] = [
          'paymentMethodName' => $field['title'],
          'label' => $field['label'],
          'url' => $field['url'],
          'type' => $field['type'],
          'show' => !empty($field['show']) && $this->validateQrPaymentMethod(),
          'isRecurrent' => FALSE,
          'description' => [
            'label' => $field['description'] ?? '',
            'show' => !empty($field['description']),
          ],
          'weight' => $field['weight'],
        ];
      }
      elseif ($id == 'tigoQrPos' && !isset($this->offer['additionalData']['show_only_loan_method'])) {
        $row[$id] = [
          'paymentMethodName' => $field['title'],
          'label' => $field['label'],
          'url' => $field['url'],
          'type' => $field['type'],
          'show' => !empty($field['show']) && $this->validateTigoQrPosMethod($this->offer['offerId']),
          'isRecurrent' => FALSE,
          'description' => [
            'label' => $field['description'] ?? '',
            'show' => !empty($field['description']),
          ],
          'weight' => $field['weight'],
        ];
        if ($field['type'] == 'webcomponent') {
          $row[$id]['scriptUrl'] = $field['scriptUrl'] ?? '';
          $row[$id]['tagHtml'] = $field['tagHtml'] ?? '';
        }
      }
    }

    /* Adicionalmente se recorren nuevas configuraciones ubicadas en el formulario de oneapp mobile
     para agregar métodos de pago dinámicamente. */
    foreach ($this->methods['fields'] as $id => $field) {
      if (isset($row[$field['machine_name_target']])) {
        if (!isset($row[$field['machine_name_target']]['description'])) {
          $row[$field['machine_name_target']]['description'] = [
            'label' => $field['description'],
            'show' => !empty($field['description']),
          ];
        }
        if (!isset($row[$field['machine_name_target']]['weight'])) {
          $row[$field['machine_name_target']]['weight'] = $field['weight'];
        }
      }
      // Si el método no existe en el bloque de configuración, lo agrega al response.
      if (!isset($row[$field['machine_name_target']])) {
        $row[$field['machine_name_target']] = [
          'paymentMethodName' => $field['machine_name_target'],
          'label' => $field['label'],
          'description' => [
            'label' => $field['description'],
            'show' => !empty($field['description']),
          ],
          'url' => $field['url'],
          'type' => $field['type'],
          'show' =>  $field['show'],
          'weight' =>  $field['weight'],
        ];
        $row[$field['machine_name_target']]['show'] = $field['show'] ? $this->isMethodAllowed($field) : FALSE;
      }
    }
    $rows[0] = $row;
    uasort($rows[0], ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    $actions['paymentMethods'] = $rows;
    return [
      'actions' => $actions,
      'dataconfig' => $dataconfig,
    ];
  }

  /**
   * Get payment methods for autopackets.
   */
  public function getPaymentMethodsAutoPacksBo() {
    $data_config = [];
    foreach ($this->configAutopack["paymentMethods"]['fields'] as $key => $value) {
      $payment_method_show = (bool) $value['show'];
      $show = FALSE;
      switch ($this->primaryNumber['info']) {
        case 'prepaid':
          if ($payment_method_show && (bool) $value["show_prepaid"]) {
            if ($value['machine_name_target'] == 'coreBalance') {
              $show = $this->verifyCoreBalanceMethod();
            }
            elseif ($value['machine_name_target'] == 'creditCard') {
              $show = $this->verifyCreditCardMethod();
            }
            else {
              $show = TRUE;
            }
          }
          break;

        case 'postpaid':
          $show = FALSE;
          break;

        case 'hybrid':
          if ($payment_method_show && (bool) $value["show_hybrid"]) {
            if ($value['machine_name_target'] == 'coreBalance') {
              $show = $this->verifyCoreBalanceMethod();
            }
            elseif ($value['machine_name_target'] == 'creditCard') {
              $show = $this->verifyCreditCardMethod();
            }
            elseif ($value['machine_name_target'] == 'Async_TigoMoney') {
              $show = $this->verifyTigomoneyMethod();
            }
            else {
              $show = TRUE;
            }
          }
          break;
      }

      $data_config[$value['machine_name_target']] = [
        "paymentMethodName" => $value['machine_name_target'],
        "label" => $value['label'],
        "url" => "/",
        "type" => "button",
        "show"  => $show,
        'isRecurrent' => ($show) ? TRUE : FALSE,
        'weight' => $value['weight'],
      ];
      if ($show) {
        if ($value['machine_name_target'] == 'coreBalance') {
          $row[$value['machine_name_target']]['description'] = $this->getDescriptionResponse();
          $row[$value['machine_name_target']]['confirmation'] = $this->confirmationCoreBalanceResponseBo(
            $row[$value['machine_name_target']]['description']);
          $data_config[$value['machine_name_target']]['description'] =
            $row[$value['machine_name_target']]['description'];
          $data_config[$value['machine_name_target']]['confirmation'] =
            $row[$value['machine_name_target']]['confirmation'];
        }
        if ($value['machine_name_target'] == 'creditCard') {
          $data_config[$value['machine_name_target']]['description'] = [
            'label' => $this->configAutopack["paymentMethods"]['description']['label'],
            'show' => (bool) $this->configAutopack["paymentMethods"]['description']['show'],
          ];
        }
      }
    }

    if (empty($data_config)) {
      $data_config = [];
    }
    return $data_config;
  }

  /**
   * @inheritDoc
   */
  public function verifyCoreBalanceMethod() {
    $show_corebalance = (bool) $this->configBlock['config']['actions']['coreBalance']['show'];
    $acquisition_method = $this->offer['additionalData']['acquisitionMethods'];
    foreach ($acquisition_method as $methodValue) {
      if ($methodValue['id'] == 1 && $show_corebalance) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function verifyTigomoneyMethod() {
    $show_tigomoney = (bool) $this->configBlock['config']['actions']['Async_TigoMoney']['show'];
    $acquisition_method = $this->offer['additionalData']['acquisitionMethods'];
    foreach ($acquisition_method as $methodValue) {
      if ($methodValue['id'] == 3 && $show_tigomoney) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function verifyCreditCardMethod() {
    $field = $this->configBlock['config']['actions']['creditCard'];
    $price = (float) $this->offer['cost'][0]['amount'];
    $amount_config = \Drupal::config('oneapp_mobile.config')->get('cardPayment_from');
    $min_price = intval($amount_config['min']);
    $show_max_price = (bool) $amount_config['show'];
    if ($show_max_price === TRUE) {
      $max_price = intval($amount_config['max']);
      $show_credit = ($price >= $min_price && $price <= $max_price) ? (bool) $field['show'] : FALSE;
    }
    else {
      $show_credit = ($price >= $min_price) ? (bool) $field['show'] : FALSE;
    }
    $acquisition_method = $this->offer['additionalData']['acquisitionMethods'];
    foreach ($acquisition_method as $methodValue) {
      if ($methodValue['id'] == 4 && $show_credit) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function getDescriptionResponse() {
    $price = (float) $this->offer['cost'][0]['amount'];
    $tu_saldo = $this->configBlock['config']['actions']['coreBalanceSumary'];
    $row = [
      'label' => $tu_saldo['title'],
      'formattedValue' => $this->balance['formattedValue'],
      'show' => (bool)$tu_saldo['show'],
    ];
    $row['value'] = [
      'amount' => $this->balance['value'],
      'currencyId' => $this->balance['currencyId'],
    ];
    $row['validForPurchase'] = ($this->balance['value'] === '') ? TRUE : $this->balance['value'] >= $price;
    return $row;
  }

  /**
   * @inheritDoc
   */
  public function confirmationCoreBalanceResponseBo($description) {
    $formatted_price = $this->utils->formatCurrency($this->offer['cost'][0]['amount'], TRUE, TRUE);
    $message_success = $this->configBlock['config']['messages']['verifyCoreBalance'];
    $message_failure = $this->configBlock['config']['messages']['package_error'];
    if ($description['validForPurchase']) {
      $message = str_replace('@amount', $formatted_price, $message_success['label']);
      $show = (bool) $message_success['show'];
      $see_packages = FALSE;
    } else {
      $message = $message_failure['label'];
      $show = (bool) $message_failure['show'];
      $see_packages = TRUE;
      $actions['rechargeMessage']['show'] = TRUE;
    }
    $row['message'] = [
      'label' => $message,
      'show' => $show,
    ];
    $row['orderDetailsTitle'] = [
      'label' => $this->configBlock['config']['response']['coreBalanceVerify']['coreBalanceVerify']['label'],
      'show' => (bool) $this->configBlock['config']['response']['coreBalanceVerify']['coreBalanceVerify']['show'],
    ];
    $row['paymentMethodsTitle'] = [
      'label' => $this->configBlock['config']['response']['coreBalanceVerify']['paymentMethodTitle']['label'],
      'show' => (bool) $this->configBlock['config']['response']['coreBalanceVerify']['paymentMethodTitle']['show'],
    ];
    $row['paymentMethod'] = [
      'label' => $this->configBlock['config']['response']['coreBalanceVerify']['paymentMethod']['label'],
      'show' => (bool) $this->configBlock['config']['response']['coreBalanceVerify']['paymentMethod']['show'],
      "formattedValue" => $this->configBlock['config']['response']['coreBalanceVerify']['paymentMethod']['value'],
    ];
    $row['coreBalancePayment'] = [
      'label' => $this->configBlock['config']['response']['coreBalanceVerify']['coreBalance']['label'],
      'show' => (bool) $this->configBlock['config']['response']['coreBalanceVerify']['coreBalance']['show'],
    ];
    $row['actions']['change'] = [
      'label' => $this->configBlock['config']['response']['coreBalanceVerify']['changeButtons']['label'],
      'url' => $this->configBlock['config']['response']['coreBalanceVerify']['changeButtons']['url'],
      'type' => $this->configBlock['config']['response']['coreBalanceVerify']['changeButtons']['type'],
      'show' => (bool) $this->configBlock['config']['response']['coreBalanceVerify']['changeButtons']['show'],
    ];
    $row['actions']['cancel'] = [
      'label' => $this->configBlock['config']['response']['coreBalanceVerify']['cancelButtons']['label'],
      'url' => $this->configBlock['config']['response']['coreBalanceVerify']['cancelButtons']['url'],
      'type' => $this->configBlock['config']['response']['coreBalanceVerify']['cancelButtons']['type'],
      'show' => (bool) $this->configBlock['config']['response']['coreBalanceVerify']['cancelButtons']['show'],
    ];
    $row['actions']['purchase'] = [
      'label' => $this->configBlock['config']['response']['coreBalanceVerify']['purchaseButtons']['label'],
      'url' => $this->configBlock['config']['response']['coreBalanceVerify']['purchaseButtons']['url'],
      'type' => $this->configBlock['config']['response']['coreBalanceVerify']['purchaseButtons']['type'],
      'show' => ($see_packages) ? FALSE :
        (bool) $this->configBlock['config']['response']['coreBalanceVerify']['purchaseButtons']['show'],
    ];
    $row['actions']['seePackages'] = [
      'label' => $this->configBlock['config']['response']['coreBalanceVerify']['seePackages']['label'],
      'url' => $this->configBlock['config']['response']['coreBalanceVerify']['seePackages']['url'],
      'type' => $this->configBlock['config']['response']['coreBalanceVerify']['seePackages']['type'],
      'show' => ($see_packages) ?
        (bool) $this->configBlock['config']['response']['coreBalanceVerify']['seePackages']['show'] : FALSE,
    ];
    $verify = $this->configBlock['config']['response']['coreBalanceVerify'];
    $row['actions']['termsOfServices'] = [
      'label' => $verify['termsAndConditions']['label'],
      'url' => $verify['termsAndConditions']['url'],
      'type' => $verify['termsAndConditions']['type'],
      'show' => (bool) $verify['termsAndConditions']['show'],
    ];
    return $row;
  }

  /**
   * Order the payment methods.
   */
  public function orderPaymentMethods($rows) {
    uasort($rows[0], ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    return $rows;
  }

  /**
   * inheritDoc.
   */
  protected function translatePlantype($plan_info) {
    $plans_search = ['HIB', 'POS', 'PRE'];
    $plans_replace = ['hybrid', 'postpaid', 'prepaid'];
    return str_replace($plans_search, $plans_replace, $plan_info);
  }

  /**
   * inheritDoc.
   */
  protected function isTypeClientAllowed($account_info) {
    $array = [];
    $this->configAutopack = \Drupal::config("oneapp.payment_gateway.mobile_autopackets.config")->get("orderDetails");
    $mobile_config_autopack = \Drupal::config("oneapp_mobile.config")->get("autopackets");
    if ($this->configAutopack != NULL && $mobile_config_autopack != NULL) {
      switch ($account_info) {
        case 'prepaid':
          if ($mobile_config_autopack['autopackets_plan_types']['prepaid']) {
            $array = ['prepaid'];
          }
          break;

        case 'hybrid':
          if ($mobile_config_autopack['autopackets_plan_types']['hybrid']) {
            $array = ['hybrid'];
          }
          break;

        case 'postpaid':
          if ($mobile_config_autopack['autopackets_plan_types']['postpaid']) {
            $array = ['postpaid'];
          }
          break;

        default:
          $array = [];
          break;
      }
      $is_allowed = in_array($account_info, $array);
      if ($is_allowed && is_array($this->configAutopack['paymentMethods']['fields'])) {
        foreach ($this->configAutopack['paymentMethods']['fields'] as $methods) {
          if ($methods['show'] == 1 && $methods["show_" . $this->primaryNumber['info']] == 1) {
            return $is_allowed;
          }
        }
      }
    }
    return FALSE;
  }

}
