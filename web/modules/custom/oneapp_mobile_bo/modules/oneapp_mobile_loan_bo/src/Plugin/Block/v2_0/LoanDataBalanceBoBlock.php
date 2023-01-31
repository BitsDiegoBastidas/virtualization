<?php

namespace Drupal\oneapp_mobile_loan_bo\Plugin\Block\v2_0;

use Drupal\Core\Form\FormStateInterface;
use Drupal\oneapp_mobile_loan\Plugin\Block\v2_0\LoanDataBalanceBlock;

/**
 * Class LoanDataBalanceBoBlock.
 */
class LoanDataBalanceBoBlock extends LoanDataBalanceBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $this->fields = [
      'creditAvailable' => [
        'title' => 'Label cupo de prestamo',
        'label' => 'Cupo de Prestamo',
        'show' => TRUE,
      ],
      'totalDebt' => [
        'title' => 'Label Monto Pendiente',
        'label' => 'Monto Pendiente de Pago',
        'show' => TRUE,
      ],
      'scoring' => [
        'title' => 'Label scoring',
        'label' => 'scoring',
        'show' => FALSE,
      ],
      'overdraft' => [
        'title' => 'Label sobregido',
        'label' => 'Sobregirado',
        'show' => FALSE,
      ],
    ];

    $this->actions = [
      'purchase' => [
        'title' => 'PAGAR',
        'label' => 'PAGAR',
        'url' => '',
        'type' => 'button',
        'show' => TRUE,
      ],
      'info' => [
        'title' => 'INFO',
        'label' => 'Préstamos de saldo y recursos que pagas en tu próxima recarga.',
        'url' => '',
        'type' => 'button',
        'show' => TRUE,
      ],
    ];

    return [
      'fields' => $this->fields,
      'actions' => $this->actions,
    ];

  }

  /**
   * {@inheritdoc}
   */
  protected function adfBlockForm($form, FormStateInterface $form_state) {
    $this->configFieldsForm($form);
    $this->configActionsForm($form);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configFieldsForm(&$form) {
    $fields = isset($this->configuration['fields']) ? $this->configuration['fields'] : $this->fields;

    $form['loanDetails'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración labels Saldos Tigo te presta'),
      '#open' => FALSE,
    ];

    $form['loanDetails']['fields'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('Description'),
        $this->t('Show'),
        '',
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
    ];

    foreach ($fields as $key => $field) {
      $form['loanDetails']['fields'][$key]['field'] = [
        '#plain_text' => isset($field['title']) ? $field['title'] : $this->fields[$key]['title'],
      ];

      $form['loanDetails']['fields'][$key]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $field['label'],
        '#size' => 25,
      ];

      $form['loanDetails']['fields'][$key]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => $field['show'],
      ];
    }
  }

  /**
   * Add config section to form array block's.
   *
   * @param mixed $form
   *   Configuration form.
   */
  public function configActionsForm(&$form) {
    $actions = isset($this->configuration['actions']) ? $this->configuration['actions'] : $this->actions;

    $form['actions'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuraciones de Botones.'),
      '#open' => FALSE,
    ];

    $form['actions']['buttons'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('label'),
        $this->t('Url'),
        $this->t('Type'),
        $this->t('Show'),
        '',
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
    ];

    foreach ($actions as $key => $action) {
      $form['actions']['buttons'][$key]['field'] = [
        '#plain_text' => isset($action['title']) ? $action['title'] : $this->fields[$key]['title'],
      ];

      $form['actions']['buttons'][$key]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $action['label'],
        '#size' => 30,
      ];

      if (isset($action['url'])) {
        $form['actions']['buttons'][$key]['url'] = [
          '#type' => 'url',
          '#size' => 30,
          '#default_value' => (isset($action['url'])) ? $action['url'] : '',
        ];
      }
      else {
        $form['actions']['buttons'][$key]['url'] = [];
      }

      if (isset($action['type'])) {
        $form['actions']['buttons'][$key]['type'] = [
          '#type' => 'select',
          '#options' => [
            'link' => $this->t('Enlace'),
            'button' => $this->t('Boton'),
          ],
          '#default_value' => (isset($action['type'])) ? $action['type'] : NULL,
        ];
      }
      else {
        $form['actions']['buttons'][$key]['type'] = [];
      }

      $form['actions']['buttons'][$key]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => (isset($action['show'])) ? $action['show'] : TRUE,
      ];
    }
  }

}
