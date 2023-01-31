<?php

namespace Drupal\oneapp_mobile_premium_bo\Services\v2_0;

use Drupal\oneapp_mobile_premium\Services\v2_0\PremiumRestLogic;

/**
 * Class PremiumRestLogicBo.
 */
class PremiumRestLogicBo extends PremiumRestLogic {

  /**
   * Get data all premium products formated.
   *
   * @return array
   *   Return fields as array of objects.
   */
  public function get($account_id) {
    $response = $this->service->validateLine($account_id);
    $configuration_service = \Drupal::config('oneapp_mobile.config');
    $behaviours = $configuration_service->get('premium')['behaviour']['codes'];
    $codes = array_column($behaviours, 'code');
    $mapping['behaviour'] = '';
    if (in_array($response->code, $codes)) {
      $mapping = array_filter($behaviours, function ($item) use ($response) {
        return $item['code'] == $response->code;
      });
      $mapping = reset($mapping);
    }
    // Validaciones para el comportamiento del card.
    switch ($mapping['behaviour']) {

      case "cant_addons":
        $data = parent::get($account_id);
        $data['addons'] = [
          'message' => !empty($mapping["message"]) ? $mapping["message"] : $response->message,
          'redirect' => (bool) $mapping["redirect"],
          'url' => !empty($mapping["url"]) ? $mapping["url"] : '',
        ];
        break;

      case "empty_card":
        $data = ['productList' => [], 'noData' => ['value' => 'empty']];
        break;

      case "error_card":
        $message = !empty($mapping["message"]) ? $mapping["message"] : $response->message;
        throw new \Exception(json_encode(['message' => $message]));

      case "is_valid":
      default:
        $data = parent::get($account_id);
        break;
    }
    return $data;
  }

}
