<?php

namespace Drupal\oneapp_convergent_upgrade_plan_bo\Services\v2_0;

use Drupal\oneapp_convergent_upgrade_plan\Services\v2_0\UpgradePlanCardRestLogic;

/**
 * Class UpgradePlanCardRestLogicBo.
 */
class UpgradePlanCardRestLogicBo extends UpgradePlanCardRestLogic {


  /**
   * Get all data plan card for api.
   *
   * @return array
   *   Return fields as array of objects.
   */
  public function get($id) {

    $this->service->setConfig($this->configBlock);
    $validation_plan_type = $this->service->getValidationPlanCode($id);

    $data_card = [ // do not show the card
        'noData' =>
          [
          'value' => 'hide',
          ],
    ];

    $upgrade_plan_config = $this->configBlock['upgradePlan']['fields'];
    $file_id = (!empty($upgrade_plan_config['banner']['url'][0])) ? $upgrade_plan_config['banner']['url'][0] : 0;
    $title = (!empty($upgrade_plan_config['title']['value'])) ? $upgrade_plan_config['title']['value'] : '';
    $data_card_show['planUpgrade'] = [ // show the data card
      'banner' => [
          'url' => $this->upgradeUtils->getImageUrl($file_id),
          'show' => (!empty($upgrade_plan_config['banner']['show'])) ? TRUE : FALSE,
      ],
      'title' => [
        'value' => $title,
        'show' => (!empty($upgrade_plan_config['title']['show'])) ? TRUE : FALSE,
      ],
      'description' =>  (!empty($upgrade_plan_config['description']['value']))
                        ? $upgrade_plan_config['description']['value'] : '',
    ];

    if ($validation_plan_type->code == 2000) {
      $has_recommended_products = $this->service->getRecommendProductsData($id, TRUE);
      if ($has_recommended_products) {
          $data_card = $data_card_show;
        }
    }
    else {
      $data_card = $data_card_show;
    }

    return $data_card;
  }


}
