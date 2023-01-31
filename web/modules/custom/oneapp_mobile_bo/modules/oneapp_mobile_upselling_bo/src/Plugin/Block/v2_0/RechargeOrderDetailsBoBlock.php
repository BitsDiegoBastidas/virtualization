<?php

namespace Drupal\oneapp_mobile_upselling_bo\Plugin\Block\v2_0;

use Drupal\Core\Form\FormStateInterface;
use Drupal\oneapp_mobile_upselling\Plugin\Block\v2_0\RechargeOrderDetailsBlock;

/**
 * Class RechargeOrderDetailsBoBlock.
 */
class RechargeOrderDetailsBoBlock extends RechargeOrderDetailsBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $this->actionsRoles = [
      'changeMsisdn',
      'creditCard',
      'Async_TigoMoney',
      'Loan_Balance',
      'invoiceChargeSecurity',
    ];

    $this->contentFields = [
      'amount' => [
        'amount' => [
          'field' => $this->t('Monto'),
          'title' => $this->t('Valor'),
          'label' => '',
          'show' => TRUE,
        ],
      ],
      'msisdn' => [
        'msisdn' => [
          'field' => $this->t('MSISDN'),
          'title' => $this->t('Número de línea'),
          'label' => '',
          'show' => TRUE,
        ],
      ],
      'type' => [
        'type' => [
          'field' => $this->t('Tipo de acción: Recarga, Paquetes u otros'),
          'title' => $this->t('Detalle compra:'),
          'label' => $this->t('Recarga de saldo'),
          'show' => TRUE,
        ],
      ],
      'templateId' => [
        'type' => [
          'field' => $this->t('Selecciona el id de plantilla a mostrar'),
          'title' => $this->t('Id Plantilla'),
          'type' => '',
          'show' => TRUE,
        ],
      ],
      'buttons' => [
        'changeMsisdn' => [
          'title' => $this->t('Label Cambiar Línea'),
          'label' => $this->t('Cambiar Línea'),
          'url' => '/',
          'type' => 'button',
          'show' => TRUE,
        ],
        'creditCard' => [
          'title' => $this->t('creditCard'),
          'label' => $this->t('Tarjeta de Crédito/Débito'),
          'description' => $this->t('Recibe de REGALO el % más de cŕedito'),
          'url' => '/',
          'type' => 'button',
          'show' => TRUE,
          'weight' => 1,
        ],
        'qrPayment' => [
          'title' => $this->t('qrPayment'),
          'label' => $this->t('Simple'),
          'url' => '/',
          'type' => 'button',
          'show' => TRUE,
          'weight' => 2,
        ],
        'Async_TigoMoney' => [
          'title' => $this->t('Async_TigoMoney'),
          'label' => $this->t('TigoMoney'),
          'url' => '/',
          'type' => 'link',
          'show' => TRUE,
          'weight' => 3,
        ],
        'Loan_Balance' => [
          'title' => $this->t('Loan_Balance'),
          'label' => $this->t('Tigo te presta saldo'),
          'url' => '/',
          'type' => 'link',
          'show' => TRUE,
          'weight' => 4,
        ],
        'invoiceChargeSecurity' => [
          'title' => $this->t('invoiceChargeSecurity'),
          'label' => $this->t('Cargo a Factura'),
          'url' => '/',
          'type' => 'link',
          'show' => TRUE,
          'weight' => 5,
        ],
      ],
      'actions_roles' => [
        'changeMsisdn',
        'creditCard',
        'qrPayment',
        'Async_TigoMoney',
        'Loan_Balance',
        'invoiceChargeSecurity',
      ], 

      'messages' => [
        'recharge_error' => [
          'title' => $this->t('Recarga Inválida'),
          'label' => $this->t('El número que ingresaste no aplica para realizar la recarga. Por favor intenta con otro número.'),
          'show' => TRUE,
        ],
        'recharge_success' => [
          'title' => $this->t('Recarga Exitosa'),
          'label' => $this->t('Ahora tu nuevo número para recarga es el '),
          'show' => TRUE,
        ],
        'number_error' => [
          'title' => $this->t('Numero Inválido'),
          'label' => $this->t('El número que ingresaste no es un número Tigo. Por favor inténtelo de nuevo.'),
          'show' => TRUE,
        ],
        'monto_error' => [
          'title' => $this->t('Monto Inválido'),
          'label' => $this->t('El monto a recargar debe ser mayor o igual a @minAmount'),
          'show' => TRUE,
        ],
        'monto_max_error' => [
          'title' => $this->t('Monto Máximo Inválido'),
          'label' => $this->t('El monto a recargar no debe ser mayor de @maxAmount'),
          'show' => TRUE,
        ],
        'facturas_error' => [
          'title' => $this->t('Número de Fácturas inválida para recargar con Cargo a Factura (>=)'),
          'label' => '2',
          'show' => TRUE,
        ],
        'verifyinvoiceCharge' => [
          'title' => $this->t('Mensaje Pantalla de Verificación metodo de pago "Cargo a factura"'),
          'label' => $this->t('Se cargarán @amount en tu siguiente factura'),
          'show' => TRUE,
        ],
      ],
      'invoiceChargeVerify' => [
        'title' => [
          'label' => $this->t('Resumen'),
          'value' => '',
          'show' => 1,
        ],
        'invoiceChargeVerify' => [
          'label' => $this->t('Datos de pago:'),
          'value' => '',
          'show' => 1,
        ],
        'productType' => [
          'label' => $this->t('Tipo de producto:'),
          'value' => '',
          'show' => 1,
        ],
        'paymentMethodTitle' => [
          'label' => $this->t('Forma de pago:'),
          'value' => '',
          'show' => 1,
        ],
        'paymentMethod' => [
          'label' => $this->t('Método de pago:'),
          'value' => $this->t('Cargo a factura'),
          'show' => 1,
        ],
        'cancelButtons' => [
          'label' => $this->t('CANCELAR'),
          'value' => '',
          'type' => 'link',
          'url' => '/',
          'show' => 1,
        ],
        'purchaseButtons' => [
          'label' => $this->t('COMPRAR'),
          'value' => '',
          'type' => 'link',
          'url' => '/',
          'show' => 1,
        ],
        'termsAndConditions' => [
          'label' => $this->t('Al presionar COMPRAR estás aceptando los términos y condiciones.'),
          'value' => '',
          'type' => 'link',
          'url' => '/',
          'show' => 1,
        ],
      ],
    ];
    if (!empty($this->adfDefaultConfiguration())) {
      return $this->adfDefaultConfiguration();
    }
    else {
      return $this->contentFields;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adfBlockForm($form, FormStateInterface $form_state) {
    $this->addAmountTable($form);
    $this->addFieldsMsisdnTable($form);
    $this->addFieldsTypeTable($form);
    $this->addFieldsTypeSelect($form);
    $this->addFieldsButtonsTable($form);
    $this->configActionsRoles($form);
    $this->configMessageFields($form);
    $this->addFieldsInvoiceChargeVerify($form);
    $this->configOthers($form);

    return $form;
  }

  /**
   * Msisdn configurations.
   *
   * @param array $form
   *   Form to add configuration.
   */
  public function addFieldsMsisdnTable(array &$form) {
    $msisdn = $this->configuration['msisdn'];

    $form['msisdn'] = [
      '#type' => 'details',
      '#title' => $this->t('Msisdn'),
      '#open' => FALSE,
    ];

    $form['msisdn']['properties'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('Titulo'),
        $this->t('label'),
        $this->t('Show'),
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
    ];

    foreach ($msisdn as $id => $entity) {
      $form['msisdn']['properties'][$id]['field'] = [
        '#type' => 'hidden',
        '#default_value' => $entity['field'],
        '#suffix' => $entity['field'],
        '#size' => 20,
      ];

      $form['msisdn']['properties'][$id]['title'] = [
        '#type' => 'textfield',
        '#default_value' => $entity['title'],
        '#size' => 20,
      ];

      $form['msisdn']['properties'][$id]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $entity['label'],
        '#size' => 20,
        '#pattern' => "^[0-9]{8}$",
        '#minlength' => 8,
        '#maxlength' => 8,
      ];

      $form['msisdn']['properties'][$id]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => $entity['show'],
      ];
    }
  }

  /**
   * Msisdn configurations.
   *
   * @param array $form
   *   Form to add configuration.
   */
  public function addFieldsTypeSelect(array &$form) {
    $msisdn = $this->configuration['templateId'];

    $form['templateId'] = [
      '#type' => 'details',
      '#title' => $this->t('Plantillas OTP'),
      '#open' => FALSE,
    ];

    $form['templateId']['properties'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('Templates'),
        $this->t('Show'),
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
    ];
    $templates_otp = \Drupal::config('oneapp_mobile.otp.config')->get('templates');
    $template_list = [];
    foreach ($templates_otp as $template) {
      $template_list[] = $template['templateId'];
    }
    foreach ($msisdn as $id => $entity) {
      $form['templateId']['properties'][$id]['field'] = [
        '#type' => 'hidden',
        '#default_value' => $entity['field'],
        '#suffix' => $entity['field'],
        '#size' => 20,
      ];

      $form['templateId']['properties'][$id]['type'] = [
        '#type' => 'select',
        '#default_value' => $entity['type'],
        '#options' => $template_list,
      ];

      $form['templateId']['properties'][$id]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => $entity['show'],
      ];
    }
  }

  /**
   * Msisdn configurations.
   *
   * @param array $form
   *   Form to add configuration.
   */
  public function addFieldsTypeTable(array &$form) {
    $type = $this->configuration['type'];

    $form['type'] = [
      '#type' => 'details',
      '#title' => $this->t('Tipo de accion'),
      '#open' => FALSE,
    ];

    $form['type']['properties'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('Titulo'),
        $this->t('label'),
        $this->t('Show'),
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
    ];

    foreach ($type as $id => $entity) {
      $form['type']['properties'][$id]['field'] = [
        '#type' => 'hidden',
        '#default_value' => $entity['field'],
        '#suffix' => $entity['field'],
        '#size' => 20,
      ];

      $form['type']['properties'][$id]['title'] = [
        '#type' => 'textfield',
        '#default_value' => $entity['title'],
        '#size' => 20,
      ];

      $form['type']['properties'][$id]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $entity['label'],
        '#size' => 20,
      ];

      $form['type']['properties'][$id]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => $entity['show'],
      ];
    }
  }

  /**
   * Fields configurations.
   *
   * @param array $form
   *   Form to add configuration.
   */
  public function addAmountTable(array &$form) {
    $amount = $this->configuration['amount'];

    $form['amount'] = [
      '#type' => 'details',
      '#title' => $this->t('Monto o Valor'),
      '#open' => FALSE,
    ];

    $form['amount']['properties'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('Titulo'),
        $this->t('label'),
        $this->t('Show'),
        '',
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
    ];

    foreach ($amount as $id => $entity) {
      $form['amount']['properties'][$id]['field'] = [
        '#type' => 'hidden',
        '#default_value' => $entity['field'],
        '#suffix' => $entity['field'],
        '#size' => 20,
      ];

      $form['amount']['properties'][$id]['title'] = [
        '#type' => 'textfield',
        '#default_value' => $entity['title'],
        '#size' => 20,
      ];

      $form['amount']['properties'][$id]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $entity['label'],
        '#size' => 20,
      ];

      $form['amount']['properties'][$id]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => $entity['show'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function configMessageFields(&$form) {
    $messages = $this->configuration['messages'];

    $form['messages'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuraciones adicionales y mensajes'),
      '#open' => FALSE,
    ];

    $form['messages']['properties'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('label'),
        $this->t('Show'),
        '',
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
    ];

    foreach ($messages as $id => $entity) {
      $form['messages']['properties'][$id]['title'] = [
        '#type' => 'hidden',
        '#default_value' => $entity['title'],
        '#suffix' => $entity['title'],
      ];

      $form['messages']['properties'][$id]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $entity['label'],
      ];

      $form['messages']['properties'][$id]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => $entity['show'],
      ];
    }
  }

  /**
   * Buttons configurations.
   *
   * @param array $form
   *   Form to add configuration.
   */
  public function addFieldsButtonsTable(array &$form) {
    $buttons = array_merge($this->contentFields['buttons'], $this->configuration['buttons']);

    $form['buttons'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración de botones y Métodos de pago'),
      '#open' => FALSE,
    ];

    $form['buttons']['properties'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('label'),
        $this->t('description'),
        $this->t('Url'),
        $this->t('Type'),
        $this->t('Show'),
        $this->t('Weight'),
        '',
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
    ];

    foreach ($buttons as $id => $entity) {
      if ($id == 'invoiceChargeSecurity' || $id == 'creditCard' || $id == 'Async_TigoMoney' || $id == 'Loan_Balance'
        || $id == 'qrPayment') {
        $form['buttons']['properties'][$id]['title'] = [
          '#type' => 'textfield',
          '#default_value' => $entity['title'],
          '#suffix' => "Métodos de pago",
        ];
      }
      else {
        $form['buttons']['properties'][$id]['title'] = [
          '#type' => 'hidden',
          '#default_value' => $entity['title'],
          '#suffix' => $entity['title'],
        ];
      }

      $form['buttons']['properties'][$id]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $entity['label'],
        '#size' => 20,
      ];

      $form['buttons']['properties'][$id]['description'] = [
        '#type' => 'textfield',
        '#default_value' => $entity['description'] ?? '',
        '#size' => 20,
      ];

      if (isset($entity['url'])) {
        $form['buttons']['properties'][$id]['url'] = [
          '#type' => 'textfield',
          '#default_value' => $entity['url'],
          '#size' => 20,
        ];
      }
      else {
        $form['buttons']['properties'][$id]['url'] = [];
      }

      $form['buttons']['properties'][$id]['type'] = [
        '#type' => 'select',
        '#default_value' => $entity['type'],
        '#options' => ['button' => 'Button', 'link' => 'Link'],
      ];

      $form['buttons']['properties'][$id]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => $entity['show'],
      ];
      if (isset($entity['weight'])) {
        $weight = ($entity['weight'] != NULL) ? $entity['weight'] : 10;
        $form['buttons']['properties'][$id]['weight'] = [
          '#type' => 'weight',
          '#delta' => 10,
          '#default_value' => $weight,
        ];
      }
      else {
        $form['buttons']['properties'][$id]['weight'] = [
          '#plain_text' => $this->t('No apply'),
        ];
      }
    }

  }

  /**
   * Buttons configurations.
   *
   * @param array $form
   *   Form to add configuration.
   */
  public function addFieldsInvoiceChargeVerify(array &$form) {
    $invoice_charge_verify = $this->configuration['invoiceChargeVerify'];

    $form['invoiceChargeVerify'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración pantalla de confirmación método pago Cargo a factura'),
      '#open' => FALSE,
    ];

    $form['invoiceChargeVerify']['properties'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('label'),
        $this->t('Value'),
        $this->t('Show'),
        $this->t('Url'),
        $this->t('Type'),
        '',
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
    ];
    foreach ($invoice_charge_verify as $id => $entity) {
      $form['invoiceChargeVerify']['properties'][$id]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $entity['label'],
        '#size' => 50,
      ];
      $form['invoiceChargeVerify']['properties'][$id]['value'] = [
        '#type' => 'hidden',
        '#default_value' => $entity['value'],
        '#size' => 15,
      ];
      if ($id == 'paymentMethod') {
        $form['invoiceChargeVerify']['properties'][$id]['value'] = [
          '#type' => 'textfield',
          '#default_value' => $entity['value'],
          '#size' => 15,
        ];
      }
      $form['invoiceChargeVerify']['properties'][$id]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => $entity['show'],
      ];
      if (isset($entity['url'])) {
        $form['invoiceChargeVerify']['properties'][$id]['url'] = [
          '#type' => 'textfield',
          '#default_value' => $entity['url'],
        ];
      }
      else {
        $form['invoiceChargeVerify']['properties'][$id]['url'] = [];
      }
      if (isset($entity['type'])) {
        $form['invoiceChargeVerify']['properties'][$id]['type'] = [
          '#type' => 'select',
          '#default_value' => $entity['type'],
          '#options' => ['button' => 'Button', 'link' => 'Link'],
        ];
      }
      else {
        $form['invoiceChargeVerify']['properties'][$id]['type'] = [];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adfBlockSubmit($form, FormStateInterface $form_state) {
    parent::adfBlockSubmit($form, $form_state);
    $this->configuration['messages'] = array_merge($this->configuration['messages'], $form_state->getValue(['messages', 'properties']));
    $this->configuration['buttons'] = array_merge($this->configuration['buttons'], $form_state->getValue(['buttons', 'properties']));
    $this->configuration['msisdn'] = array_merge($this->configuration['msisdn'], $form_state->getValue(['msisdn', 'properties']));
    $this->configuration['amount'] = array_merge($this->configuration['amount'], $form_state->getValue(['amount', 'properties']));
    $this->configuration['type'] = array_merge($this->configuration['type'], $form_state->getValue(['type', 'properties']));
    $this->configuration['templateId'] = array_merge($this->configuration['templateId'],
      $form_state->getValue(['templateId', 'properties']));
    $this->configuration['invoiceChargeVerify'] = array_merge($this->configuration['invoiceChargeVerify'],
      $form_state->getValue(['invoiceChargeVerify', 'properties']));
    $this->configuration['actions_roles'] = $form_state->getValue('actions_roles');
  }

}
