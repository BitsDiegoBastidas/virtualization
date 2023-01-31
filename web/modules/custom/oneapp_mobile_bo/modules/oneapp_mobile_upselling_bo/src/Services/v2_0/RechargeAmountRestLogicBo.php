<?php

namespace Drupal\oneapp_mobile_upselling_bo\Services\v2_0;

use Drupal\oneapp_mobile_upselling\Services\v2_0\RechargeAmountRestLogic;

/**
 * Class RechargeAmountRestLogicBo.
 */
class RechargeAmountRestLogicBo extends RechargeAmountRestLogic {

  /**
   * {@inheritDoc}
   */
  public function get($account) {
    $rechargeFields = [];
    $actionsFields = [];
    $this->determinateUserRole();
    $max = \Drupal::config('oneapp_mobile.config')->get('recharge_amounts_dimensions')['dimension_max'];
    $min = \Drupal::config('oneapp_mobile.config')->get('recharge_amounts_dimensions')['min'];
    $amounts = $this->getAllAmounts();
    $rechargeConfigs = $this->configBlock['fields'];
    $actionsConfig = $this->configBlock['actions'];

    foreach ($rechargeConfigs as $key => $config) {

      $rechargeFields[$key] = [
        'label' => $config['label'],
        'show' => ($config['show']) ? TRUE : FALSE,
      ];

      switch ($key) {
        case 'msisdn':
          // Si viene el target.
          if ($account) {
            $rechargeFields[$key]['value'] = $account;
            $rechargeFields[$key]['formatValue'] = \Drupal::service('oneapp.mobile.utils')->getFormattedMsisdn($account);
          }
          break;

        case 'rechargeAmounts':
          $rechargeFields[$key]['options'] = $this->formatOptions($amounts, $min, $max);
          $rechargeFields[$key]['validations'] = ['required' => TRUE];
          break;

        case 'otherAmount':
          $description = $config['description'];
          $rechargeFields[$key]['type'] = 'number';
          $rechargeFields[$key]['placeholder'] = $config['placeholder'];
          $rechargeFields[$key]['description'] = str_replace(["@min", "@max"], [$min, $max], $description);
          $rechargeFields[$key]['validations'] = [
            'required' => TRUE,
            'min' => $min,
            'max' => $max,
          ];
          break;
      }
    }

    foreach ($actionsConfig as $key => $config) {

      $actionsFields[$key] = [
        'label' => $config['label'],
        'show' => ($config['show']) ? TRUE : FALSE,
      ];

      switch ($key) {

        case 'changeMsisdn':
        case 'otherValue':
          $actionsFields[$key]['type'] = $config['type'];
          break;

        case 'continue':
          $actionsFields[$key]['type'] = $config['type'];
          $actionsFields[$key]['url'] = $config['url'];
          break;
      }
    }
    $this->checkAccessRoleToActions($actionsFields);
    return [
      'rechargeDetail' => $rechargeFields,
      'actions' => $actionsFields,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getAllAmounts() {
    $ids = \Drupal::entityQuery('mobile_recharge_amounts')->execute();
    $amounts = \Drupal::entityTypeManager()
      ->getStorage('mobile_recharge_amounts')
      ->loadMultiple($ids);
    $result = [];
    foreach ($amounts as $value) {
      $amount = intval($value->getValue());
      $result[$amount] = [
        'value' => $amount,
        'formattedValue' => $this->utils->formatCurrency($amount, TRUE),
        'show' => $value->isPublished(),
      ];
    }
    sort($result);
    return $result;
  }

}
