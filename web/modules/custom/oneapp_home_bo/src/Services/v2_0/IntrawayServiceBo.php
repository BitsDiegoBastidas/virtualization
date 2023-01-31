<?php

namespace Drupal\oneapp_home_bo\Services\v2_0;

use Drupal\oneapp_home\Services\IntrawayService;

/**
 * Class IntrawayServiceBo.
 */
class IntrawayServiceBo extends IntrawayService {

  /**
   * Set formatted data with the block config.
   *
   * @param array $blockConfig
   *   All data.
   * @param string $id
   *   Id.
   *
   * @return array
   *   Reponse all the data with throw the block config.
   */
  public function hide(array $blockConfig, $id) {
    if (!empty($blockConfig['hide']['services'])) {
      $services = explode("\r\n", $blockConfig['hide']['services']);
      $this->setSubscriptions($id);
      $hide = FALSE;
      foreach ($this->subscriptions as $key => $subscription) {
        if (in_array($subscription->planType, $services)) {
          // Oculta el card en los planes seleccionados.
          $hide = TRUE;
          break;
        }
      }
      return $hide;
    }
    else {
      return FALSE;
    }
  }

}
