<?php

namespace Drupal\oneapp_convergent_upgrade_plan_bo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UpgradePlanLogGtFilterForm.
 */
class UpgradePlanLogBoFilterForm extends FormBase {

  /**
   * @return string
   */
  public function getFormId() {
    return 'upgrade_plan_log_bo_filter_form';
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#method'] = 'get';
    $form['business_unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Business unit'),
      '#options' => [
        'HOME' => $this->t('HOME'),
        'MOBILE' => $this->t('MOBILE'),
      ],
      '#empty_value' => '',
      '#maxlength' => 64,
      '#weight' => 0,
    ];
    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => 1,
    ];
    $form['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Fecha desde'),
      '#weight' => 2,
    ];
    $form['end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Fecha hasta'),
      '#weight' => 3,
    ];
    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 4,
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Search'),
        '#weight' => 1,
      ],
      'reset' => [
        '#type' => 'link',
        '#title' => $this->t('Reset'),
        '#weight' => 2,
        '#url' => \Drupal\Core\Url::fromRoute('oneapp.settings.config.convergent.upgrade_plan_log_index'),
        '#attributes' => [
          'id' => 'id',
          'class' => ['button'],
        ]
      ]
    ];

    if (!empty($_REQUEST)) {
      $form['actions']['export'] = [
        '#type' => 'link',
        '#title' => $this->t('Export') . ' CSV',
        '#weight' => 3,
        '#url' => \Drupal\Core\Url::fromRoute('oneapp.settings.config.convergent.upgrade_plan_log_export')->setRouteParameters($_REQUEST),
        '#attributes' => [
          'id' => 'id',
          'class' => ['button'],
        ]
      ];
    }

    return $form;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    return;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return;
  }
}
