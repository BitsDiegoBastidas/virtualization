<?php

namespace Drupal\oneapp_mobile_upselling_bo\Plugin\Block\v2_0;

use Drupal\Core\Form\FormStateInterface;
use Drupal\oneapp_mobile_upselling\Plugin\Block\v2_0\AvailableOffersBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AvailableOffersBoBlock extends AvailableOffersBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $this->contentFields = [
      'offersList' => [
        'fields' => [
          'offerId' => [
            'title' => $this->t('Oferta'),
            'label' => '',
            'show' => 0,
            'weight' => 0,
          ],
          'offerName' => [
            'title' => $this->t('Nombre:'),
            'label' => $this->t('Nombre'),
            'show' => 1,
            'weight' => 1,
          ],
          'description' => [
            'title' => $this->t('Description:'),
            'label' => $this->t('Description'),
            'show' => 1,
            'weight' => 2,
          ],
          'tags' => [
            'title' => $this->t('Etiqueta:'),
            'label' => '',
            'show' => 0,
            'weight' => 3,
          ],
          'validity' => [
            'title' => $this->t('Valor unitario:'),
            'label' => $this->t('Valor unitario'),
            'show' => 1,
            'weight' => 4,
          ],
          'price' => [
            'title' => $this->t('Precio:'),
            'label' => $this->t('Precio'),
            'show' => 1,
            'weight' => 5,
          ],
          'order' => [
            'title' => $this->t('Orden de imágenes'),
            'label' => $this->t('Orden de imágenes'),
            'show' => 1,
            'weight' => 5,
          ],
        ],
      ],
      'acquiredOffers' => [
        'show' => 1,
        'url' => '/',
      ],
      'messages' => [
        'offerFree' => $this->t('Gratis.'),
        'error' => $this->t('En este momento no podemos obtener los productos disponibles, por favor intentelo más tarde.'),
        'offerError' => $this->t('No se encontrarón ofertas relacionadas con el número consultado.'),
        'empty' => $this->t('No se encontrarón ofertas relacionadas con el número consultado.'),
      ],
      'config' => [
        'imagePath' => [
          'url' => '/',
        ],
        'products' => [
          'order' => '',
        ],
        'prefix' => [
          'active' => TRUE,
        ],
        'removeOffers' => ''
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
   * Build configuration form.
   *
   * {@inheritdoc}
   */
  public function adfBlockForm($form, FormStateInterface $form_state) {

    $form = parent::adfBlockForm($form, $form_state);

    $config_available_offers = (!empty($this->configuration['config']))?
      $this->configuration['config'] : [];

    $form['config']['removeOffers'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quitar Ofertas'),
      '#default_value' => (!empty($config_available_offers['removeOffers'])) ? $config_available_offers['removeOffers'] : '',
    ];

    return $form;
  }

}
