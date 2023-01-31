<?php

namespace Drupal\oneapp_mobile_upselling_bo\Plugin\Block\v2_0;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\oneapp_mobile_upselling\Plugin\Block\v2_0\PacketsOrderDetailsBlock;

/**
 * Class PacketsOrderDetailsBoBlock.
 */
class PacketsOrderDetailsBoBlock extends PacketsOrderDetailsBlock {

  /**
   * List default configuration.
   *
   * @var mixed
   */
  protected $defaultConfig;


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $this->actionsRoles = [
      'coreBalance',
      'creditCard',
      'qrPayment',
      'tigoQrPos',
      'Loan_Packets',
      'changeMsisdn',
      'Async_TigoMoney',
      'emergencyLoan',
    ];
    $this->defaultConfig = [
      'data' => [
        'fields' => [
          'title' => [
            'title' => $this->t('Título para Pantalla de Métodos de Pago'),
            'label' => $this->t('Detalles de compra'),
            'show' => 1,
            'weight' => 1,
          ],
          'msisdn' => [
            'title' => $this->t('msisdn'),
            'label' => $this->t('Número de línea:'),
            'show' => 1,
            'weight' => 1,
          ],
          'description' => [
            'title' => $this->t('Descripción'),
            'label' => $this->t('Detalle compra:'),
            'show' => 1,
            'weight' => 2,
          ],
          'price' => [
            'title' => $this->t('Valor'),
            'label' => $this->t('Valor:'),
            'show' => 1,
            'weight' => 5,
          ],
          'period' => [
            'title' => $this->t('Vigencia'),
            'label' => $this->t('Vigencia:'),
            'show' => 1,
            'weight' => 5,
          ],
          'dateFormat' => [
            'title' => $this->t('Formato de fecha:'),
            'label' => '',
            'pattern' => '',
            'show' => 1,
            'weight' => 5,
            'type' => 'select',
          ],
        ],
      ],
      'config' => [
        'actions' => [
          'changeMsisdn' => [
            'label' => $this->t('Cambiar linea'),
            'url' => '/',
            'type' => 'button',
            'show' => TRUE,
          ],
          'fulldescription' => [
            'label' => $this->t('ver más'),
            'url' => '/',
            'type' => 'button',
            'show' => TRUE,
          ],
          'paymentMethodsTitle' => [
            'title' => $this->t('Label Escoge tu forma de pago'),
            'value' => $this->t('Escoge tu forma de pago'),
            'show' => 1,
          ],
          'coreBalance' => [
            'title' => $this->t('coreBalance'),
            'label' => $this->t('Saldo'),
            'url' => '/',
            'type' => 'link',
            'show' => 1,
            'weight' => 1,
          ],
          'coreBalanceSumary' => [
            'title' => $this->t('Tu saldo:'),
            'show' => 1,
          ],
          'creditCard' => [
            'title' => $this->t('creditCard'),
            'label' => $this->t('Tarjeta de Débito/Crédito'),
            'url' => '/',
            'type' => 'link',
            'show' => 1,
            'description' => '',
            'weight' => 2,
          ],
          'qrPayment' => [
            'title' => $this->t('qrPayment'),
            'label' => $this->t('Simple'),
            'url' => '/',
            'type' => 'link',
            'show' => 1,
            'description' => '',
            'weight' => 3,
          ],
          'tigoQrPos' => [
            'title' => $this->t('tigoQrPos'),
            'label' => $this->t('Pago QR MiTigo'),
            'url' => '/',
            'type' => 'button',
            'show' => FALSE,
            'description' => '',
            'weight' => 4,
          ],
          'Async_TigoMoney' => [
            'title' => $this->t('Async_TigoMoney'),
            'label' => $this->t('TigoMoney'),
            'url' => '/',
            'type' => 'link',
            'show' => 1,
            'description' => '',
            'weight' => 5,
          ],
          'Loan_Packets' => [
            'title' => $this->t('Loan_Packets'),
            'label' => $this->t('Tigo te presta paquetes'),
            'url' => '/',
            'type' => 'link',
            'show' => 1,
            'description' => '',
            'weight' => 6,
          ],
          'emergencyLoan' => [
            'title' => $this->t('emergencyLoan'),
            'label' => $this->t('Tigo te Presta'),
            'url' => '/',
            'type' => 'link',
            'show' => 1,
            'offerIds' => '',
            'description' => '',
            'weight' => 7,
          ],
          'favoriteConfigure' => [
            'label' => $this->t('CONFIGURAR'),
            'url' => '/api/v2.0/mobile/favorites/subscribers/{msisdn}/plans',
            'message' => $this->t('Felicidades! Adquiriste un FAVORITO Tigo para hablar SIN LIMITES hoy, configuralo aqui:'),
            'ids' => '',
          ],
          'rechargeMessage' => [
            'label' => $this->t('RECARGAR'),
            'type' => 'button',
            'url' => '/',
            'externalUrl' => '/',
            'show' => 0,
          ],
        ],
        'actions_roles' => [
          'coreBalance',
          'creditCard',
          'Loan_Packets',
          'changeMsisdn',
          'Async_TigoMoney',
          'emergencyLoan',
        ],
        'messages' => [
          'package_error' => [
            'title' => $this->t('Compra Inválida'),
            'label' => $this->t('No tienes saldo suficiente para realizar esta compra, te invitamos a realizar una recarga o pagar con tarjeta de crédito.'),
            'show' => TRUE,
          ],
          'verifyCoreBalance' => [
            'title' => $this->t('Mensaje Pantalla de Verificación metodo de pago "Saldo"'),
            'label' => $this->t('Se descontará @amount de tu saldo para realizar la compra del paquete:'),
            'show' => TRUE,
          ],
          'verifyTigoMoney' => [
            'title' => $this->t('Mensaje Pantalla de Verificación metodo de pago "Tigo Money"'),
            'label' => $this->t('Se descontará @amount de tu saldo para realizar la compra del paquete'),
            'show' => TRUE,
          ],
          'verifyEmergencyLoan' => [
            'title' => $this->t('Mensaje Pantalla de Verificación metodo de pago "Préstamos de emergencia"'),
            'label' => $this->t('El monto adelantado se descontará de tu saldo más el costo del servicio.'),
            'show' => TRUE,
          ],
          'number_error' => [
            'title' => $this->t('Numero Inválido'),
            'label' => $this->t('El número que ingresaste no es un número Tigo. Por favor inténtelo de nuevo.'),
            'show' => TRUE,
          ],
          'offer_error' => [
            'title' => $this->t('Oferta Inválida'),
            'label' => $this->t('La oferta solicitada no existe. Por favor inténtelo de nuevo.'),
            'show' => TRUE,
          ],
          'gift_invalid' => [
            'title' => $this->t('Regalo Inválido'),
            'label' => $this->t('El número de línea no es válido para regalo. Inténtelo de nuevo.'),
            'show' => TRUE,
          ],
          'postpaid_invalid' => [
            'title' => $this->t('Compra inválida postpago'),
            'label' => $this->t('El número de línea no puede comprar paquetes.'),
            'show' => TRUE,
          ],
          'balance_error' => [
            'title' => $this->t('No se obtuvo el balance'),
            'label' => $this->t('La consulta del balance falló. Inténtelo de nuevo.'),
            'show' => TRUE,
          ],
          'typeAccountInvalid' => [
            'title' => $this->t('Tipo de línea inválido para recargar con cargo a factura.'),
            'label' => $this->t('Tipo de línea inválido para recargar con cargo a factura.'),
            'show' => TRUE,
          ],
          'hasBillsToPay' => [
            'title' => $this->t('Deuda de Facturas'),
            'label' => $this->t('Tiene facturas por pagar.'),
            'show' => TRUE,
          ],
          'isB2B' => [
            'title' => $this->t('Número de línea (b2b o staff)'),
            'label' => $this->t('Su número de línea es b2b o staff.'),
            'show' => TRUE,
          ],
          'lineStatusSuspend' => [
            'title' => $this->t('Número de línea en estado suspendido.'),
            'label' => $this->t('Número de línea en estado suspendido.'),
            'show' => TRUE,
          ],
          'verificationCode' => [
            'title' => $this->t('Código de verificación inválido.'),
            'label' => $this->t('Código de verificación inválido.'),
            'show' => TRUE,
          ],
          'msisdnInvalidForRecharge' => [
            'title' => $this->t('Número de línea inválido para recargar con Cargo a Factura'),
            'label' => $this->t('Número de línea inválido para recargar con Cargo a Factura'),
            'show' => TRUE,
          ],
          'facturas_error' => [
            'title' => $this->t('Número de Fácturas inválida para recargar con Cargo a Factura (>=)'),
            'label' => '2',
            'show' => TRUE,
          ],
          'hasStatusInvalid' => [
            'title' => $this->t('Mensaje de error para líneas hibridas con estado "SR"'),
            'label' => $this->t('El estado de tu línea no te permite comprar paquetigos. Regulariza tus pagos y podrás acceder a este servicio.'),
            'show' => TRUE,
          ],
          'limitInvalidForRecharge' => [
            'title' => $this->t('Límite para recargas'),
            'label' => $this->t('Monto a acreditar supera el Límite de Cta.'),
            'show' => TRUE,
          ],
        ],
        'response' => [
          'getInfo' => [
            'notFound' => $this->t('No se encontraron resultados.'),
            'error' => $this->t('En este momento no podemos obtener información de la oferta, intenta de nuevo más tarde.'),
          ],
          'tigoMoneyVerify' => [
            'title' => [
              'label' => $this->t('Resumen'),
              'show' => 1,
            ],
            'tigoMoneyVerify' => [
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
              'value' => $this->t('Tigo Money'),
              'show' => 1,
            ],
            'cancelButtons' => [
              'label' => $this->t('CANCELAR'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
            'purchaseButtons' => [
              'label' => $this->t('COMPRAR'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
            'termsAndConditions' => [
              'label' => $this->t('Al presionar COMPRAR estás aceptando los términos y condiciones.'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
          ],
          'emergencyLoanVerify' => [
            'title' => [
              'label' => $this->t('Resumen'),
              'show' => 1,
            ],
            'emergencyLoanVerify' => [
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
              'value' => $this->t('Tigo te Presta Adicional'),
              'show' => 1,
            ],
            'cancelButtons' => [
              'label' => $this->t('CANCELAR'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
            'purchaseButtons' => [
              'label' => $this->t('COMPRAR'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
            'termsAndConditions' => [
              'label' => $this->t('Al presionar COMPRAR estás aceptando los términos y condiciones.'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
          ],
          'packetsLoanVerify' => [
            'confirmationTitle' => [
              'title' => $this->t('Título Pantalla Confirmación'),
              'label' => "Resumen",
              'show' => TRUE,
            ],
            'message' => [
              'title' => $this->t('Description'),
              'label' => "Tu próxima recarga debe ser igual o superior al valor del paquete: ",
              'show' => TRUE,
            ],
            'orderDetailsTitle' => [
              'title' => $this->t('Label datos de pago'),
              'label' => "Datos de pago:  ",
              'show' => TRUE,
            ],
            'targetAccountNumber' => [
              'title' => $this->t('MSISDN'),
              'label' => "Número: ",
              'show' => TRUE,
            ],
            'loanAmount' => [
              'title' => $this->t('Label valor'),
              'label' => "Valor: ",
              'show' => TRUE,
            ],
            'purchaseDetail' => [
              'title' => $this->t('Label detalle compra'),
              'label' => 'Detalle compra: ',
              'formattedValue' => 'Recarga',
              'show' => TRUE,
            ],
            'paymentMethodsTitle' => [
              'title' => $this->t('Label Métodos de pago'),
              'label' => "Métodos de pago: ",
              'show' => TRUE,
            ],
            'paymentMethod' => [
              'title' => $this->t('Label Métodos de pago 2'),
              'label' => "Métodos de pago: ",
              'formattedValue' => 'Adelanta Saldo',
              'show' => TRUE,
            ],
            'loanBalance' => [
              'title' => $this->t('Label préstamo'),
              'label' => "Tigo te Presta: ",
              'show' => TRUE,
            ],
            'feeAmount' => [
              'title' => $this->t('Label valor servicio'),
              'label' => "Valor Servicio: ",
              'show' => TRUE,
            ],
            "change" => [
              'title' => $this->t('Boton Cambiar'),
              'label' => $this->t('CAMBIAR'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
            "cancel" => [
              'title' => $this->t('Boton Cancelar'),
              'label' => $this->t('CANCEL'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
            "purchase" => [
              'title' => $this->t('Boton Pagar'),
              'label' => $this->t('PAGAR'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
            'termsOfServices' => [
              'title' => $this->t('Mensaje terminos y condiciones'),
              'label' => $this->t('Al presionar PAGAR estás aceptando los términos y condiciones.'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
          ],
          'coreBalanceVerify' => [
            'title' => [
              'label' => $this->t('Resumen'),
              'show' => 1,
            ],
            'coreBalanceVerify' => [
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
              'value' => $this->t('Saldo'),
              'show' => 1,
            ],
            'coreBalance' => [
              'label' => $this->t('Saldo disponible:'),
              'value' => '',
              'show' => 1,
            ],
            'changeButtons' => [
              'label' => $this->t('CAMBIAR'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
            'cancelButtons' => [
              'label' => $this->t('CANCELAR'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
            'purchaseButtons' => [
              'label' => $this->t('COMPRAR'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
            'seePackages' => [
              'label' => $this->t('VER PAQUETES'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
            'termsAndConditions' => [
              'label' => $this->t('Al presionar COMPRAR estás aceptando los términos y condiciones.'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
          ],
          'postSuccess' => [
            'title' => [
              'label' => $this->t('¡Compra realizada con éxito!'),
              'rechargeLabel' => $this->t('¡Recarga realizada con éxito!'),
              'show' => 1,
            ],
            'message' => [
              'label' => $this->t('La transacción se realizó correctamente.'),
              'show' => 1,
            ],
            'paymentMethod' => [
              'label' => $this->t('Método de pago:'),
              'value' => $this->t('Saldo'),
              'invoiceCharge' => $this->t('Cargo a Factura'),
              'show' => 1,
            ],
            'details' => [
              'label' => $this->t('VER DETALLES'),
              'type' => 'link',
              'show' => 1,
            ],
            'home' => [
              'label' => $this->t('VOLVER AL INICIO'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
            'transactionDetailsTitle' => [
              'label' => 'Label Detalles de transacción',
              'value' => 'Detalles de la transacción',
              'show' => 1,
            ],
            'transactionDetailsId' => [
              'label' => 'Label Id de Transacción',
              'value' => 'Id de Transacción',
              'show' => 1,
            ],
            'transactionDetailsDetail' => [
              'label' => 'Label Tipo de producto',
              'value' => 'Tipo de producto',
              'rechargevalue' => 'Recarga',
              'show' => 1,
            ],
            'transactionDetailsMSISDN' => [
              'label' => 'Label Número de linea:',
              'value' => 'Número de linea:',
              'show' => 1,
            ],
            'transactionDetailsValidity' => [
              'label' => 'Label Vigencia',
              'value' => 'Vigencia',
              'show' => 1,
            ],
            'transactionDetailsPrice' => [
              'label' => 'Label Valor',
              'value' => 'Valor',
              'show' => 1,
            ],
          ],
          'postFailed' => [
            'title' => [
              'label' => $this->t('¡Pago no realizado!'),
              'value' => $this->t('¡Recarga no realizada!'),
              'show' => 1,
            ],
            'message' => [
              'label' => $this->t('No se pudo realizar la compra, intentelo más tarde nuevamente.'),
              'value' => $this->t('No se pudo realizar la recarga.'),
              'show' => 1,
            ],
            'home' => [
              'label' => $this->t('VOLVER AL INICIO'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
          ],
          'postLoanSuccess' => [
            'title' => [
              'label' => $this->t('¡Compra realizada con éxito!'),
              'show' => 1,
            ],
            'message' => [
              'label' => $this->t('La transacción se realizó correctamente.'),
              'show' => 0,
            ],
            'paymentMethod' => [
              'label' => $this->t('Método de pago:'),
              'value' => $this->t('Tigo te Presta'),
              'show' => 1,
            ],
            'details' => [
              'label' => $this->t('VER DETALLES'),
              'type' => 'link',
              'show' => 1,
            ],
            'home' => [
              'label' => $this->t('VOLVER AL INICIO'),
              'type' => 'link',
              'url' => '/',
              'show' => 1,
            ],
            'transactionDetailsTitle' => [
              'label' => 'Label Detalles de transacción',
              'value' => 'Detalles de la transacción',
              'show' => 1,
            ],
            'transactionDetailsId' => [
              'label' => 'Label Id de Transacción',
              'value' => 'Id de Transacción',
              'show' => 1,
            ],
            'transactionDetailsDetail' => [
              'label' => 'Label Detalle de compra',
              'value' => 'Detalle de compra',
              'show' => 1,
            ],
            'transactionDetailsMSISDN' => [
              'label' => 'Label Número de linea:',
              'value' => 'Número de linea:',
              'show' => 1,
            ],
            'transactionDetailsValidity' => [
              'label' => 'Label Vigencia',
              'value' => '',
              'show' => 0,
            ],
            'transactionDetailsPrice' => [
              'label' => 'Label Precio',
              'value' => 'Valor',
              'show' => 1,
            ],
            'transactionDetailsFee' => [
              'label' => 'Label Fee',
              'value' => 'Comisión',
              'show' => 1,
            ],
          ],
          'postLoanFailed' => [
            'title' => [
              'label' => $this->t('¡Compra no realizada!'),
              'show' => 1,
            ],
            'message' => [
              'label' => $this->t('No se pudo realizar la compra, intentelo más tarde nuevamente.'),
              'show' => 1,
            ],
          ],
        ],
      ],
    ];

    if (!empty($this->adfDefaultConfiguration())) {
      return $this->adfDefaultConfiguration();
    }
    else {
      return [
        'fields' => $this->defaultConfig['data']['fields'],
        'config' => $this->defaultConfig['config'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adfBlockForm($form, FormStateInterface $form_state) {
    $form['fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Contenido'),
      '#open' => FALSE,
    ];
    $form['config'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración'),
      '#open' => TRUE,
    ];

    $this->configDataFields($form);
    $this->configConfigPaymentMethods($form);
    $this->configActionsRoles($form);
    $this->configConfigResponse($form);
    $this->configMessageResponse($form);
    $this->configOthers($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configMessageResponse(&$form) {

    $messages = $this->configuration['config']['messages'];

    $form['messages'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuraciones adicionales y Mensajes'),
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

      if ($id == 'verifyCoreBalance' || $id == 'verifyTigoMoney' || $id == 'verifyEmergencyLoan') {
        $form['messages']['properties'][$id]['label'] = [
          '#type' => 'textfield',
          '#default_value' => $entity['label'],
          '#description' => $this->t('Debe introducir en el mensaje @amount para obtener el monto'),
        ];
      }
      else {
        $form['messages']['properties'][$id]['label'] = [
          '#type' => 'textfield',
          '#default_value' => $entity['label'],
        ];
      }

      $form['messages']['properties'][$id]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => $entity['show'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configDataFields(&$form) {
    $fields = $this->configuration['fields'];

    $form['fields']['fields'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('label'),
        $this->t('Show'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'mytable-order-weight',
        ],
      ],
    ];

    foreach ($fields as $id => $entity) {
      $fields[$id]['weight'] = $entity['weight'];
    }

    uasort($fields, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    foreach ($fields as $id => $entity) {
      $form['fields']['fields'][$id]['#attributes']['class'][] = 'draggable';

      $form['fields']['fields'][$id]['field'] = [
        '#plain_text' => $this->defaultConfig['data']['fields'][$id]['title'],
      ];

      $form['fields']['fields'][$id]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $entity['label'],
      ];

      $form['fields']['fields'][$id]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => $entity['show'],
      ];

      $form['fields']['fields'][$id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $this->defaultConfig['data']['fields'][$id]['title']]),
        '#title_display' => 'invisible',
        '#default_value' => $entity['weight'],
        '#attributes' => [
          'class' => ['mytable-order-weight'],
        ],
      ];
      if ($id === 'dateFormat') {
        $form['fields']['fields'][$id]['label'] = [
          '#type' => 'select',
          '#options' => $this->getDateTypes(),
          '#title' => $this->defaultConfig['data']['fields'][$id]['title'],
          '#default_value' => isset($entity['label']) ? $entity['label'] : $this->defaultConfig['data']['fields']['dateFormat']['label'],
        ];
      }
    }
  }

  /**
   * Get date formats.
   *
   * @return array
   *   Date formats.
   */
  public function getDateTypes() {
    $date_types = DateFormat::loadMultiple();
    $date_formatter = \Drupal::service('date.formatter');
    $date_formats = [];

    foreach ($date_types as $machine_name => $format) {
      $date_formats[$machine_name] = $this->t('@name format: @dateFormatted', [
        '@name' => $format->get('label'),
        '@dateFormatted' => $date_formatter->format(REQUEST_TIME, $machine_name),
      ]);
    }

    return $date_formats;
  }

  /**
   * {@inheritdoc}
   */
  public function configConfigPaymentMethods(&$form) {
    $form['config']['actions'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración de Botones y Métodos de pago'),
      '#open' => FALSE,
    ];

    $coreBalance = $this->configuration['config']['actions']['coreBalance'];
    $form['config']['actions']['coreBalance'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Saldo de Recarga'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['config']['actions']['coreBalance']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar método de pago "Saldo de Recarga"'),
      '#default_value' => $coreBalance['show'],
    ];
    $form['config']['actions']['coreBalance']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Método de pago'),
      '#default_value' => $coreBalance['title'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][coreBalance][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['coreBalance']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label del botón'),
      '#default_value' => $coreBalance['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][coreBalance][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['coreBalance']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo'),
      '#default_value' => $coreBalance['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][coreBalance][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['coreBalance']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $coreBalance['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][coreBalance][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $weight = ($coreBalance['weight'] != NULL) ? $coreBalance['weight'] : 10;
    $form['config']['actions']['coreBalance']['weight'] = [
      '#title' => $this->t('weight'),
      '#type' => 'weight',
      '#delta' => 10,
      '#default_value' => $weight,
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][coreBalance][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $creditCardAction = $this->configuration['config']['actions']['creditCard'];
    $form['config']['actions']['creditCard'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Tarjeta de Débito o Crédito'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['config']['actions']['creditCard']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar método de pago "Tarjeta de Débito o Crédito"'),
      '#default_value' => $creditCardAction['show'],
    ];
    $form['config']['actions']['creditCard']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Método de pago'),
      '#default_value' => $creditCardAction['title'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][creditCard][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['creditCard']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label del botón'),
      '#default_value' => $creditCardAction['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][creditCard][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['creditCard']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $creditCardAction['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][creditCard][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['creditCard']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo'),
      '#default_value' => $creditCardAction['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][creditCard][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['creditCard']['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $creditCardAction['description'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][creditCard][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $weight = ($creditCardAction['weight'] != NULL) ? $creditCardAction['weight'] : 10;
    $form['config']['actions']['creditCard']['weight'] = [
      '#title' => $this->t('weight'),
      '#type' => 'weight',
      '#delta' => 10,
      '#default_value' => $weight,
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][creditCard][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $qrPayment = $this->configuration['config']['actions']['qrPayment'] ?? $this->defaultConfig['config']['actions']['qrPayment'];
    $form['config']['actions']['qrPayment'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Simple'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['config']['actions']['qrPayment']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar método de pago simple'),
      '#default_value' => $qrPayment['show'],
    ];
    $form['config']['actions']['qrPayment']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Método de pago'),
      '#default_value' => $qrPayment['title'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][qrPayment][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['qrPayment']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label del botón'),
      '#default_value' => $qrPayment['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][qrPayment][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['qrPayment']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo'),
      '#default_value' => $qrPayment['type'],
      '#options' => ['button' => 'Button', 'link' => 'Link'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][qrPayment][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['qrPayment']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $qrPayment['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][qrPayment][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['qrPayment']['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $qrPayment['description'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][qrPayment][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $weight = ($qrPayment['weight'] != NULL) ? $qrPayment['weight'] : 10;
    $form['config']['actions']['qrPayment']['weight'] = [
      '#title' => $this->t('weight'),
      '#type' => 'weight',
      '#delta' => 10,
      '#default_value' => $weight,
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][qrPayment][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $tigo_qr_payment = $this->configuration['config']['actions']['tigoQrPos'] ?? $this->defaultConfig['config']['actions']['tigoQrPos'];
    $form['config']['actions']['tigoQrPos'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Pago QR MiTigo'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['config']['actions']['tigoQrPos']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar método dePago QR MiTigo'),
      '#default_value' => $tigo_qr_payment['show'],
    ];
    $form['config']['actions']['tigoQrPos']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Método de pago'),
      '#default_value' => $tigo_qr_payment['title'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][tigoQrPos][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['tigoQrPos']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label del botón'),
      '#default_value' => $tigo_qr_payment['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][tigoQrPos][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['tigoQrPos']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo'),
      '#default_value' => $tigo_qr_payment['type'],
      '#options' => ['button' => 'Button', 'link' => 'Link', 'webcomponent' => 'Web Component'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][tigoQrPos][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['tigoQrPos']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $tigo_qr_payment['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][tigoQrPos][show]"]' => ['checked' => TRUE],
          ':input[name="settings[config][actions][tigoQrPos][type]"]' => [['value' => 'button'], 'or', ['value' => 'link']],
        ],
      ],
    ];
    $form['config']['actions']['tigoQrPos']['scriptUrl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Script URL'),
      '#default_value' => $tigo_qr_payment['scriptUrl'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][tigoQrPos][show]"]' => ['checked' => TRUE],
          ':input[name="settings[config][actions][tigoQrPos][type]"]' => ['value' => 'webcomponent'],
        ],
      ],
    ];
    $form['config']['actions']['tigoQrPos']['tagHtml'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tag HTML'),
      '#default_value' => $tigo_qr_payment['tagHtml'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][tigoQrPos][show]"]' => ['checked' => TRUE],
          ':input[name="settings[config][actions][tigoQrPos][type]"]' => ['value' => 'webcomponent'],
        ],
      ],
    ];
    $form['config']['actions']['tigoQrPos']['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $tigo_qr_payment['description'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][tigoQrPos][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $weight = ($tigo_qr_payment['weight'] != NULL) ? $tigo_qr_payment['weight'] : 10;
    $form['config']['actions']['tigoQrPos']['weight'] = [
      '#title' => $this->t('weight'),
      '#type' => 'weight',
      '#delta' => 10,
      '#default_value' => $weight,
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][tigoQrPos][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $async_tigo_money = $this->configuration['config']['actions']['Async_TigoMoney'];
    $form['config']['actions']['Async_TigoMoney'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Async_TigoMoney'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['config']['actions']['Async_TigoMoney']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar método de pago "Async_TigoMoney"'),
      '#default_value' => $async_tigo_money['show'],
    ];
    $form['config']['actions']['Async_TigoMoney']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Método de pago'),
      '#default_value' => $async_tigo_money['title'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][Async_TigoMoney][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['Async_TigoMoney']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label del botón'),
      '#default_value' => $async_tigo_money['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][Async_TigoMoney][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['Async_TigoMoney']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo'),
      '#default_value' => $async_tigo_money['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][Async_TigoMoney][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['Async_TigoMoney']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $async_tigo_money['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][Async_TigoMoney][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['Async_TigoMoney']['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $async_tigo_money['description'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][Async_TigoMoney][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $weight = ($async_tigo_money['weight'] != NULL) ? $async_tigo_money['weight'] : 10;
    $form['config']['actions']['Async_TigoMoney']['weight'] = [
      '#title' => $this->t('weight'),
      '#type' => 'weight',
      '#delta' => 10,
      '#default_value' => $weight,
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][Async_TigoMoney][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $packets_loan = $this->configuration['config']['actions']['Loan_Packets'];
    $form['config']['actions']['Loan_Packets'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Tigo te presta paquetes'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['config']['actions']['Loan_Packets']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar método de pago "Tigo te presta paquetes"'),
      '#default_value' => $packets_loan['show'],
    ];
    $form['config']['actions']['Loan_Packets']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Método de pago'),
      '#default_value' => $packets_loan['title'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][packetsLoan][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['Loan_Packets']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label del botón'),
      '#default_value' => $packets_loan['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][packetsLoan][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['Loan_Packets']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo'),
      '#default_value' => $packets_loan['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][packetsLoan][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['Loan_Packets']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $packets_loan['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][packetsLoan][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['Loan_Packets']['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $packets_loan['description'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][packetsLoan][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $weight = ($packets_loan['weight'] != NULL) ? $packets_loan['weight'] : 10;
    $form['config']['actions']['Loan_Packets']['weight'] = [
      '#title' => $this->t('weight'),
      '#type' => 'weight',
      '#delta' => 10,
      '#default_value' => $weight,
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][Loan_Packets][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $emergency_loan = $this->configuration['config']['actions']['emergencyLoan'];
    $form['config']['actions']['emergencyLoan'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Préstamo de emergencia'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['config']['actions']['emergencyLoan']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar método de pago "Préstamo de emergencia"'),
      '#default_value' => $emergency_loan['show'],
    ];
    $form['config']['actions']['emergencyLoan']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Método de pago'),
      '#default_value' => $emergency_loan['title'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][emergencyLoan][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['emergencyLoan']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label del botón'),
      '#default_value' => $emergency_loan['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][emergencyLoan][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['emergencyLoan']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo'),
      '#default_value' => $emergency_loan['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][emergencyLoan][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['emergencyLoan']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $emergency_loan['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][emergencyLoan][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['emergencyLoan']['offerIds'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ids de los ofertas'),
      '#default_value' => $emergency_loan['offerIds'],
      '#placeholder' => 'Inserte los ids separados por coma (,)',
      '#description' => $this->t('Ids de los ofertas que no visualizan prestamos de emergencia como metodo de pago'),
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][emergencyLoan][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['emergencyLoan']['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $emergency_loan['description'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][emergencyLoan][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $weight = ($emergency_loan['weight'] != NULL) ? $emergency_loan['weight'] : 10;
    $form['config']['actions']['emergencyLoan']['weight'] = [
      '#title' => $this->t('weight'),
      '#type' => 'weight',
      '#delta' => 10,
      '#default_value' => $weight,
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][emergencyLoan][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    // Set up button, for favorite products.
    // Configure button, for favorite products.
    $favorite_setup_config = $this->configuration['config']['actions']['favoriteConfigure'];
    $form['config']['actions']['favoriteConfigure'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Boton configurar planes de favoritos'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    // Label button.
    $form['config']['actions']['favoriteConfigure']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $favorite_setup_config['label'],
    ];
    // Message text.
    $form['config']['actions']['favoriteConfigure']['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#default_value' => $favorite_setup_config['message'],
    ];
    // Redirect url to after purchase.
    $form['config']['actions']['favoriteConfigure']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Url'),
      '#default_value' => $favorite_setup_config['url'],
    ];
    // Ids.
    $form['config']['actions']['favoriteConfigure']['ids'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ids'),
      '#default_value' => $favorite_setup_config['ids'],
      '#description' => $this->t('Introdusca los ids serparados por ,'),
    ];

    $core_balance_sumary = $this->configuration['config']['actions']['coreBalanceSumary'];
    $form['config']['actions']['coreBalanceSumary'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Saldo'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['config']['actions']['coreBalanceSumary']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar label "Tu Saldo"'),
      '#default_value' => $core_balance_sumary['show'],
    ];
    $form['config']['actions']['coreBalanceSumary']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label Tu saldo'),
      '#default_value' => $core_balance_sumary['title'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][coreBalanceSumary][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $change_msisdn_action = $this->configuration['config']['actions']['changeMsisdn'];
    $form['config']['actions']['changeMsisdn'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cambiar Línea'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['config']['actions']['changeMsisdn']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar enlace: Cambiar Línea'),
      '#default_value' => $change_msisdn_action['show'],
    ];
    $form['config']['actions']['changeMsisdn']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label del botón'),
      '#default_value' => $change_msisdn_action['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][changeMsisdn][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['changeMsisdn']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $change_msisdn_action['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][changeMsisdn][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['changeMsisdn']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo'),
      '#default_value' => $change_msisdn_action['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][changeMsisdn][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $fulldescription_action = $this->configuration['config']['actions']['fulldescription'];
    $form['config']['actions']['fulldescription'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('ver mas'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['config']['actions']['fulldescription']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar enlace: ver mas'),
      '#default_value' => $fulldescription_action['show'],
    ];
    $form['config']['actions']['fulldescription']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label del botón'),
      '#default_value' => $fulldescription_action['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][fulldescription][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['fulldescription']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $fulldescription_action['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][fulldescription][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['actions']['fulldescription']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo'),
      '#default_value' => $fulldescription_action['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][fulldescription][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $payment_methods_title = $this->configuration['config']['actions']['paymentMethodsTitle'];
    $form['config']['actions']['paymentMethodsTitle'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Titulo de Metodos de Pago'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['config']['actions']['paymentMethodsTitle']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar título Pantalla Métodos de pago'),
      '#default_value' => $payment_methods_title['show'],
    ];
    $form['config']['actions']['paymentMethodsTitle']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $payment_methods_title['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][actions][paymentMethodsTitle][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Recharge Message Button.
    $this->addButtonRechargeConfigToForm($form);
  }

  /**
   * {@inheritdoc}
   */
  public function configConfigResponse(&$form) {

    $form['config']['response'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuraciones'),
      '#open' => FALSE,
    ];
    $response = $this->configuration['config']['response'] ? $this->configuration['config']['response'] : $this->defaultConfig['config']['response'];

    $form['config']['response']['getInfo'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Obtener datos'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['config']['response']['getInfo']['notFound'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No se encontraron datos'),
      '#default_value' => $response['getInfo']['notFound'],
    ];
    $form['config']['response']['getInfo']['error'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mensaje por defecto de error'),
      '#default_value' => $response['getInfo']['error'],
    ];
    // coreBalance Verify.
    $form['config']['response']['coreBalanceVerify'] = [
      '#type' => 'details',
      '#title' => $this->t('Pantalla Verificación Método de Pago Saldo de recargas'),
      '#open' => FALSE,
    ];
    $form['config']['response']['coreBalanceVerify']['title']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Título'),
      '#default_value' => $response['coreBalanceVerify']['title']['show'],
    ];
    $form['config']['response']['coreBalanceVerify']['title']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['coreBalanceVerify']['title']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][title][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['paymentMethod']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Método de pago'),
      '#default_value' => $response['coreBalanceVerify']['paymentMethod']['show'],
    ];
    $form['config']['response']['coreBalanceVerify']['paymentMethod']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['coreBalanceVerify']['paymentMethod']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][paymentMethod][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['paymentMethod']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['coreBalanceVerify']['paymentMethod']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][paymentMethod][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['coreBalanceVerify']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Datos de pago"'),
      '#default_value' => $response['coreBalanceVerify']['coreBalanceVerify']['show'],
    ];
    $form['config']['response']['coreBalanceVerify']['coreBalanceVerify']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['coreBalanceVerify']['coreBalanceVerify']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][coreBalanceVerify][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['productType']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Tipo de producto'),
      '#default_value' => $response['coreBalanceVerify']['productType']['show'],
    ];
    $form['config']['response']['coreBalanceVerify']['productType']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['coreBalanceVerify']['productType']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][productType][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['paymentMethodTitle']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Forma de Pago'),
      '#default_value' => $response['coreBalanceVerify']['paymentMethodTitle']['show'],
    ];
    $form['config']['response']['coreBalanceVerify']['paymentMethodTitle']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['coreBalanceVerify']['paymentMethodTitle']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][paymentMethodTitle][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['coreBalance']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Saldo actual'),
      '#default_value' => $response['coreBalanceVerify']['coreBalance']['show'],
    ];
    $form['config']['response']['coreBalanceVerify']['coreBalance']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['coreBalanceVerify']['coreBalance']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][coreBalance][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['cancelButtons']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar botón Cancelar'),
      '#default_value' => $response['coreBalanceVerify']['cancelButtons']['show'],
    ];
    $form['config']['response']['coreBalanceVerify']['cancelButtons']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['coreBalanceVerify']['cancelButtons']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][cancelButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['cancelButtons']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['coreBalanceVerify']['cancelButtons']['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][cancelButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['cancelButtons']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $response['coreBalanceVerify']['cancelButtons']['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][cancelButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['purchaseButtons']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar botón Comprar'),
      '#default_value' => $response['coreBalanceVerify']['purchaseButtons']['show'],
    ];
    $form['config']['response']['coreBalanceVerify']['purchaseButtons']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['coreBalanceVerify']['purchaseButtons']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][purchaseButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['purchaseButtons']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['coreBalanceVerify']['purchaseButtons']['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][purchaseButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['purchaseButtons']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $response['coreBalanceVerify']['purchaseButtons']['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][purchaseButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['seePackages']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar botón VER PAQUETES'),
      '#default_value' => $response['coreBalanceVerify']['seePackages']['show'],
    ];
    $form['config']['response']['coreBalanceVerify']['seePackages']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['coreBalanceVerify']['seePackages']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][seePackages][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['seePackages']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['coreBalanceVerify']['seePackages']['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][seePackages][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['seePackages']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $response['coreBalanceVerify']['seePackages']['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][seePackages][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['config']['response']['coreBalanceVerify']['changeButtons']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar botón Cambiar Método de pago'),
      '#default_value' => $response['coreBalanceVerify']['changeButtons']['show'],
    ];
    $form['config']['response']['coreBalanceVerify']['changeButtons']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['coreBalanceVerify']['changeButtons']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][changeButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['changeButtons']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['coreBalanceVerify']['changeButtons']['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][changeButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['changeButtons']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $response['coreBalanceVerify']['changeButtons']['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][changeButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['termsAndConditions']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar botón Términos y Condiciones'),
      '#default_value' => $response['coreBalanceVerify']['termsAndConditions']['show'],
    ];
    $form['config']['response']['coreBalanceVerify']['termsAndConditions']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['coreBalanceVerify']['termsAndConditions']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][termsAndConditions][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['termsAndConditions']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['coreBalanceVerify']['termsAndConditions']['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][termsAndConditions][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['coreBalanceVerify']['termsAndConditions']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $response['coreBalanceVerify']['termsAndConditions']['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][coreBalanceVerify][termsAndConditions][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    // tigoMoney Verify.
    $form['config']['response']['tigoMoneyVerify'] = [
      '#type' => 'details',
      '#title' => $this->t('Pantalla Verificación Método de Pago Tigo Money'),
      '#open' => FALSE,
    ];
    $form['config']['response']['tigoMoneyVerify']['title']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar titulo'),
      '#default_value' => $response['tigoMoneyVerify']['title']['show'],
    ];
    $form['config']['response']['tigoMoneyVerify']['title']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['tigoMoneyVerify']['title']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][tigoMoneyVerify][title][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['tigoMoneyVerify']['paymentMethod']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar método de pago'),
      '#default_value' => $response['tigoMoneyVerify']['paymentMethod']['show'],
    ];
    $form['config']['response']['tigoMoneyVerify']['paymentMethod']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['tigoMoneyVerify']['paymentMethod']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][tigoMoneyVerify][paymentMethod][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['tigoMoneyVerify']['paymentMethod']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['tigoMoneyVerify']['paymentMethod']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][tigoMoneyVerify][paymentMethod][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['tigoMoneyVerify']['tigoMoneyVerify']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Datos de pago"'),
      '#default_value' => $response['tigoMoneyVerify']['tigoMoneyVerify']['show'],
    ];
    $form['config']['response']['tigoMoneyVerify']['tigoMoneyVerify']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['tigoMoneyVerify']['tigoMoneyVerify']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][tigoMoneyVerify][tigoMoneyVerify][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['tigoMoneyVerify']['productType']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Tipo de producto'),
      '#default_value' => $response['tigoMoneyVerify']['productType']['show'],
    ];
    $form['config']['response']['tigoMoneyVerify']['productType']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['tigoMoneyVerify']['productType']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][tigoMoneyVerify][productType][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['tigoMoneyVerify']['paymentMethodTitle']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Forma de pago'),
      '#default_value' => $response['tigoMoneyVerify']['paymentMethodTitle']['show'],
    ];
    $form['config']['response']['tigoMoneyVerify']['paymentMethodTitle']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['tigoMoneyVerify']['paymentMethodTitle']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][tigoMoneyVerify][paymentMethodTitle][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['tigoMoneyVerify']['cancelButtons']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar botón Cancelar'),
      '#default_value' => $response['tigoMoneyVerify']['cancelButtons']['show'],
    ];
    $form['config']['response']['tigoMoneyVerify']['cancelButtons']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['tigoMoneyVerify']['cancelButtons']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][tigoMoneyVerify][cancelButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['tigoMoneyVerify']['cancelButtons']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['tigoMoneyVerify']['cancelButtons']['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][tigoMoneyVerify][cancelButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['tigoMoneyVerify']['cancelButtons']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $response['tigoMoneyVerify']['cancelButtons']['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][tigoMoneyVerify][cancelButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['tigoMoneyVerify']['purchaseButtons']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar botón Comprar'),
      '#default_value' => $response['tigoMoneyVerify']['purchaseButtons']['show'],
    ];
    $form['config']['response']['tigoMoneyVerify']['purchaseButtons']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['tigoMoneyVerify']['purchaseButtons']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][tigoMoneyVerify][purchaseButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['tigoMoneyVerify']['purchaseButtons']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['tigoMoneyVerify']['purchaseButtons']['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][tigoMoneyVerify][purchaseButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['tigoMoneyVerify']['purchaseButtons']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $response['tigoMoneyVerify']['purchaseButtons']['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][tigoMoneyVerify][purchaseButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['tigoMoneyVerify']['termsAndConditions']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar botón Términos y Condiciones'),
      '#default_value' => $response['tigoMoneyVerify']['termsAndConditions']['show'],
    ];
    $form['config']['response']['tigoMoneyVerify']['termsAndConditions']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['tigoMoneyVerify']['termsAndConditions']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][tigoMoneyVerify][termsAndConditions][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['tigoMoneyVerify']['termsAndConditions']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['tigoMoneyVerify']['termsAndConditions']['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][tigoMoneyVerify][termsAndConditions][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['tigoMoneyVerify']['termsAndConditions']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $response['tigoMoneyVerify']['termsAndConditions']['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][tigoMoneyVerify][termsAndConditions][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    // emergencyLoan Verify.
    $form['config']['response']['emergencyLoanVerify'] = [
      '#type' => 'details',
      '#title' => $this->t('Pantalla Verificación Método de Pago Préstamos de emergencia'),
      '#open' => FALSE,
    ];
    $form['config']['response']['emergencyLoanVerify']['title']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar titulo'),
      '#default_value' => $response['emergencyLoanVerify']['title']['show'],
    ];
    $form['config']['response']['emergencyLoanVerify']['title']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['emergencyLoanVerify']['title']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][emergencyLoanVerify][title][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['emergencyLoanVerify']['paymentMethod']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar método de pago'),
      '#default_value' => $response['emergencyLoanVerify']['paymentMethod']['show'],
    ];
    $form['config']['response']['emergencyLoanVerify']['paymentMethod']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['emergencyLoanVerify']['paymentMethod']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][emergencyLoanVerify][paymentMethod][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['emergencyLoanVerify']['paymentMethod']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['emergencyLoanVerify']['paymentMethod']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][emergencyLoanVerify][paymentMethod][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['emergencyLoanVerify']['emergencyLoanVerify']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Datos de pago"'),
      '#default_value' => $response['emergencyLoanVerify']['emergencyLoanVerify']['show'],
    ];
    $form['config']['response']['emergencyLoanVerify']['emergencyLoanVerify']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['emergencyLoanVerify']['emergencyLoanVerify']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][emergencyLoanVerify][emergencyLoanVerify][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['emergencyLoanVerify']['productType']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Tipo de producto'),
      '#default_value' => $response['emergencyLoanVerify']['productType']['show'],
    ];
    $form['config']['response']['emergencyLoanVerify']['productType']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['emergencyLoanVerify']['productType']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][emergencyLoanVerify][productType][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['emergencyLoanVerify']['paymentMethodTitle']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Forma de pago'),
      '#default_value' => $response['emergencyLoanVerify']['paymentMethodTitle']['show'],
    ];
    $form['config']['response']['emergencyLoanVerify']['paymentMethodTitle']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['emergencyLoanVerify']['paymentMethodTitle']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][emergencyLoanVerify][paymentMethodTitle][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['emergencyLoanVerify']['cancelButtons']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar botón Cancelar'),
      '#default_value' => $response['emergencyLoanVerify']['cancelButtons']['show'],
    ];
    $form['config']['response']['emergencyLoanVerify']['cancelButtons']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['emergencyLoanVerify']['cancelButtons']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][emergencyLoanVerify][cancelButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['emergencyLoanVerify']['cancelButtons']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['emergencyLoanVerify']['cancelButtons']['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][emergencyLoanVerify][cancelButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['emergencyLoanVerify']['cancelButtons']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $response['emergencyLoanVerify']['cancelButtons']['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][emergencyLoanVerify][cancelButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['emergencyLoanVerify']['purchaseButtons']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar botón Comprar'),
      '#default_value' => $response['emergencyLoanVerify']['purchaseButtons']['show'],
    ];
    $form['config']['response']['emergencyLoanVerify']['purchaseButtons']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['emergencyLoanVerify']['purchaseButtons']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][emergencyLoanVerify][purchaseButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['emergencyLoanVerify']['purchaseButtons']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['emergencyLoanVerify']['purchaseButtons']['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][emergencyLoanVerify][purchaseButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['emergencyLoanVerify']['purchaseButtons']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $response['emergencyLoanVerify']['purchaseButtons']['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][emergencyLoanVerify][purchaseButtons][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['emergencyLoanVerify']['termsAndConditions']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar botón Términos y Condiciones'),
      '#default_value' => $response['emergencyLoanVerify']['termsAndConditions']['show'],
    ];
    $form['config']['response']['emergencyLoanVerify']['termsAndConditions']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['emergencyLoanVerify']['termsAndConditions']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][emergencyLoanVerify][termsAndConditions][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['emergencyLoanVerify']['termsAndConditions']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['emergencyLoanVerify']['termsAndConditions']['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][emergencyLoanVerify][termsAndConditions][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['emergencyLoanVerify']['termsAndConditions']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $response['emergencyLoanVerify']['termsAndConditions']['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][emergencyLoanVerify][termsAndConditions][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    // Packets Loans Verify Configuration.
    $form['config']['response']['packetsLoanVerify'] = [
      '#type' => 'details',
      '#title' => $this->t('Pantalla Verificación Método de Pago Préstamos de paquetes'),
      '#open' => FALSE,
    ];

    $verify_packets_loan_config = $response['packetsLoanVerify'];

    foreach ($verify_packets_loan_config as $key => $config) {
      $title = $this->defaultConfig['config']['response']['packetsLoanVerify'][$key]['title'];
      $form['config']['response']['packetsLoanVerify'][$key] = [
        '#type' => 'details',
        '#title' => $title,
        '#open' => FALSE,
      ];
      // Label.
      $form['config']['response']['packetsLoanVerify'][$key]['label'] = [
        '#title' => $this->t('Label'),
        '#type' => 'textfield',
        '#default_value' => $config['label'],
      ];
      // Show.
      $form['config']['response']['packetsLoanVerify'][$key]['show'] = [
        '#title' => $this->t('Mostrar'),
        '#type' => 'checkbox',
        '#default_value' => $config['show'],
      ];
      // Formatted Vallue.
      if (isset($config['formattedValue'])) {
        $form['config']['response']['packetsLoanVerify'][$key]['formattedValue'] = [
          '#title' => $this->t('Format Value'),
          '#type' => 'textfield',
          '#default_value' => $config['formattedValue'],
        ];
      }
      else {
        $form['config']['response']['packetsLoanVerify'][$key]['formattedValue'] = [];
      }
      // Type.
      if (isset($config['type'])) {
        $form['config']['response']['packetsLoanVerify'][$key]['type'] = [
          '#title' => $this->t('Type'),
          '#type' => 'select',
          '#options' => [
            'link' => $this->t('Enlace'),
            'button' => $this->t('Boton'),
          ],
          '#default_value' => (isset($config['type'])) ? $config['type'] : NULL,
        ];
      }
      else {
        $form['config']['response']['type'][$key]['formattedValue'] = [];
      }
      // Url.
      if (isset($config['url'])) {
        $form['config']['response']['packetsLoanVerify'][$key]['url'] = [
          '#title' => $this->t('URL'),
          '#type' => 'textfield',
          '#default_value' => $config['url'],
        ];
      }
      else {
        $form['config']['response']['type'][$key]['url'] = [];
      }
    }

    // Response postSuccess.
    $form['config']['response']['postSuccess'] = [
      '#type' => 'details',
      '#title' => $this->t('Compra exitosa'),
      '#open' => FALSE,
    ];
    $form['config']['response']['postSuccess']['title']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Título'),
      '#default_value' => $response['postSuccess']['title']['show'],
    ];
    $form['config']['response']['postSuccess']['title']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postSuccess']['title']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][title][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['title']['rechargeLabel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postSuccess']['title']['rechargeLabel'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][title][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['message']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Mensaje'),
      '#default_value' => $response['postSuccess']['message']['show'],
    ];
    $form['config']['response']['postSuccess']['message']['label'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postSuccess']['message']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][message][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['paymentMethod']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar método de pago'),
      '#default_value' => $response['postSuccess']['paymentMethod']['show'],
    ];
    $form['config']['response']['postSuccess']['paymentMethod']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postSuccess']['paymentMethod']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][paymentMethod][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['paymentMethod']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postSuccess']['paymentMethod']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][paymentMethod][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['paymentMethod']['invoiceCharge'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postSuccess']['paymentMethod']['invoiceCharge'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][paymentMethod][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['details']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar botón "Ver detalles"'),
      '#default_value' => $response['postSuccess']['details']['show'],
    ];
    $form['config']['response']['postSuccess']['details']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postSuccess']['details']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][details][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['details']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postSuccess']['details']['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][details][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['home']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar botón "VOLVER AL INICIO"'),
      '#default_value' => $response['postSuccess']['home']['show'],
    ];
    $form['config']['response']['postSuccess']['home']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postSuccess']['home']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][home][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['home']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postSuccess']['home']['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][home][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['home']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $response['postSuccess']['home']['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][home][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['transactionDetailsTitle']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Detalles de transacción'),
      '#default_value' => $response['postSuccess']['transactionDetailsTitle']['show'],
    ];
    $form['config']['response']['postSuccess']['transactionDetailsTitle']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postSuccess']['transactionDetailsTitle']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][transactionDetailsTitle][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['transactionDetailsId']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Id de transacción'),
      '#default_value' => $response['postSuccess']['transactionDetailsId']['show'],
    ];
    $form['config']['response']['postSuccess']['transactionDetailsId']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postSuccess']['transactionDetailsId']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][transactionDetailsId][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['transactionDetailsDetail']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Detalle de compra'),
      '#default_value' => $response['postSuccess']['transactionDetailsDetail']['show'],
    ];
    $form['config']['response']['postSuccess']['transactionDetailsDetail']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postSuccess']['transactionDetailsDetail']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][transactionDetailsDetail][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['transactionDetailsDetail']['rechargevalue'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postSuccess']['transactionDetailsDetail']['rechargevalue'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][transactionDetailsDetail][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['transactionDetailsMSISDN']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Número de Línea'),
      '#default_value' => $response['postSuccess']['transactionDetailsMSISDN']['show'],
    ];
    $form['config']['response']['postSuccess']['transactionDetailsMSISDN']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postSuccess']['transactionDetailsMSISDN']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][transactionDetailsMSISDN][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['transactionDetailsValidity']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Vigencia'),
      '#default_value' => $response['postSuccess']['transactionDetailsValidity']['show'],
    ];
    $form['config']['response']['postSuccess']['transactionDetailsValidity']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postSuccess']['transactionDetailsValidity']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][transactionDetailsValidity][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postSuccess']['transactionDetailsPrice']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Precio'),
      '#default_value' => $response['postSuccess']['transactionDetailsPrice']['show'],
    ];
    $form['config']['response']['postSuccess']['transactionDetailsPrice']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postSuccess']['transactionDetailsPrice']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postSuccess][transactionDetailsPrice][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Response postFailed.
    $form['config']['response']['postFailed'] = [
      '#type' => 'details',
      '#title' => $this->t('Compra fallida'),
      '#open' => FALSE,
    ];
    $form['config']['response']['postFailed']['title']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Título'),
      '#default_value' => $response['postFailed']['title']['show'],
    ];
    $form['config']['response']['postFailed']['title']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postFailed']['title']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postFailed][title][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postFailed']['title']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postFailed']['title']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postFailed][title][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postFailed']['message']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Mensaje'),
      '#default_value' => $response['postFailed']['message']['show'],
    ];
    $form['config']['response']['postFailed']['message']['label'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postFailed']['message']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postFailed][message][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postFailed']['message']['value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postFailed']['message']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postFailed][message][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postFailed']['home']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar botón "Volver al inicio"'),
      '#default_value' => $response['postFailed']['home']['show'],
    ];
    $form['config']['response']['postFailed']['home']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postFailed']['home']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postFailed][home][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postFailed']['home']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postFailed']['home']['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postFailed][home][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postFailed']['home']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $response['postFailed']['home']['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postFailed][home][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    // Response postLoanSuccess.
    $form['config']['response']['postLoanSuccess'] = [
      '#type' => 'details',
      '#title' => $this->t('Préstamo exitoso'),
      '#open' => FALSE,
    ];
    $form['config']['response']['postLoanSuccess']['title']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Título'),
      '#default_value' => $response['postLoanSuccess']['title']['show'],
    ];
    $form['config']['response']['postLoanSuccess']['title']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postLoanSuccess']['title']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanSuccess][title][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postLoanSuccess']['message']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Mensaje'),
      '#default_value' => $response['postLoanSuccess']['message']['show'],
    ];
    $form['config']['response']['postLoanSuccess']['message']['label'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postLoanSuccess']['message']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanSuccess][message][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postLoanSuccess']['paymentMethod']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar método de pago'),
      '#default_value' => $response['postLoanSuccess']['paymentMethod']['show'],
    ];
    $form['config']['response']['postLoanSuccess']['paymentMethod']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postLoanSuccess']['paymentMethod']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanSuccess][paymentMethod][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postLoanSuccess']['paymentMethod']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postLoanSuccess']['paymentMethod']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanSuccess][paymentMethod][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postLoanSuccess']['details']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar botón "Ver detalles"'),
      '#default_value' => $response['postLoanSuccess']['details']['show'],
    ];
    $form['config']['response']['postLoanSuccess']['details']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postLoanSuccess']['details']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanSuccess][details][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postLoanSuccess']['details']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postLoanSuccess']['details']['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanSuccess][details][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postLoanSuccess']['home']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar botón "VOLVER AL INICIO"'),
      '#default_value' => $response['postLoanSuccess']['home']['show'],
    ];
    $form['config']['response']['postLoanSuccess']['home']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postLoanSuccess']['home']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanSuccess][home][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postLoanSuccess']['home']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postLoanSuccess']['home']['type'],
      '#options' => [
        'button' => $this->t('Button'),
        'link' => $this->t('Link'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanSuccess][home][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postLoanSuccess']['home']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $response['postLoanSuccess']['home']['url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanSuccess][home][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postLoanSuccess']['transactionDetailsTitle']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Detalles de transacción'),
      '#default_value' => $response['postLoanSuccess']['transactionDetailsTitle']['show'],
    ];
    $form['config']['response']['postLoanSuccess']['transactionDetailsTitle']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postLoanSuccess']['transactionDetailsTitle']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanSuccess][transactionDetailsTitle][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postLoanSuccess']['transactionDetailsId']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Id de transacción'),
      '#default_value' => $response['postLoanSuccess']['transactionDetailsId']['show'],
    ];
    $form['config']['response']['postLoanSuccess']['transactionDetailsId']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postLoanSuccess']['transactionDetailsId']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanSuccess][transactionDetailsId][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postLoanSuccess']['transactionDetailsDetail']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Detalle de compra'),
      '#default_value' => $response['postLoanSuccess']['transactionDetailsDetail']['show'],
    ];
    $form['config']['response']['postLoanSuccess']['transactionDetailsDetail']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postLoanSuccess']['transactionDetailsDetail']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanSuccess][transactionDetailsDetail][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postLoanSuccess']['transactionDetailsMSISDN']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Número de Línea'),
      '#default_value' => $response['postLoanSuccess']['transactionDetailsMSISDN']['show'],
    ];
    $form['config']['response']['postLoanSuccess']['transactionDetailsMSISDN']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postLoanSuccess']['transactionDetailsMSISDN']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanSuccess][transactionDetailsMSISDN][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postLoanSuccess']['transactionDetailsValidity']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Vigencia'),
      '#default_value' => $response['postLoanSuccess']['transactionDetailsValidity']['show'],
    ];
    $form['config']['response']['postLoanSuccess']['transactionDetailsValidity']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postLoanSuccess']['transactionDetailsValidity']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanSuccess][transactionDetailsValidity][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postLoanSuccess']['transactionDetailsPrice']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Precio'),
      '#default_value' => $response['postLoanSuccess']['transactionDetailsPrice']['show'],
    ];
    $form['config']['response']['postLoanSuccess']['transactionDetailsPrice']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postLoanSuccess']['transactionDetailsPrice']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanSuccess][transactionDetailsPrice][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postLoanSuccess']['transactionDetailsFee']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Comisión'),
      '#default_value' => $response['postLoanSuccess']['transactionDetailsFee']['show'],
    ];
    $form['config']['response']['postLoanSuccess']['transactionDetailsFee']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor'),
      '#default_value' => $response['postLoanSuccess']['transactionDetailsFee']['value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanSuccess][transactionDetailsFee][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    // Response postLoanFailed.
    $form['config']['response']['postLoanFailed'] = [
      '#type' => 'details',
      '#title' => $this->t('Préstamo fallido'),
      '#open' => FALSE,
    ];
    $form['config']['response']['postLoanFailed']['title']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Título'),
      '#default_value' => $response['postLoanFailed']['title']['show'],
    ];
    $form['config']['response']['postLoanFailed']['title']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postLoanFailed']['title']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanFailed][title][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['config']['response']['postLoanFailed']['message']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar Mensaje'),
      '#default_value' => $response['postLoanFailed']['message']['show'],
    ];
    $form['config']['response']['postLoanFailed']['message']['label'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Label'),
      '#default_value' => $response['postLoanFailed']['message']['label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[config][response][postLoanFailed][message][show]"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

}
