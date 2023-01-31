<?php

namespace Drupal\oneapp_mobile_upselling_bo\Services\v2_0;

use Drupal\oneapp\Exception\HttpException;
use Drupal\oneapp_mobile_upselling\Services\v2_0\RechargeOrderDetailsRestLogic;

/**
 * Class RechargeOrderDetailsRestLogicBo.
 */
class RechargeOrderDetailsRestLogicBo extends RechargeOrderDetailsRestLogic {

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
  protected $amountConfig;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $rechargeAmount;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $invalidRecharge;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $templateOtp;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $invalidMsisdn;

  /**
   * Responds to GET requests.
   *
   * @param string $msisdn
   *   Msisdn.
   * @param int $amount
   *   Amount.
   * @param bool $is_same
   *   Is same msisdn to target.
   * @param bool $account_id
   *   Is msisdn origin.
   *
   * @return mixed
   *   mixed
   *
   * @throws \ReflectionException
   */
  public function get($msisdn, $amount, $is_same = FALSE, $account_id = FALSE) {
    $this->invalidMsisdn = FALSE;
    $this->invalidRecharge = FALSE;
    $current = $account_id;
    $this->rechargeAmount = $amount;
    $this->amountConfig = \Drupal::config('oneapp_mobile.config')->get('recharge_amounts_dimensions');
    $this->primaryNumber['accountId'] = $account_id;
    $this->primaryNumber['isConvergent'] = FALSE;
    $info = $this->getMasterAccountRecord($account_id);
    $this->primaryNumber['info'] = ($info != FALSE) ? $this->getTypeLine($info, $account_id) : FALSE;
    if ($is_same) {
      try {
        $this->getPaymentMethods();
      }
      catch (HttpException $exception) {
        $this->invalidMsisdn = TRUE;
        $this->primaryNumber['info'] = '';
      }
    }
    else {
      try {
        $current = $msisdn;
        $info = $this->getMasterAccountRecord($msisdn);
        $this->targetNumber['info'] = ($info != FALSE) ? $this->getTypeLine($info, $msisdn) : FALSE;
        $this->targetNumber['accountId'] = $msisdn;
        if ($this->targetNumber['info']) {
          $this->getPaymentMethodsForGift();
        }
      }
      catch (HttpException $exception) {
        $this->invalidMsisdn = TRUE;
        $this->primaryNumber['info'] = '';
      }
    }
    $errors = $this->getErrors($this->invalidMsisdn);
    $config = $this->configResponse($amount, $errors);
    return [
      'data' => $this->getData($current, $amount),
      'config' => $config,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getTemplateOtp() {
    $config_templates_otp = \Drupal::config('oneapp_mobile.otp.config')->get('templates');
    $template_list = [];
    foreach ($config_templates_otp as $template) {
      $template_list[] = $template['templateId'];
    }
    $template_id = $this->configBlock['templateId']['type']['type'];
    $this->templateOtp = $template_list[$template_id];
  }

  /**
   * {@inheritdoc}
   */
  public function configResponse($amount, $errors) {
    $actions = [];
    $rows = [];
    $row = [];
    $config = $this->configBlock['buttons'];
    $min = intval($this->amountConfig['min']);
    $change_msisdn_amount = intval($this->amountConfig['changeMsisdn']);
    $this->getTemplateOtp();

    // Se recorren las configuraciones del bloque de configuración.
    foreach ($config as $id => $field) {
      if ($id == 'changeMsisdn') {
        $actions[$id] = [
          'label' => $field['label'],
          'url' => $field['url'],
          'type' => $field['type'],
          'show' => (bool) $field['show'],
        ];
        if ($amount >= $min && $amount < $change_msisdn_amount) {
          $actions['changeMsisdn']['show'] = FALSE;
        }
        if ($errors['error'] === TRUE) {
          $rows = $errors['rows'];
          break;
        }
      }
      else {
        $row[$id] = [
          'paymentMethodName' => $field['title'],
          'label' => $field['label'],
          'description' => [
            'label' => $field['description'],
            'show' => !empty($field['description']),
          ],
          'url' => $field['url'],
          'type' => $field['type'],
          'show' => FALSE,
          'weight' => $field['weight'],
        ];
        foreach ($this->primaryNumber['paymentMethods'] as $item) {
          if ($id === $item[0]) {
            $row[$id]['show'] = (bool) $field['show'];
            if ($id === 'invoiceChargeSecurity') {
              $row[$id]['confirmation'] = $this->invoiceChargeVerify($amount);
            }
          }
        }
      }
    }

    /* Adicionalmente se recorren nuevas configuraciones ubicadas en el formulario de oneapp mobile
    para agregar métodos de pago dinámicamente, así como peso y descripción a los métodos que en el
    bloque de configuracián no lo tenían.*/
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
      'otpTemplateId' => $this->templateOtp,
      'actions' => $actions,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function invoiceChargeVerify($amount) {
    $row = [];
    $format_currency_local = $this->utils->formatCurrency($amount, TRUE, TRUE);
    $message_success = $this->configBlock['messages']['verifyinvoiceCharge'];
    $message = str_replace('@amount', $format_currency_local, $message_success['label']);
    $show = (bool) $message_success['show'];

    $verify = $this->configBlock['invoiceChargeVerify'];
    $row['confirmationTitle'] = [
      'label' => $verify['title']['label'],
      'show' => (bool) $verify['title']['show'],
    ];
    $row['message'] = [
      'label' => $message,
      'show' => $show,
    ];
    $row['orderDetailsTitle'] = [
      'label' => $verify['invoiceChargeVerify']['label'],
      'show' => (bool) $verify['invoiceChargeVerify']['show'],
    ];
    $row['productType'] = [
      'label' => $verify['productType']['label'],
      'show' => (bool) $verify['productType']['show'],
    ];
    $row['paymentMethodTitle'] = [
      'label' => $verify['paymentMethodTitle']['label'],
      'show' => (bool) $verify['paymentMethodTitle']['show'],
    ];
    $row['paymentMethod'] = [
      'label' => $verify['paymentMethod']['label'],
      "formattedValue" => $verify['paymentMethod']['value'],
      'show' => (bool) $verify['paymentMethod']['show'],
    ];
    $row['actions']['cancel'] = [
      'label' => $verify['cancelButtons']['label'],
      'url' => $verify['cancelButtons']['url'],
      'type' => $verify['cancelButtons']['type'],
      'show' => (bool) $verify['cancelButtons']['show'],
    ];
    $row['actions']['purchase'] = [
      'label' => $verify['purchaseButtons']['label'],
      'url' => $verify['purchaseButtons']['url'],
      'type' => $verify['purchaseButtons']['type'],
      'show' => (bool) $verify['purchaseButtons']['show'],
    ];
    $row['actions']['termsOfServices'] = [
      'label' => $verify['termsAndConditions']['label'],
      'url' => $verify['termsAndConditions']['url'],
      'type' => $verify['termsAndConditions']['type'],
      'show' => (bool) $verify['termsAndConditions']['show'],
    ];
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors($invalid_msisdn) {
    $max = intval($this->amountConfig['maxCredit']);
    $min = intval($this->amountConfig['min']);
    return $this->error($this->rechargeAmount, $min, $max, $this->invalidRecharge, $invalid_msisdn);
  }

  /**
   * {@inheritdoc}
   */
  protected function error($amount, $min, $max, $invalid_recharge = FALSE, $invalid_msisdn = FALSE) {
    $error = FALSE;
    $rows = [];
    $messages = $this->configBlock['messages'];
    if ($amount < $min) {
      $value = $this->utils->formatCurrency($min, TRUE, TRUE);
      $row = [
        'value' => str_replace('@minAmount', $value, $messages['monto_error']['label']),
        'show' => (bool) $messages['monto_error']['show'],
      ];
      $error = TRUE;
      $rows = $row;
    }
    if ($amount > $max) {
      $value = $this->utils->formatCurrency($max, TRUE, TRUE);
      $row = [
        'value' => str_replace('@maxAmount', $value, $messages['monto_max_error']['label']),
        'show' => (bool) $messages['monto_max_error']['show'],
      ];
      $error = TRUE;
      $rows = $row;
    }
    if ($invalid_recharge === TRUE) {
      $row = [
        'value' => $messages['recharge_error']['label'],
        'show' => (bool) $messages['monto_max_error']['show'],
      ];
      $error = TRUE;
      $rows = $row;
    }
    if ($invalid_msisdn === TRUE) {
      $row = [
        'value' => $messages['number_error']['label'],
        'show' => (bool) $messages['monto_max_error']['show'],
      ];
      $error = TRUE;
      $rows = $row;
    }
    return [
      'rows' => $rows,
      'error' => $error,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethods() {
    switch ($this->primaryNumber['info']) {
      case 'prepaid':
        if ($this->verifyCreditCard()) {
          $this->primaryNumber['paymentMethods'][] = [
            'creditCard',
          ];
        }
        if ($this->getLoanBalance($this->primaryNumber['accountId'])) {
          $this->primaryNumber['paymentMethods'][] = [
            'Loan_Balance',
          ];
        }
        $this->primaryNumber['paymentMethods'][] = [
          'Async_TigoMoney',
        ];
        break;

      case 'postpaid':
        $this->primaryNumber['paymentMethods'] = [];
        $this->invalidRecharge = TRUE;
        break;

      case 'control':
      case 'hybrid':
        if ($this->verifyCreditCard()) {
          $this->primaryNumber['paymentMethods'][] = [
            'creditCard',
          ];
        }
        if ($this->verifyInvoiceCharge() && !$this->primaryNumber['isConvergent']) {
          $this->primaryNumber['paymentMethods'][] = [
            'invoiceChargeSecurity',
          ];
        }
        if ($this->getLoanBalance($this->primaryNumber['accountId'])) {
          $this->primaryNumber['paymentMethods'][] = [
            'Loan_Balance',
          ];
        }
        $this->primaryNumber['paymentMethods'][] = [
          'Async_TigoMoney',
        ];
        break;
    }

    if ($this->validateQrPaymentMethod($this->rechargeAmount, $this->primaryNumber['info'])) {
      $this->primaryNumber['paymentMethods'][] = [
        'qrPayment',
      ];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethodsForGift() {

    switch ($this->primaryNumber['info']) {
      case 'prepaid':
      case 'postpaid':
        if ($this->targetNumber['info'] === "prepaid" ||
          $this->targetNumber['info'] === "control" || $this->targetNumber['info'] === "hybrid") {
          $this->primaryNumber['paymentMethods'][] = [
            'Async_TigoMoney',
          ];
          if ($this->verifyCreditCard()) {
            $this->primaryNumber['paymentMethods'][] = [
              'creditCard',
            ];
          }
        }
        else {
          $this->invalidRecharge = TRUE;
          $this->primaryNumber['paymentMethods'] = [];
        }
        break;

      case 'control':
      case 'hybrid':
        if ($this->targetNumber['info'] === "control" || $this->targetNumber['info'] === 'hybrid') {
          if ($this->verifyInvoiceCharge($this->targetNumber['accountId']) && !$this->primaryNumber['isConvergent']) {
            $this->primaryNumber['paymentMethods'][] = [
              'invoiceChargeSecurity',
            ];
          }
          $this->primaryNumber['paymentMethods'][] = [
            'Async_TigoMoney',
          ];
          if ($this->verifyCreditCard()) {
            $this->primaryNumber['paymentMethods'][] = [
              'creditCard',
            ];
          }
        }
        elseif ($this->targetNumber['info'] === "prepaid") {
          if ($this->verifyCreditCard()) {
            $this->primaryNumber['paymentMethods'][] = [
              'creditCard',
            ];
          }
        }
        else {
          $this->invalidRecharge = TRUE;
          $this->primaryNumber['paymentMethods'] = [];
        }
        break;
    }

    if (!empty($this->configBlock['buttons']['qrPayment']['show'])) {
      $this->primaryNumber['paymentMethods'][] = [
        'qrPayment',
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function verifyStatus($msisdn) {
    try {
      $account_info = $this->getMasterAccountRecord($msisdn);
      foreach ($account_info->customerAccountList as $customer_account_list) {
        foreach ($customer_account_list->accountList as $account_list) {
          foreach ($account_list->subscriptionList as $subscription_list) {
            if (isset($subscription_list->msisdnList) && $subscription_list->msisdnList != []) {
              foreach ($subscription_list->msisdnList as $msisdn_list) {
                if ($msisdn_list->msisdn == $msisdn) {
                  $this->billingType = $subscription_list->subscriptionType;
                  $status = $msisdn_list->lifecycle->status;
                  break;
                }
              }
            }
          }
        }
      }

      if (strtolower($status) === 'suspend') {
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
  public function hasPayment() {
    $invoices_not_allowed = intval($this->configBlock['messages']['facturas_error']['label']);
    try {
      $invoices = $this->callInvoicesApi($this->primaryNumber['accountId']);
    }
    catch (HttpException $exception) {
      if ($exception->getCode() === 404) {
        return TRUE;
      }
      return FALSE;
    }

    $count = 0;
    foreach ($invoices as $invoice) {
      if ($invoice->hasPayment === FALSE) {
        $count++;
      }
    }
    return ($count >= $invoices_not_allowed) ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function verifyInvoiceCharge($parameters = FALSE) {
    // Verificar si numero de facturas < 2.
    if ($this->hasPayment()) {
      try {
        // Consultar API para saber si linea es B2B o STAFF.
        $info = $this->callCustomerInfo($this->primaryNumber['accountId']);
      }
      catch (HttpException $exception) {
        $info = [];
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
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function verifyCreditCard() {
    $amount_config = \Drupal::config('oneapp_mobile.config')->get('recharge_amounts_dimensions');
    $min_credit = intval($amount_config['max']);
    $max_credit = intval($amount_config['maxCredit']);
    return ($this->rechargeAmount >= $min_credit && $this->rechargeAmount <= $max_credit) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLoanBalance($id) {
    try {
      $loan_offers_list = $this->getBalanceLoanOffers($id);
    }
    catch (HttpException $exception) {
      $loan_offers_list = FALSE;
    }

    if ($loan_offers_list != FALSE) {
      foreach ($loan_offers_list as $loan_offer) {
        $prod_name = $loan_offer->productCategory;
        $text_search = 'saldo';
        if (strpos($prod_name, $text_search) !== FALSE) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getData($msisdn, $amount) {
    $data = [];
    $data['msisdn'] = [
      'label' => $this->configBlock['msisdn']['msisdn']['title'],
      'value' => $msisdn,
      'formattedValue' => $msisdn,
      'show' => (bool) $this->configBlock['msisdn']['msisdn']['show'],
    ];
    $data['amount'] = [
      'label' => $this->configBlock['amount']['amount']['title'],
      'value' => $amount,
      'formattedValue' => $this->utils->formatCurrency($amount, TRUE, TRUE),
      'show' => (bool) $this->configBlock['amount']['amount']['show'],
    ];
    $data['detail'] = [
      'label' => $this->configBlock['type']['type']['title'],
      'formattedValue' => $this->configBlock['type']['type']['label'],
      'show' => (bool) $this->configBlock['type']['type']['show'],
    ];
    return $data;
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
  public function getTypeLine($account_info, $msisdn) {
    foreach ($account_info->customerAccountList as $customer_account_list) {
      foreach ($customer_account_list->accountList as $account_list) {
        if ($msisdn == $this->primaryNumber['accountId'] && $account_list->businessUnit == 'convergent') {
          $this->primaryNumber['isConvergent'] = TRUE;
        }
        foreach ($account_list->subscriptionList as $subscription_list) {
          if (isset($subscription_list->msisdnList) && $subscription_list->msisdnList != []) {
            foreach ($subscription_list->msisdnList as $msisdn_list) {
              if ($msisdn_list->msisdn == $msisdn) {
                return $subscription_list->subscriptionType;
              }
            }
          }
        }
      }
    }
    $this->invalidMsisdn = TRUE;
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMasterAccountRecord($id) {
    try {
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
    catch (HttpException $exception) {
      $this->invalidMsisdn = TRUE;
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBalanceLoanOffers($msisdn) {
    return $this->manager
      ->load('oneapp_mobile_v2_0_balance_loan_offers_endpoint')
      ->setHeaders([])
      ->setQuery([])
      ->setParams(['msisdn' => $msisdn])
      ->sendRequest();

  }

}
