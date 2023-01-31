<?php

namespace Drupal\oneapp_convergent_upgrade_plan_bo\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\oneapp_convergent_upgrade_plan\Services\UtilService;

/**
 * Class HomeUpgradeServiceUtil.
 *
 * @package Drupal\home_upgrade
 */
class UtilServiceBo extends UtilService {

  public function __construct($utils, $request_stack, ConfigFactoryInterface $config_factory_service) {
    parent::__construct($utils, $request_stack, $config_factory_service);
  }

  /**
   * {@inheritdoc}
   */
  public function getProductFormatValue($new_product, $product_name_format, $format_data = 'Mb') {
    $product_name_format = is_numeric($product_name_format) ? NULL : $product_name_format;
    if (empty($new_product)) {
      return $new_product;
    }
    if ($product_name_format == 'mbps') {
      return $this->utils->formatData($new_product, $format_data);
    }
    if ($product_name_format == 'currency') {
      if (empty($new_product)) {
        return '';
      }
      return $this->utils->formatCurrency($new_product, TRUE);
    }
    return trim("{$new_product} {$product_name_format}");
  }
}
