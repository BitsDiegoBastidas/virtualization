<?php

namespace Drupal\oneapp_mobile_upselling_bo\Plugin\Block\v2_0;

use Drupal\Core\Form\FormStateInterface;
use Drupal\oneapp_mobile_upselling\Plugin\Block\v2_0\VoiceBalanceBlock;

/**
 * Class VoiceBalanceBoBlock.
 */
class VoiceBalanceBoBlock extends VoiceBalanceBlock {

  /**
   * @return array
   */
  public function defaultConfiguration() {
    $configDefault = parent::defaultConfiguration();
    $adfConfigDefault = parent::adfDefaultConfiguration();

    $this->contentFieldsConfig['showDetailWebComponent'] = [
      'title' => $this->t('VER WEBCOMPONENT:'),
      'label' => $this->t('VER WEBCOMPONENT'),
      'type' => 'webcomponent',
      'scriptUrl' => '',
      'tagHtml' => '',
      'slug' => '',
      'supportedVersions' => [
        'min' => '1.0.0',
        'max' => '5.0.0'
      ],
      'show' => FALSE
    ];

    if (!empty($adfConfigDefault)) {
      return $adfConfigDefault;
    }
    else {
      return [
        'voiceBalance' => $configDefault["voiceBalance"],
        'config' => $this->contentFieldsConfig,
      ];
    }
  }

  /**
   * @param $form
   * @param FormStateInterface $form_state
   * @return array|void
   */
  public function adfBlockForm($form, FormStateInterface $form_state) {
    $form = parent::adfBlockForm($form, $form_state);
    $config = $this->configuration['config']['webcomponent'] ?? $this->contentFieldsConfig['showDetailWebComponent'];

    $form['config']['webcomponent'] = [
      '#type' => 'details',
      '#title' => $this->t('webcomponent'),
      '#open' => FALSE,
    ];

    $form['config']['webcomponent']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Título'),
      '#default_value' => $config['title'],
    ];

    $form['config']['webcomponent']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $config['label'],
    ];

    $form['config']['webcomponent']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo'),
      '#options' => [
        'link' => $this->t('Enlace'),
        'button' => $this->t('Boton'),
        'webcomponent' => $this->t('webcomponent'),
      ],
      '#default_value' => $config['type'],
    ];

    $form['config']['webcomponent']['scriptUrl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Script'),
      '#default_value' => $config['scriptUrl'],
    ];

    $form['config']['webcomponent']['tagHtml'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tag HTML'),
      '#default_value' => $config['tagHtml'],
    ];

    $form['config']['webcomponent']['slug'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Slug'),
      '#default_value' => $config['slug'],
    ];

    $form['config']['webcomponent']['supportedVersions']['min'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Versión mínima soportada'),
      '#default_value' => $config['supportedVersions']['min'],
    ];

    $form['config']['webcomponent']['supportedVersions']['max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Versión máxima soportada'),
      '#default_value' => $config['supportedVersions']['max'],
    ];

    $form['config']['webcomponent']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar'),
      '#default_value' => $config['show'],
    ];

    return $form;
  }

  /**
   * @param $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function adfBlockSubmit($form, FormStateInterface $form_state) {
    parent::adfBlockSubmit($form, $form_state);
    $this->configuration['config']['webcomponent'] = $form_state->getValue(["config","webcomponent"]);
  }

}
