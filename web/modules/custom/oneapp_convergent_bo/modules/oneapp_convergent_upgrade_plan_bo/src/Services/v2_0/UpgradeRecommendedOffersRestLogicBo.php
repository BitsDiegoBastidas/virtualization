<?php

namespace Drupal\oneapp_convergent_upgrade_plan_bo\Services\v2_0;

use Drupal\oneapp_convergent_upgrade_plan\Services\v2_0\UpgradeRecommendedOffersRestLogic;

/**
 * Class UpgradeRecommendedOffersRestLogicBo.
 */
class UpgradeRecommendedOffersRestLogicBo extends UpgradeRecommendedOffersRestLogic {

  private $bundledItemsList, $productsListData;

  protected $currentOffers;

  protected $currentDecoders;

  protected $recommendedPlans;

  protected $recommendedDecoders;

  /**
   * @param $billing_id
   * @return object|null
   */
  public function getCustomerAccount($billing_id) {
    if (empty($this->customerAccout)) {
      $response = $this->service->getDARByBillingaccountId($billing_id);
      $this->customerAccout = $response->customerAccountList[0] ?? NULL;
    }
    return $this->customerAccout;
  }

  /**
   * Get all data plan card for api
   *
   * @param string $billing_id
   *  The billing account id number.
   * @param string|bool $return_recommended_plan
   *  The recommended plan id for returning the plan data.
   *  False when is a flag for return all recommended plans data.
   *
   * @return array
   *  associative array containing the plans
   */
  public function get($billing_id, $return_recommended_plan = FALSE) {
    $this->service->setConfig($this->configBlock);
    $validation_plan_type = $this->service->getValidationPlanCode($billing_id);
    if (isset($validation_plan_type->code) && $validation_plan_type->code != "2000") {
      return [
        'noData' => [
          'value' => 'hide',
        ],
        'message' => [
          'value' =>  isset($validation_plan_type->message)
                      ? (($validation_plan_type->message == "NOT FOUND")
                        ? $this->configBlock["message"]["empty"]["label"]
                        : $validation_plan_type->message)
                      : '',
          'show' =>   true,
        ],
      ];
    }
    $recommend_products = $this->service->getRecommendProductsData($billing_id);
    $products = $this->service->getProductList($billing_id);

    $data = [];
    if (!empty($recommend_products) && !empty($products)) {
      $this->service->setConfig($this->configBlock);

      $recommended_offers_config = $this->configBlock['recommendedOffers']['fields'];

      $data['comparative'] = TRUE;
      $data['planCard'] = [
        'title' => [
          'label' => $recommended_offers_config['static']['plan']['label'],
          'show' => (!empty($recommended_offers_config['static']['plan']['show'])) ? TRUE : FALSE,
        ],
        'tax' => [
          'label' => $recommended_offers_config['static']['iva']['label'],
          'show' => (!empty($recommended_offers_config['static']['iva']['show'])) ? TRUE : FALSE,
        ],
      ];

      $recommended_offers = $recommended_offers_verification = [];
      $product_configs = $this->getProductConfigs();

      if ($return_recommended_plan) {
        foreach ($recommend_products as $key => $recommendProduct) {
          $recommended_offer_id = $recommendProduct->productOfferingUUID;
          if ($recommended_offer_id != $return_recommended_plan) {
            unset($recommend_products[$key]);
          }
        }
        if (count($recommend_products) > 1) {
          $key = array_key_first($recommend_products);
          $recommend_products = [$recommend_products[$key]];
        }
      }
      $current_product_list = $this->getCurrentProductData($products);
      $pos_decoder = array_search('DECODER', array_column($current_product_list->offeringList, 'key'));
      $count_current_decoder = $current_product_list->offeringList[$pos_decoder]['value'];
      $current_price = $this->getCurrentProductPrice($products);

      foreach ($recommend_products as $key => $recommendProduct) {
        $recommend_product_data = $this->getProductData($recommendProduct);
        $recommended_offer_id = $recommendProduct->productOfferingUUID;
        $plan_name = $recommendProduct->commercialEnrichmentsList[0]->descriptions->entries[1]->value;
        $amount = $recommendProduct->pricesList[0]->price->taxIncludedAmount;
        $recommended_offers_verification[$recommended_offer_id]['planId'] = $recommended_offer_id;
        $recommended_offers_verification[$recommended_offer_id]['planType'] =
          $this->getPlanTypeFromCategoriesList($recommendProduct->categoriesList ?? []);
        $recommended_offers_verification[$recommended_offer_id]['planName'] = $this->upgradeUtils->getFormatLowerCase($plan_name, TRUE);
        $recommended_offers_verification[$recommended_offer_id]['productOfferingUUID'] = $recommended_offer_id;
        // OfferingPlan vs CurrentPlan.
        foreach ($recommend_product_data as $items) {
          $product_name = $items['key'];
          $product_name_label = $this->upgradeUtils->getProductConfigField($product_configs, $product_name, 'label');
          $product_name_show = $this->upgradeUtils->getProductConfigField($product_configs, $product_name, 'show');
          $new_product = $items['value'];
          $product_name_format = '';
          if (isset($product_configs[strtolower(rtrim($product_name))]['format'])) {
            $product_name_format = $product_configs[strtolower(rtrim($product_name))]['format'];
          }
          if ($new_product > 0 && $product_name_show) {
            $new_product_formatted = trim($this->upgradeUtils->getProductFormatValue($new_product, $product_name_format));
            $recommended_offers_verification[$recommended_offer_id]['offers'][] = "{$product_name_label} {$new_product_formatted}";
          }
        }
        $recommended_offers_verification_products  = implode(" + ", $recommended_offers_verification[$recommended_offer_id]['offers']);
        $recommended_offers_verification[$recommended_offer_id]['formattedValue'] = $recommended_offers_verification_products;
        $recommended_offers_verification[$recommended_offer_id]['productsPrice'] = $amount;
        $recommended_offers_verification[$recommended_offer_id]['customerAccountId'] = $billing_id;
        $recommended_offers_verification[$recommended_offer_id]['current_plan_id'] = $products[0]->planId;
        $recommended_offers_verification[$recommended_offer_id]['current_plan_name'] = $products[0]->plantName;
      }

      if ($return_recommended_plan) {
        $recommended_offers_verification[$recommended_offer_id]['needsWO'] = $this->newPlanNeedsWO($products, $recommendProduct);
        return $recommended_offers_verification;
      }

      $current_plans = $this->currentPlanAnalyzer($products);
      $recommended_plans = $this->recommendedPlanAnalyzer($recommend_products);

      // Order By Amount and Featured.
      $this->upgradeUtils->setConfig($this->configBlock);
      $recommended_offers = $this->buildPlanList($current_plans, $recommended_plans);

      $data['planList'] = array_values($recommended_offers);
      $data['verificationPlan'] = $this->getVerificationPlan($billing_id, $recommended_offers_verification);
      $data['upgradeProcessMessages'] = $this->getUpgradeProcessMessages();
      $count_decoders = $this->configBlock['generalConfig']['decoders'];
      $options = array();

      for ($i = $count_current_decoder; $i<= $count_decoders; $i++) {
        $values = array();
        $values["value"] = $i;
        $values["formattedValue"] = $i;
        array_push($options, $values);
      }

    } else {
      return [
        'noData' => [
          'value' => 'hide',
        ],
        'message' => [
          'value' => $this->configBlock["message"]["empty"]["label"],
          'show' => true,
        ],
      ];
    }

    return $data;
  }

  /**
   * Get if the current plan has any internet product
   *
   * @param object $current_plan
   *  The current plan information
   *
   * @return bool
   *  Returns true if the plan has internet product
   *  false otherwise
   */
  public function currentPlanHasInternetProduct($current_plan) {
    foreach ($current_plan->productList as $product) {
      if ($product->productType == 'INTERNET') {
        return true;
      }
    }
    return false;
  }

  /**
   * Let us know if the new plans needs a Working Order
   *
   * @param array $current_plan
   *  Current contracted plan
   * @param object $new_plan
   *  The plan to be upgraded to
   *
   * @return bool
   *  true when the new plans needs a Working Order, false otherwise.
   */
  public function newPlanNeedsWO($current_plan, $new_plan) {
    $plan_needs_wo = false;

    $current_plan_type = strstr($current_plan[0]->planType, 'SINGLE') ? 'SINGLE' : $current_plan[0]->planType;
    $formated_current_plan = $this->currentPlanAnalyzer($current_plan);
    $formated_current_plan = array_shift($formated_current_plan);
    $current_plan_decoders = $formated_current_plan["decoders"];
    $current_plan_internet = $this->currentPlanHasInternetProduct($current_plan[0]);

    $formated_new_plan = $this->recommendedPlanAnalyzer([$new_plan]);
    $formated_new_plan = array_shift($formated_new_plan);
    $new_plan_type = $formated_new_plan["type"];
    $new_plan_decoders = $formated_new_plan['offers']["DECODER"]["value"]; // ! Validar esto
    $new_plan_internet = isset($formated_new_plan['offers']['INTERNET']) ;

    // Decoders validation
    if (isset($current_plan_decoders) && isset($new_plan_decoders)
      && ($current_plan_decoders < $new_plan_decoders)) {
        $plan_needs_wo = true;
    }

    // if the internet was added then it needs a working order
    if (!$plan_needs_wo && !$current_plan_internet && $new_plan_internet) {
      $plan_needs_wo = true;
    }

    // the last comparisons are about the plan type
    if (!$plan_needs_wo && $current_plan_type != 'TRIPLE PLAY ') {
      if (!$plan_needs_wo && $current_plan_type == 'SINGLE' && in_array($new_plan_type, ['BUNDLED', 'TRIPLE PLAY'])) {
        $plan_needs_wo = true;
      }
      if (!$plan_needs_wo && $current_plan_type == 'BUNDLED' && $new_plan_type == 'TRIPLE PLAY') {
        $plan_needs_wo = true;
      }
    }

    return $plan_needs_wo;
  }

  public function searchInternetProduct($recommend_product_data) {
    $keys = array_column($recommend_product_data, 'key');
    $internet_value = 0;
    foreach ($keys as $k => $key) {
      $pos = strpos($key, 'BBI');
      if ($pos !== false) {
        $internet_value = $recommend_product_data[$k]['value'];
      }
    }
    return $internet_value;
  }


  /**
   * @param $products
   * @return int
   */
  public function getCurrentProductPrice($products) {
    $price = 0;

    foreach ($products[0]->productList as $product) {
      foreach ($product->offeringList as $p) {
        if (isset($p->priceAmount)) {
          $price = $price + $p->priceAmount;
        }
      }
    }

    return $price;
}


  /**
   * {@inheritdoc}
   */
  public function getStrposNameProducts($name1, $name2) {
    $flag = FALSE;

    if (strpos(strtolower($name1), strtolower($name2)) !== FALSE) {
      $flag = TRUE;
    }

    if (strpos(strtolower($name2), strtolower($name1)) !== FALSE) {
      $flag = TRUE;
    }

    return $flag;
  }

  /**
   * @param $item_key
   * @param $current_value
   * @param $new_value
   * @return array[]
   */
  public function buildPlanItem($item_key, $item_name, $current_value, $new_value) {
    if (empty($this->product_configs)) {
      $this->product_configs = $this->getProductConfigs();
    }
    $product_name_format = $this->upgradeUtils->getProductConfigField($this->product_configs, $item_key, 'format');
    $product_name_label = $this->upgradeUtils->getProductConfigField($this->product_configs, $item_key, 'label');
    $product_name_show = $this->upgradeUtils->getProductConfigField($this->product_configs, $item_key, 'show');
    $product_name_class = $this->upgradeUtils->getProductConfigField($this->product_configs, $item_key, 'class');
    $icon_comparative = $this->upgradeUtils->getProductConfigField($this->product_configs, $item_key, 'icon');
    return [
      'productName' => [
        'value' => $item_key,
        'label' => $product_name_label == $item_key ? $item_name : $product_name_label,
        'show' => $product_name_show,
        'class' => $product_name_class,
      ],
      'currentProduct' => [
        'value' => $current_value,
        'formattedValue' => $this->upgradeUtils->getProductFormatValue($current_value, $product_name_format),
        'class' => $this->upgradeUtils->getProductClass($current_value, $icon_comparative),
      ],
      'newProduct' => [
        'value' => $new_value,
        'formattedValue' => $this->upgradeUtils->getProductFormatValue($new_value, $product_name_format),
        'class' => $this->upgradeUtils->getProductClass($new_value, $icon_comparative),
      ],
    ];
  }

  /**
   * @param $current_plans
   * @param $recommended_plans
   * @return array
   */
  public function buildPlanList($current_plans, $recommended_plans) {
    $plan_list = [];
    foreach ($recommended_plans as $rpk => $recommended_plan) {
      // Offers
      $current_offers = array_values($current_plans)[0]['offers'] ?? [];
      $recommended_offers = &$recommended_plan['offers'];
      // Plan info
      $plan_list[$rpk]['featured'] = FALSE;
      $plan_list[$rpk]['planId'] = $recommended_plan['id'];
      $plan_list[$rpk]['planType'] = $recommended_plan['type'];
      $plan_list[$rpk]['planName'] = [
        'value' => $recommended_plan['name'],
        'formattedValue' => $this->upgradeUtils->getFormatLowerCase($recommended_plan['name'], TRUE),
        'show' => TRUE,
      ];
      $plan_list[$rpk]['price'] = [
        'value' => [
          'amount' => $recommended_plan['price'],
          'currencyId' => $recommended_plan['currency'],
        ],
        'formattedValue' => $this->service->formatCurrency($recommended_plan['price'], TRUE),
        'show' => TRUE
      ];
      $plan_list[$rpk]['products']['offersList'] = [];
      $offers_list = &$plan_list[$rpk]['products']['offersList'];
      // Recommended Offers
      foreach ($recommended_offers as $ido => $offer) {
        $offer_current_value = 0;
        if (!empty($current_offers[$ido])) {
          $offer_current_value = $current_offers[$ido]['value'];
          unset($current_offers[$ido]);
        }
        // Offer info
        $offers_list[] = $this->buildPlanItem($offer['key'], $offer['name'], $offer_current_value, $offer['value']);
      }
      // Current Offers
      foreach ($current_offers as $ido => $offer) {
        // Offer info
        $offers_list[] = $this->buildPlanItem($offer['key'], $offer['name'], $offer['value'], 0);
      }
    }
    $plan_list_order = $this->configBlock['generalConfig']['orderPlansByAmount']['value'];
    if ($plan_list_order != 'n') {
      $plan_list = $this->orderPlanListByAmount($plan_list, ($plan_list_order == 'a'));
    }
    return $plan_list;
  }

  /**
   * Orders the list of plans acording the price taking the $ascending_order value
   *
   * @param array $plan_list
   * @param bool $ascending_order
   *
   * @return array
   */
  public function orderPlanListByAmount($plan_list, $ascending_order = true) {
    usort($plan_list, function($a, $b) use ($ascending_order) {
      if ($ascending_order) {
        return $a["price"]["value"]["amount"] > $b["price"]["value"]["amount"];
      }
      else {
        return $a["price"]["value"]["amount"] < $b["price"]["value"]["amount"];
      }
      return 0;
    });
    return $plan_list;
  }

  /**
   * {@inheritdoc}
   */
  public function getVerificationPlan($billing_id, $recommended_offers_verification = []) {

    $config_verification = (!empty($this->configBlock['recommendedOffers']['verification']['fields'])) ?
      $this->configBlock['recommendedOffers']['verification']['fields'] : [];
    $data['verificationPlan']['title'] = [
      'value' => (!empty($config_verification['title']['label'])) ? $config_verification['title']['label'] : '',
      'show' => (!empty($config_verification['title']['show'])) ? TRUE : FALSE,
    ];
    $date = new \DateTime('now');
    $format_date = (!empty($config_verification['date']['formatDate'])) ? $config_verification['date']['formatDate'] : 'short';

    return [
      'title' => [
        'value' => (!empty($config_verification['title']['label'])) ? $config_verification['title']['label'] : '',
        'show' => (!empty($config_verification['title']['show'])) ? TRUE : FALSE,
      ],
      'detail' => [
        'value' => (!empty($config_verification['detail']['label'])) ? $config_verification['detail']['label'] : '',
        'show' => (!empty($config_verification['detail']['show'])) ? TRUE : FALSE,
      ],
      'upgradePlan' => [
        'label' => (!empty($config_verification['plan']['label'])) ? $config_verification['plan']['label'] : '',
        'values' => array_values($recommended_offers_verification),
        'show' => (!empty($config_verification['plan']['show'])) ? TRUE : FALSE,
      ],
      'account' => [
        'label' => (!empty($config_verification['bill']['label'])) ? $config_verification['bill']['label'] : '',
        'value' => $this->upgradeUtils->getFormatAccount($billing_id),
        'show' => (!empty($config_verification['bill']['show'])) ? TRUE : FALSE,
      ],
      'price' => [
        'label' => (!empty($config_verification['price']['label'])) ? $config_verification['price']['label'] : '',
        'value' => isset($recommended_offers_verification['productsPrice'])
                    ? $this->service->formatCurrency($recommended_offers_verification['productsPrice'], TRUE)
                    : '',
        'show'  => (!empty($config_verification['price']['show'])) ? TRUE : FALSE,
      ],
      'activateDate' => [
        'label' => (!empty($config_verification['date']['label'])) ? $config_verification['date']['label'] : '',
        'value' => $this->homeUtils->formatDate($date->getTimestamp(), $format_date),
        'show' => (!empty($config_verification['date']['show'])) ? TRUE : FALSE,
      ],
      'termsConditions' => [
        'label' => (!empty($config_verification['terms']['label'])) ? $config_verification['terms']['label'] : '',
        'url' => (!empty($config_verification['terms']['url'])) ? $config_verification['terms']['url'] : '#',
        'value' => (!empty($config_verification['termsDesc']['value'])) ? $config_verification['termsDesc']['value'] : '',
        'show' => (!empty($config_verification['terms']['show'])) ? TRUE : FALSE,
      ],
    ];

  }

  public function getProductData($recommended_product) {
    $products_list_data = [];

    $this->searchChanelsCount($recommended_product->bundledItemsList, $products_list_data);
    $i = sizeof($this->productsListData);

    foreach ($this->bundledItemsList as $item) {
      foreach ($item->commercialEnrichmentsList[0]->descriptions as $entry) {
        if (!in_array($entry[0]->value, array_column($this->productsListData, 'key'))) {
          if (str_contains($entry[0]->value, 'BBI')) { // obtener posicion de decoder
            $this->productsListData[$i]['key'] = "INTERNET";
            $this->productsListData[$i]['value'] = $this->getSpeed($entry[1]->value);

          }
          else {
            $this->productsListData[$i]['key'] = trim($entry[0]->value);
            $this->productsListData[$i]['value'] = 1;
          }



        }
      }
        $i++;
      }


    return $this->productsListData;
  }

  /**
   * @param $str
   * @return mixed
   */
  public function getTVs($str) {
    preg_match_all('!\d+ TV!', $str, $matches);
    if (!empty($matches[0][0])) {
      return str_replace(' TV', '', $matches[0][0]);
    }
    return 0;
  }

  /**
   * @param $str
   * @return mixed
   */
  public function getSpeed($str) {
    preg_match('/\d+\s*MB|\d+\s*Mb|\d+\s*mb/', $str, $matches);
    $speed = $matches[0] ?? 0;
    return (int) trim(str_replace(['MB', 'mb'], '', $speed));
  }

  /**
   * @param $str
   * @return array
   */
  public function getChannels($str) {
    preg_match_all('/\d+\D+/', $str, $matches);
    $total_channels = 0;
    $list = $matches[0] ?? [];
    foreach ($list as $k => $_num) {
      $sufix = 'SD_CHANNELS';
      if (stripos($_num, ' hd') !== FALSE
       || stripos($_num, 'hd ') !== FALSE) {
        $sufix = 'HD_CHANNELS';
      }
      elseif (stripos($_num, 'music') !== FALSE
       || stripos($_num, 'músic') !== FALSE) {
        $sufix = 'MS_CHANNELS';
      }
      $list[$sufix] = (int) filter_var($_num, FILTER_SANITIZE_NUMBER_INT);
      $total_channels += $list[$sufix];
      unset($list[$k]);
    }
    if ($list) {
      $list['CHANNELS'] = $total_channels;
    }
    return $list;
  }

  /**
   * @param $bundledItemsList
   * @param $products_list_data
   */
  public function searchChanelsCount($bundled_items_list, $products_list_data) {
    $chanel_hd_count = 0;
    $chanel_sd_count = 0;
    $chanel_music_count = 0;
    $decoder_count = 0;

    foreach ($bundled_items_list as $key => $item) {
      foreach ($item->commercialEnrichmentsList[0]->descriptions as $entry) {

        if (str_contains($entry[0]->value, 'DECODER')) { // cantidad de decodificadores
          $decoder_count++;
          $flag = true;
          }

        else {
          $values = explode(' ', $entry[1]->value);
          $val = 0;
          $flag = false;
          foreach ($values as $value) {
            if (is_numeric($value)) {
              $val = $value;
            }
            if (str_contains($value, 'HD')) {
              $chanel_hd_count = $chanel_hd_count + $val;
              $val = 0;
              $flag = true;
            }
            if (str_contains($value, 'Música') || str_contains($value, 'música') || str_contains($value, 'musica')) {
              $chanel_music_count = $chanel_music_count + $val;
              $val = 0;
              $flag = true;
            }
            if (str_contains($value, 'SD') || str_contains($value, 'Estándar')) {
              $chanel_sd_count = $chanel_sd_count + $val;
              $val = 0;
              $flag = true;
            }
        }
          }
          if ($flag) {
            unset($bundled_items_list[$key]);
          }
      }

    }

    // the default decoder is added
    if ($decoder_count > 0) {
      $decoder_count ++;
    }

    $products_list_data[0]['key'] = 'Canales Hd';
    $products_list_data[0]['value'] = $chanel_hd_count;
    $products_list_data[1]['key'] = 'Canales Sd';
    $products_list_data[1]['value'] = $chanel_sd_count;
    $products_list_data[2]['key'] = 'Canales Musica';
    $products_list_data[2]['value'] = $chanel_music_count;
    $products_list_data[3]['key'] = 'CHANNELS';
    $products_list_data[3]['value'] = $chanel_hd_count + $chanel_sd_count + $chanel_music_count ;
    $products_list_data[4]['key'] = 'DECODER';
    $products_list_data[4]['value'] = $decoder_count;
    $this->bundledItemsList = $bundled_items_list;
    $this->productsListData = $products_list_data;
  }

  /**
   * @param array $categories_list
   * @return string
   */
  public function getPlanTypeFromCategoriesList($categories_list = []) {
    $plan_type = 'SINGLE';
    if (!empty($categories_list)) {
      foreach ($categories_list as $category) {
        if ($category->categoryType == 'PlanType') {
          return $category->id ?? $plan_type;
        }
      }
    }
    return $plan_type;
  }

  /**
   * @param $recommended_plans
   * @return array
   */
  public function recommendedPlanAnalyzer($recommended_plans) {
    $result = [];
    foreach ($recommended_plans as $plan) {
      $plan_name = $plan->commercialEnrichmentsList[0]->descriptions->entries[1]->value ?? '';
      $plan_id = $plan->productOfferingUUID;
      $plan_key = $plan_id;
      $plan_price = $plan->pricesList[0]->price->taxIncludedAmount ?? 0;
      // COUNT DECODER ACCORDING PLAN NAME
      $decoders_by_plan = $this->getTVs($plan_name);
      $decoders_by_offer_name = 1;
      $decoders_by_offer_type = 0;
      $base_plan = [
        'id' => $plan_id,
        'key' => $plan_key,
        'name' => $plan_name,
        'type' => NULL,
        'value' => 0,
      ];
      if (empty($plan->bundledItemsList)) {
        $result[$plan_id]['id'] = $plan_id;
        $result[$plan_id]['name'] = $plan_name;
        $result[$plan_id]['price'] = $plan_price;
        $result[$plan_id]['currency'] = 'Bs';
        $result[$plan_id]['decoders'] = $this->recommendedDecoders[$plan_id] = $decoders_by_plan;
        $result[$plan_id]['offers'][$plan_key] = $base_plan;
        continue;
      }
      foreach ($plan->bundledItemsList as $bundle_key => $offer) {
        $_offer_name = trim($offer->commercialEnrichmentsList[0]->descriptions->entries[1]->value ?? '');
        $_offer_id = $offer->id;
        $_offer_key = $_offer_id;
        $_offer = array_merge($base_plan, [
          'id' => $_offer_id,
          'key' => $_offer_key,
          'name' => $_offer_name,
          'value' => 1,
        ]);
        if (stripos($_offer['name'], 'canal') !== FALSE
          && stripos($_offer['name'], 'TIGO SPORT') === FALSE) {
          // COUNT CHANNELS
          $channels = $this->getChannels($_offer_name);
          foreach ($channels as $key => $value) {
            $_offer_key = $key;
            $_offer = array_merge($_offer, [
              'key' => $_offer_key,
              'name' => $_offer_key,
              'value' => $value,
            ]);
            $result[$plan_id]['id'] = $plan_id;
            $result[$plan_id]['name'] = $plan_name;
            $result[$plan_id]['price'] = $plan_price;
            $result[$plan_id]['currency'] = 'Bs';
            $result[$plan_id]['decoders'] =
              $this->recommendedDecoders[$plan_id] = $decoders_by_plan ?: max($decoders_by_offer_name, $decoders_by_offer_type);
            $result[$plan_id]['offers'][$_offer_key] = $_offer;
          }
          continue;
        }
        if (stripos($_offer['name'], 'DECODER') !== FALSE) {
          // COUNT ADICIONAL DECODERS
          $_offer_key = 'DECODER';
          $decoders_by_offer_name += 1;
          $_offer = array_merge($_offer, [
            'key' => $_offer_key,
            'name' => $_offer_key,
            'value' => max($decoders_by_offer_name, $decoders_by_plan),
          ]);
        }
        if (stripos($_offer['type'], 'DECODER') !== FALSE) {
          // COUNT DECODER DEVICES
          $_offer_key = 'DECODER';
          $decoders_by_offer_type += 1;
          $_offer = array_merge($_offer, [
            'key' => $_offer_key,
            'name' => $_offer_key,
            'value' => max($decoders_by_offer_type, $decoders_by_plan),
          ]);
        }
        if (stripos($_offer['name'], 'mb') !== FALSE
          || stripos($_offer['name'], 'internet') !== FALSE) {
          $_offer_key = 'INTERNET';
          $_offer['key'] = $_offer_key;
          $_offer['value'] = $this->getSpeed($_offer['name']);
        }
        $result[$plan_id]['id'] = $plan_id;
        $result[$plan_id]['name'] = $plan_name;
        $result[$plan_id]['price'] = $plan_price;
        $result[$plan_id]['currency'] = 'Bs';
        $result[$plan_id]['type'] = $this->getPlanTypeFromCategoriesList($plan->categoriesList ?? []);
        $result[$plan_id]['decoders'] =
          $this->recommendedDecoders[$plan_id] = $decoders_by_plan ?: max($decoders_by_offer_name, $decoders_by_offer_type);
        $result[$plan_id]['offers'][$_offer_key] = $_offer;
      }
    }
    return $result;
  }

  /**
   * @param $current_plans
   * @return array
   */
  public function currentPlanAnalyzer($current_plans) {
    $has_tv_product = 0;
    $_current_plans = [];
    foreach ($current_plans ?? [] as $plan) {
      $_plan_name = $plan->planName ?? ($plan->plantName ?? '');
      $_plan_id = $plan->planId;
      $_plan_key = $_plan_id;
      // COUNT DECODER ACCORDING PLAN NAME
      $decoders_by_plan = $this->getTVs($_plan_name);
      $decoders_by_offer_name = 1;
      $decoders_by_offer_type = 0;
      $_plan = [
        'id' => $_plan_id,
        'key' => $_plan_key,
        'name' => $_plan_name,
        'type' => $plan->planType,
        'value' => 0,
      ];
      if (empty($plan->productList)) {
        $_current_plans[$_plan_id]['id'] = $_plan_id;
        $_current_plans[$_plan_id]['name'] = $_plan_name;
        $_current_plans[$_plan_id]['price'] = 0;
        $_current_plans[$_plan_id]['currency'] = 'Bs';
        $_current_plans[$_plan_id]['decoders'] = $this->currentDecoders[$_plan_id] = $decoders_by_plan;
        $_current_plans[$_plan_id]['offers'][$_plan_key] = $_plan;
        continue;
      }
      foreach ($plan->productList ?? [] as $product) {
        $_product_key = $product->productType == 'INTERNET' ? 'INTERNET' : $product->productId;
        switch ($product->productType) {
          case 'TV':
            $product->offeringList[] = (object) [
              'offeringName' => $product->productDetail,
              'offeringId' => $product->productId,
              'offeringType' => 'TV'
            ];
            $value = 1;
            break;
          case 'INTERNET':
            $value = $this->getSpeed($product->productDetail);
            break;
          default:
            $value = 1;
        }
        $_product = array_merge($_plan, [
          'id' => $product->productId,
          'key' => $_product_key,
          'name' => $product->productName,
          'type' => $product->productType,
          'value' => $value,
        ]);
        $has_tv_product = empty($has_tv_product) && $product->productType == 'TV' ? 1 : $has_tv_product;
        $offers_and_devices = array_merge($product->offeringList ?? [], $product->deviceList ?? []);
        if (empty($offers_and_devices)) {
          $_current_plans[$_plan_id]['id'] = $_plan_id;
          $_current_plans[$_plan_id]['name'] = $_plan_name;
          $_current_plans[$_plan_id]['price'] = 0;
          $_current_plans[$_plan_id]['currency'] = 'Bs';
          $_current_plans[$_plan_id]['decoders'] = $this->currentDecoders[$_plan_id] = max($has_tv_product, $decoders_by_plan);
          $_current_plans[$_plan_id]['offers'][$_product_key] = $_product;
          continue;
        }
        foreach ($offers_and_devices ?? [] as $offer) {
          $_offer_name = $offer->offeringName ?? ($offer->serialNumber ?? NULL);
          $_offer_id = $offer->offeringId ?? ($offer->serialNumber ?? NULL);
          $_offer_key = $_offer_id;
          $processed = false;
          $is_addon = (isset($offer->isAddon) && $offer->isAddon == "true");
          $_offer = array_merge($_product, [
            'id' => $_offer_id,
            'key' => $_offer_key,
            'name' => $_offer_name,
            'type' => $offer->offeringType ?? ($offer->type ?? $_product['type']),
            'value' => 1,
          ]);
          if (stripos($_offer['name'], 'canal') !== FALSE
            && stripos($_offer['name'], 'TIGO SPORT') === FALSE) {
            // COUNT CHANNELS
            $channels = $this->getChannels($_offer_name);
            foreach ($channels as $key => $value) {
              $_offer_key = $key;
              $_offer = array_merge($_offer, [
                'key' => $_offer_key,
                'name' => $_offer_key,
                'value' => $value,
              ]);
              $_current_plans[$_plan_id]['id'] = $_plan_id;
              $_current_plans[$_plan_id]['name'] = $_plan_name;
              $_current_plans[$_plan_id]['price'] = 0;
              $_current_plans[$_plan_id]['currency'] = 'Bs';
              $_current_plans[$_plan_id]['decoders'] = $this->currentDecoders[$_plan_id] = $decoders_by_plan
                ?: max($decoders_by_offer_name, $decoders_by_offer_type);
              $_current_plans[$_plan_id]['offers'][$_offer_key] = $_offer;
            }
            continue;
          }
          if (stripos($_offer['name'], 'DECODER') !== FALSE) {
            // COUNT ADICIONAL DECODERS
            $_offer_key = 'DECODER';
            $decoders_by_offer_name += 1;
            $_offer = array_merge($_offer, [
              'key' => $_offer_key,
              'name' => $_offer_key,
              'value' => max($decoders_by_offer_name, $decoders_by_plan),
            ]);
            $processed = true;
          }
          if (stripos($_offer['type'], 'DECODER') !== FALSE) {
            // COUNT DECODER DEVICES
            $_offer_key = 'DECODER';
            $decoders_by_offer_type += 1;
            $_offer = array_merge($_offer, [
              'key' => $_offer_key,
              'name' => $_offer_key,
              'value' => max($decoders_by_offer_type, $decoders_by_plan),
            ]);
            $processed = true;
          }
          if (stripos($_offer['name'], 'mb') !== FALSE
          || stripos($_offer['name'], 'internet') !== FALSE) {
            $_offer_key = 'INTERNET';
            $_offer['key'] = $_offer_key;
            $_offer['value'] = $this->getSpeed($_offer['name']);
            $processed = true;
          }
          if (!$processed && $is_addon) {
            continue;
          }
          $_current_plans[$_plan_id]['id'] = $_plan_id;
          $_current_plans[$_plan_id]['name'] = $_plan_name;
          $_current_plans[$_plan_id]['price'] = 0;
          $_current_plans[$_plan_id]['currency'] = 'Bs';
          $_current_plans[$_plan_id]['decoders'] =
            $this->currentDecoders[$_plan_id] = $decoders_by_plan ?: max($decoders_by_offer_name, $decoders_by_offer_type, $has_tv_product);
          $_current_plans[$_plan_id]['offers'][$_offer_key] = $_offer;
        }
      }
    }
    return $_current_plans;
  }

  public function getCurrentProductData($current) {

    $this->currentOffers = $this->currentPlanAnalyzer($current);

    $products_list_data = new \stdClass();
    $i = 0;
    $count = 0;
    $key_decoder = 0;
    $chanel_hd_count = 0;
    $chanel_sd_count = 0;
    $chanel_music_count = 0;

    foreach ($current[0]->productList as $item) {
      $array = $item->offeringList;
      if (isset($item->productType)) {
         if ($item->productType == "TV") {
           $values = explode(' ', $item->offeringList[0]->offeringName);
           $val = 0;

           foreach ($values as $value) {
             if (is_numeric($value)) {
               $val = $value;
             }
             if (str_contains($value, 'HD')) {
               $chanel_hd_count = $chanel_hd_count + $val;
               $val = 0;
             }
             if (str_contains($value, 'Música') || str_contains($value, 'música') || str_contains($value, 'musica')) {
               $chanel_music_count = $chanel_music_count + $val;
               $val = 0;
             }
             if (str_contains($value, 'SD') || str_contains($value, 'Estándar')) {
               $chanel_sd_count = $chanel_sd_count + $val;
               $val = 0;
             }
           }
           $products_list_data->offeringList[0]['key'] = 'Canales Hd';
           $products_list_data->offeringList[0]['value'] = $chanel_hd_count;
           $products_list_data->offeringList[1]['key'] = 'Canales Sd';
           $products_list_data->offeringList[1]['value'] = $chanel_sd_count;
           $products_list_data->offeringList[2]['key'] = 'Canales Musica';
           $products_list_data->offeringList[2]['value'] = $chanel_music_count;
           $products_list_data->offeringList[3]['key'] = 'CHANNELS';
           $products_list_data->offeringList[3]['value'] = $chanel_hd_count + $chanel_sd_count + $chanel_music_count ;
           $i = sizeof($products_list_data->offeringList);
         } else {
           $products_list_data->offeringList[$i]['key'] = $item->productType;
           if (isset($item->offeringList[0]->offeringName)) {
            $products_list_data->offeringList[$i]['value'] = $item->offeringList[0]->offeringName;
           }
         }

      // unset($item->offeringList[0]); // se elimina la primera posicion , donde llega el valor del producto
        $i++;
        $array = array_slice($item->offeringList, 1);
      }

      foreach ($array as $entry) {

        if (!in_array($entry->offeringName, array_column($products_list_data->offeringList, 'key'))) {
          if (!empty($entry->offeringName) && str_contains($entry->offeringName, 'DECODER')) {
            $key_decoder = $i;
          }
          $products_list_data->offeringList[$i]['key'] = $entry->offeringName;
          $products_list_data->offeringList[$i]['value'] = 1;

        }
        if (!empty($entry->offeringName) && str_contains($entry->offeringName, 'DECODER')) {
          $count++;
        }
       $i++;
      }
    }

    $products_list_data->offeringList[$key_decoder]['key'] = 'DECODER';
    $products_list_data->offeringList[$key_decoder]['value'] = $this->currentDecoders;


    sort($products_list_data->offeringList);
    return $products_list_data;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataConfig($data) {
    $data_config = [];
    if (!isset($data['noData'])) {

      $config_actions = (isset($this->configBlock['recommendedOffers']['actions']['card'])) ?
        $this->configBlock['recommendedOffers']['actions']['card'] : [];

      $url = (!empty($config_actions['recommendedOffersAction']['url'])) ?
        $config_actions['recommendedOffersAction']['url'] : [];

      $data_config['actions']['upgradePlan'] = [
        'label' => (!empty($config_actions['recommendedOffersAction']['label'])) ?
          $config_actions['recommendedOffersAction']['label'] : '',
        'show' => (!empty($config_actions['recommendedOffersAction']['show'])) ? TRUE : FALSE,
        'type' => (!empty($config_actions['recommendedOffersAction']['type'])) ?
          $config_actions['recommendedOffersAction']['type'] : 'button',
        'url' => $this->upgradeUtils->getUrlByOrigin($url),
      ];

      $url_purchase = (!empty($config_actions['verificationActionAccept']['url'])) ?
        $config_actions['verificationActionAccept']['url'] : [];
      $url_cancel = (!empty($config_actions['verificationActionCancel']['url'])) ?
        $config_actions['verificationActionCancel']['url'] : [];

      $data_config['actions']['verificationActions'] = [
        'purchase' => [
          'label' => (!empty($config_actions['verificationActionAccept']['label'])) ?
            $config_actions['verificationActionAccept']['label'] : '',
          'show' => (!empty($config_actions['verificationActionAccept']['show'])) ? TRUE : FALSE,
          'type' => (!empty($config_actions['verificationActionAccept']['type'])) ?
            $config_actions['verificationActionAccept']['type'] : 'button',
          'url' => $this->upgradeUtils->getUrlByOrigin($url_purchase),
        ],
        'cancel' => [
          'label' => (!empty($config_actions['verificationActionCancel']['label'])) ?
            $config_actions['verificationActionCancel']['label'] : '',
          'show' => (!empty($config_actions['verificationActionCancel']['show'])) ? TRUE : FALSE,
          'type' => (!empty($config_actions['verificationActionCancel']['type'])) ?
            $config_actions['verificationActionCancel']['type'] : 'button',
          'url' => $this->upgradeUtils->getUrlByOrigin($url_cancel),
        ],
      ];

      if (!empty($config_actions['filterPlans'])) {
        $data_config['actions']['filterPlans'] = [
          'label' => $config_actions['filterPlans']['label'] ?? '',
          'type' => $config_actions['filterPlans']['type'] ?? '',
          'show' => !empty($config_actions['filterPlans']['show']),
          'url' => $this->upgradeUtils->getUrlByOrigin($config_actions['filterPlans']['url'] ?? []),
        ];
      }

      if (!empty($config_actions['showPlans'])) {
        $data_config['actions']['showPlans'] = [
          'label' => $config_actions['showPlans']['label'] ?? '',
          'type' => $config_actions['showPlans']['type'] ?? '',
          'show' => !empty($config_actions['showPlans']['show']),
          'url' => $this->upgradeUtils->getUrlByOrigin($config_actions['showPlans']['url'] ?? []),
        ];
      }

      if (!empty($this->configBlock['recommendedOffers']['forms'])) {
        $data_config['forms'] = $this->configBlock['recommendedOffers']['forms'];
      }

      $data_config['forms'] = array_merge_recursive($this->getForms(), $data_config['forms']);

      if (isset($data_config['forms']['plansFilter']['boxesNumber'])) {
        $data_config['forms']['plansFilter']['boxesNumber']['defaultValue'] = min($this->currentDecoders) ?? 0;
        $data_config['forms']['plansFilter']['boxesNumber']['validations']['min'] = min($this->currentDecoders) ?? 0;
        $data_config['forms']['plansFilter']['boxesNumber']['validations']['max'] = max($this->recommendedDecoders) ?? 0;
      }

    }

    return $data_config;
  }

  /**
   * {@inheritdoc}
   */
  public function getForms() {

    $change_plan_fields = (isset($this->configBlock['recommendedOffers']['verification']['changePlanFields'])) ?
      $this->configBlock['recommendedOffers']['verification']['changePlanFields'] : [];

    $confirm_identity_fields = (isset($this->configBlock['recommendedOffers']['verification']['confirmIdentityFields'])) ?
      $this->configBlock['recommendedOffers']['verification']['confirmIdentityFields'] : [];

    $beneficiary_line_fields = (isset($this->configBlock['recommendedOffers']['verification']['beneficiaryLineFields'])) ?
      $this->configBlock['recommendedOffers']['verification']['beneficiaryLineFields'] : [];

    $customer_account_info = $this->getCustomerAccount(\Drupal::request()->get('id'));

    if (!empty($customer_account_info->accountList)) {
      $response = $this->service->getBeneficiary($customer_account_info->accountList[0]->billingAccountId);
      $beneficiary_line = $response->customerAccount->agreements[0]->agreementsItems[0]->products[0]->resources[0]->primaryId ?? '';
      $beneficiary_line_fields['beneficiaryLine']['defaultValue'] = $beneficiary_line;
    }

    $identification_type_options = [];
    if (!empty($confirm_identity_fields['identificationType']['options'])) {
      $change_allowed_values_to_array = $this->service->changeAllowedValuesToArray(
        $confirm_identity_fields['identificationType']['options']
      );

      foreach ($change_allowed_values_to_array as $key => $value) {
        $identification_type_options[] = [
          'id' => $key,
          'value' => $value,
        ];
      }
    }

    $confirm_identity_show = (!empty($confirm_identity_fields['confirmIdentity']['show'])) ? TRUE : FALSE;
    $get_conf_forms['verificationPlan'] = [
      'changePlan' => [
        'label' => (!empty($change_plan_fields['changePlan']['label'])) ? $change_plan_fields['changePlan']['label'] : '',
        'show' => (!empty($change_plan_fields['changePlan']['show'])) ? TRUE : FALSE,
        'options' => [
          'inmediate' => [
            'value' => TRUE,
            'label' => (!empty($change_plan_fields['changePlan']['options']['inmediate']['label'])) ?
              $change_plan_fields['changePlan']['options']['inmediate']['label'] : '',
            'type' => 'radio',
            'description' => (!empty($change_plan_fields['changePlan']['options']['inmediate']['description'])) ?
              $change_plan_fields['changePlan']['options']['inmediate']['description'] : '',
          ],
          'nextMonth' => [
            'value' => FALSE,
            'label' => (!empty($change_plan_fields['changePlan']['options']['nextMonth']['label'])) ?
              $change_plan_fields['changePlan']['options']['nextMonth']['label'] : '',
            'type' => 'radio',
            'description' => (!empty($change_plan_fields['changePlan']['options']['nextMonth']['description'])) ?
              $change_plan_fields['changePlan']['options']['nextMonth']['description'] : '',
          ],
        ],
      ],
      'confirmIdentity' => [
        'label' => (!empty($confirm_identity_fields['confirmIdentity']['label'])) ?
          $confirm_identity_fields['confirmIdentity']['label'] : '',
        'show' => $confirm_identity_show,
        'description' => (!empty($confirm_identity_fields['confirmIdentity']['description'])) ?
          $confirm_identity_fields['confirmIdentity']['description'] : '',
        'fields' => [
          'identificationType' => [
            'label' => (!empty($confirm_identity_fields['identificationType']['label'])) ?
              $confirm_identity_fields['identificationType']['label'] : '',
            'show' => TRUE,
            'type' => 'select',
            'options' => $identification_type_options,
          ],
          'identificationNumber' => [
            'label' => (!empty($confirm_identity_fields['identificationNumber']['label'])) ?
              $confirm_identity_fields['identificationNumber']['label'] : '',
            'show' => TRUE,
            'placeholder' => (!empty($confirm_identity_fields['identificationNumber']['placeholder'])) ?
              $confirm_identity_fields['identificationNumber']['placeholder'] : '',
            'type' => 'number',
            'validations' => [
              'required' => (!empty($confirm_identity_fields['identificationNumber']['required'])) ? TRUE : FALSE,
              'minLength' => (!empty($confirm_identity_fields['identificationNumber']['minLength'])) ?
                $confirm_identity_fields['identificationNumber']['minLength'] : 0,
              'maxLength' => (!empty($confirm_identity_fields['identificationNumber']['maxLength'])) ?
                $confirm_identity_fields['identificationNumber']['maxLength'] : 128,
            ],
            'value' => (!empty($confirm_identity_fields['identificationNumber']['defaultValue'])) ?
              $confirm_identity_fields['identificationNumber']['defaultValue'] : '',
          ],
        ],
      ],
      'beneficiaryLine' => [
        'label' => (!empty($beneficiary_line_fields['beneficiaryLine']['label'])) ?
          $beneficiary_line_fields['beneficiaryLine']['label'] : '',
        'description' => (!empty($beneficiary_line_fields['beneficiaryLine']['description'])) ?
          $beneficiary_line_fields['beneficiaryLine']['description'] : '',
        'show' => TRUE,
        'placeholder' => (!empty($beneficiary_line_fields['beneficiaryLine']['placeholder'])) ?
          $beneficiary_line_fields['beneficiaryLine']['placeholder'] : '',
        'type' => 'number',
        'validations' => [
          'required' => (!empty($beneficiary_line_fields['beneficiaryLine']['required'])) ? TRUE : FALSE,
          'minLength' => (!empty($beneficiary_line_fields['beneficiaryLine']['minLength'])) ?
            $beneficiary_line_fields['beneficiaryLine']['minLength'] : 0,
          'maxLength' => (!empty($beneficiary_line_fields['beneficiaryLine']['maxLength'])) ?
            $beneficiary_line_fields['beneficiaryLine']['maxLength'] : 128,
        ],
        'value' => (!empty($beneficiary_line_fields['beneficiaryLine']['defaultValue'])) ?
          $beneficiary_line_fields['beneficiaryLine']['defaultValue'] : '',
      ]
    ];

    return $get_conf_forms;
  }

  public function getUpgradeProcessMessages() {
    return explode('|', $this->configBlock['recommendedOffers']['upgradeProcessMessage']['message'] ?? '');
  }
}
