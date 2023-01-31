<?php

namespace Drupal\oneapp_mobile_upselling_bo\Services\v2_0;

use Drupal\oneapp\Exception\HttpException;
use Drupal\oneapp_mobile_upselling\Services\v2_0\AvailableOffersRestLogic;

/**
 * Class AvailableOffersRestLogic.
 */
class AvailableOffersRestLogicBo extends AvailableOffersRestLogic {

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $nboProducts = [];

  /**
   * AutopacksService.
   *
   * @var string
   */
  protected $autopacksService;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $primaryNumber;

  /**
   * {@inheritdoc}
   */
  public function get($msisdn) {
    $response = [];
    // Get offers.
    $id = $this->mobileUtils->modifyMsisdnCountryCode($msisdn, TRUE);
    $products = $this->availableOffersServices->getNboOffer($id);

    if (! property_exists($products, 'packages')) {
      $buffer = new \stdClass();
      $buffer->packages = $products;
      $products = $buffer;
    }

    $products->packages->products = $this->orderProductsByPrice($products->packages->products);

    $this->findNboProductsNewOffer($products);
    $loan_offers = $this->getLendingScoring($msisdn);

    if (! count($products->packages->products)) {
      return [];
    }


    // Get billingType by token info.
    $billing_type = $this->getBillingTypeByMsisdn($msisdn);
    // Get info.
    $info = $this->mobileUtils->getInfoMasterAccount($msisdn);

    // Get sections.
    $sections = $this->getAllSectionsFormatted();
    $configs = $this->configBlock['offersList']['fields'];
    $count_section = 0;
    foreach ($sections as $section) {
      if ($this->showSectionByBillingType($billing_type, $section) && $this->showIfIsB2b($section, $info)) {
        $section_row = [];
        $count_category = 0;
        $section_row['label'] = $section['label'];
        $section_row['class'] = $section['class'];
        $section_row['show'] = $section['show'];
        if ($section['categories']) {
          $categories = $this->getAllCategoriesFormatted($section['categories']);
          foreach ($categories as $category) {
            $count_sub_category = 0;
            $category_row = [];
            $category_row['label'] = $category['label'];
            $category_row['show'] = $category['show'];
            $category_row['expand'] = $category['expand'];
            if ($category['subcategories']) {
              foreach ($this->getAllSubCategoriesFormatted($category['subcategories']) as $subcategory) {
                // Find all products by category and subcategory.
                $products_by_category_and_sub_category = $this->findProductByCategoryAndSubCategory(
                  (array) $products, $configs, $category['key'], $subcategory['key']
                );
                // If there are products.
                if ($products_by_category_and_sub_category || $section['showIfEmpty']) {
                  $sub_category_row = [];
                  $sub_category_row['label'] = $subcategory['label'];
                  $sub_category_row['show'] = $subcategory['show'];
                  $sub_category_row['expand'] = $subcategory['expand'];
                  $sub_category_row['products'] = $products_by_category_and_sub_category;
                  $category_row['subcategories'][$count_sub_category] = $sub_category_row;
                  $count_sub_category++;
                }
              }
            }
            else {
              $products_by_category = $this->findProductByCategory($products->packages->products, $configs, $category['key']);
             if ($products_by_category || $section['showIfEmpty'] || $category['id'] == "tigo_te_presta") {
                $category_row['products'] = $products_by_category;
              }


             // Nbo Products.

                if ($category['nbo'] === TRUE) {
                // Get sibling categories.
                // Find products nbo by category.
                $sibling_categories = $this->getNboSibling($categories);
                foreach ($sibling_categories as $siblingCategory) {
                  $nbo_products = $this->getNboProductsByCategory($siblingCategory['key']);
                  if ($nbo_products) {
                    $category_row['products'] = $nbo_products;
                  }
                }
              }

            }
            // If there are products, then attach products.
            if ($this->verifyExistProductsByCategory($category_row, $section)) {
              $section_row['categories'][$count_category] = $category_row;
              $count_category++;
            }
          }
        }
        else {
          $section_row['categories'] = [];
        }
        // If there are not categories.
        if ($this->verifyCategoryBySectionEmpty($section_row, $section)) {
          $response[$count_section] = $section_row;
          $count_section++;
        }
      }
    }
    $this->matchToAutoPackets($response, $msisdn);

    //Loan Products
    if(!empty($loan_offers)) {
      $response = $this->findLoanProducts($response, $loan_offers, $configs);
    }

    return ['sections' => $response];

  }

  /**
   * {@inheritdoc}
   */
  protected function findProductByCategory(array $products, array $configs, $category_name) {
    $count = 0;
    $rows = [];
    foreach ($products as $product) {
      $product->subCategory = !isset($product->subCategory) ? '' : $product->subCategory;
      if ($this->getFormattedCategory($product->category) === $category_name && $product->subCategory === '') {
        // Map products and categorize.
        $remove_offers = (!empty($this->configBlock['config']['removeOffers'])) ?
          $this->configBlock['config']['removeOffers'] : '';

        $exclude = explode(",", $remove_offers);

        if (!in_array($product->productId, $exclude)) {
          $row = $this->getProductMapped($configs, $product);
          $rows[$count] = $row;
          $count++;
        }

      }
    }

    return $rows ? $rows : [];
  }

  /**
   * Verify if there are products and will verify show empty by section.
   *
   * @param mixed $categoryRow
   *   Category Row.
   * @param mixed $section
   *   Section.
   *
   * @return bool
   *   Return true or false.
   */
  public function verifyExistProductsByCategory($categoryRow, $section) {
    return (isset($categoryRow['products']) && $categoryRow['products']) ||
      (isset($categoryRow['subcategories']) && $categoryRow['subcategories']) ||
      (isset($section['showIfEmpty']) && $section['showIfEmpty']) || ($categoryRow['label'] == 'Tigo te presta' || $categoryRow['label'] == 'Tigo te Presta');
  }

  public function findLoanProducts(array $response,array $loan_offers, array $configs) {
    $count = 0;
    $rows = [];
    $internet_index = array_search('Internet',array_column($response,'label'));
    $loan_index = array_search('Tigo te presta',array_column($response[$internet_index]['categories'],'label'));

    if (!is_bool($loan_index)) {
    foreach ($loan_offers as $product_offer) {
            if ($product_offer->productCategory === 'INTERNET') {
              $row = $this->getProductLoanMapped($configs, $product_offer);
              $rows[$count] = $row;
              $count++;
            }
          }

      $response[$internet_index]['categories'][$loan_index]['products'] = $rows;
    }


   return $response;

  }


  /**
   * {@inheritdoc}
   */
  protected function findProductByCategoryAndSubCategory(array $products, array $configs, $category_name, $sub_category_name) {
    $count = 0;
    $rows = [];
    foreach ($products as $product) {
      if ($this->getFormattedCategory($product->subCategory) === $this->getFormattedCategory($sub_category_name) &&
          $this->getFormattedCategory($product->category) === $this->getFormattedCategory($category_name)
        ) {
          \Drupal::logger('debug')->debug('TODO validar si este findProductByCategoryAndSubCategory se necesita en modules/custom/oneapp_mobile_bo/modules/oneapp_mobile_upselling_bo/src/Services/v2_0/AvailableOffersRestLogicBo.php');
        // Map products and categorize.
        $remove_offers = (!empty($this->configBlock['config']['removeOffers'])) ?
          $this->configBlock['config']['removeOffers'] : '';

        $exclude = explode(",", $remove_offers);

      }
    }
    return $rows ? $rows : [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getProductMapped(array $configs, $product) {
    $row = [];
    foreach ($configs as $id => $field) {
      $row[$id] = [
        'label' => $field['label'],
        'show' => $this->utils->formatBoolean($field['show']),
      ];

      switch ($id) {
        case 'offerId':
          $row[$id]['value'] = $product->productId;
          $row[$id]['formattedValue'] = $product->productId;
          break;

        case 'offerName':
          $row[$id]['value'] = $product->name;
          $row[$id]['formattedValue'] = $product->name;
          break;

        case 'description':
          $row[$id]['value'] = $product->description;
          $row[$id]['formattedValue'] = empty((array) $product->description) ? "" : $product->description;
          $row[$id]['allowsPayAutopacket'] = $this->allowsPayAutopaket($product);
          break;

        case 'tags':
          $product->tags = !isset($product->tags) ? [] : $product->tags;
          $row[$id]['value'] = array_map("strtolower", $product->tags);
          $orden = intval($configs['order']['label']);
          $img_b_d = "";
          if (!empty($product->tags)) {
            $img_b_d = $this->utils->orderedListImages($product->tags, FALSE, $orden);
          }
          if ($img_b_d != "") {
            $row[$id]['imageName'] = $img_b_d;
          }
          break;

        case 'validity':
          $row[$id]['value'] = [
            'validity' => isset($product->validityNumber) ? $product->validityNumber : '',
            'validityUnit' => isset($product->validityType) ? $product->validityType : '',
          ];
          $row[$id]['formattedValue'] = $product->validity;
          break;

        case 'price':
          $price = $product->price;
          $row[$id]['value'] = [
            (object) [
              'amount' => $price,
              'currencyId' => $this->getDefaultCurrency($product->category),
            ],
          ];
          if ($price === 0) {
            $row[$id]['formattedValue'] = $this->configBlock['messages']['offerFree'];
          }
          else {
            $row[$id]['formattedValue'] = $this->utils->formatCurrency($price, TRUE);
          }
          break;
      }
    }

    return $row;
  }


  /**
   * {@inheritdoc}
   */
  protected function getProductLoanMapped(array $configs, $product) {
    $row = [];
    foreach ($configs as $id => $field) {
      $row[$id] = [
        'label' => $field['label'],
        'show' => $this->utils->formatBoolean($field['show']),
      ];

      $offer_id = isset($product->productID) ? 'tp,'. $product->productID: '';
      switch ($id) {

        case 'offerId':
          $row[$id]['value'] = $offer_id;
          $row[$id]['formattedValue'] = $offer_id;
          break;

        case 'offerName':
          $row[$id]['value'] = $product->productName;
          $row[$id]['formattedValue'] = $product->productName;
          break;

        case 'description':
          $row[$id]['value'] = $product->productDescription;
          $row[$id]['formattedValue'] = empty((array) $product->productDescription) ? "" : $product->productDescription;
          $row[$id]['allowsPayAutopacket'] = $this->allowsPayAutopaket($product);
          break;

        case 'tags':
          $product->tags = !isset($product->productType) ? [] : $product->productType;
          $row[$id]['value'] = is_array($product->productType) ? array_map("strtolower", $product->productType) : (array) strtolower($product->productType);
          $orden = intval($configs['order']['label']);
          $img_b_d = "";
          if (!empty($product->productType)) {
            $img_b_d = $this->utils->orderedListImages((array) $product->productType, FALSE, $orden);
          }
          if ($img_b_d != "") {
            $row[$id]['imageName'] = $img_b_d;
          }
          break;

        case 'validity':
          $row[$id]['value'] = [
            'validity' => isset($product->validityNumber) ? $product->validityNumber : '',
            'validityUnit' => isset($product->validityType) ? $product->validityType : '',
          ];
          $row[$id]['formattedValue'] = $product->validityType;
          break;

        case 'price':
          $price = $product->price;
          $row[$id]['value'] = [
            (object) [
              'amount' => $price,
              'currencyId' => $this->getDefaultCurrency($product->productCategory),
            ],
          ];
          if ($price === 0) {
            $row[$id]['formattedValue'] = $this->configBlock['messages']['offerFree'];
          }
          else {
            $row[$id]['formattedValue'] = $this->utils->formatCurrency($price, TRUE);
          }
          break;
      }
    }

    return $row;
  }

  public function getLendingScoring($msisdn) {
    try {
      return $this->manager
        ->load('oneapp_mobile_v2_0_balance_loan_offers_endpoint')
        ->setHeaders([])
        ->setQuery([])
        ->setParams(['msisdn' => $msisdn])
        ->sendRequest();
    }
    catch (HttpException $exception) {
      return null;
    }
  }


  /**
   * {@inheritdoc}
   */
  protected function getAvailableOffers($msisdn) {
    try {
      $products = $this->manager
        ->load('oneapp_mobile_upselling_v2_0_available_offers_endpoint')
        ->setHeaders([])
        ->setQuery(['channelID' => 3])
        ->setParams(['msisdn' => $msisdn])
        ->sendRequest()
        ->products;
      return $this->orderProductsByPrice($products);
    }
    catch (HttpException $exception) {
      $messages = $this->configBlock['messages'];
      $title = !empty($this->configBlock['label']) ? $this->configBlock['label'] . ': ' : '';
      $message = ($exception->getCode() == '404') ? $title . $messages['empty'] : $title . $messages['error'];

      $reflected_object = new \ReflectionClass(get_class($exception));
      $property = $reflected_object->getProperty('message');
      $property->setAccessible(TRUE);
      $property->setValue($exception, $message);
      $property->setAccessible(FALSE);

      throw $exception;
    }
  }

  /**
   * Find Nbo products and delete from the array of products.
   *
   * @param array $products
   *   Array of products.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function findNboProducts(array &$products) {
    foreach ($products as $index => $product) {
      if (strpos(strtolower($product->productId), 'nbo-') === 0) {
        array_push($this->nboProducts, $product);
        unset($products[(int) $index]);
      }
    }
  }

  /**
   * Find Nbo products and delete from the array of products.
   *
   * @param array $products
   *   Array of products.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function findNboProductsNewOffer($products) {
    if (isset($products)) {
      foreach ($products->packages->products as $index => $product) {
        if (isset($product->offer_priority) && isset($product->recommendation_tags)) {
          array_push($this->nboProducts, $product);
        }
      }
    }

  }

  /**
   * Get Nbo Products By Category.
   *
   * @param string $label
   *   Label of category.
   *
   * @return mixed
   *   Return Nbo Products.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getNboProductsByCategory($key) {
    $nbo_products = [];
    foreach ($this->nboProducts as $product) {
      if ($this->getFormattedCategory($product->category) === $this->getFormattedCategory($key)) {
        $remove_offers = (!empty($this->configBlock['config']['removeOffers'])) ?
          $this->configBlock['config']['removeOffers'] : '';

        $exclude = explode(",", $remove_offers);

        if (!in_array($product->productId, $exclude)) {
          $product = $this->getProductMapped($this->configBlock['offersList']['fields'], $product);
          array_push($nbo_products, $product);
        }

      }
    }
    return $nbo_products;
  }

  /**
   * Get Nbo Category.
   *
   * @param array $categories
   *   List of categories.
   *
   * @return mixed
   *   Return category.
   */
  protected function getNboSibling(array $categories) {
    $sibling_categories = [];
    foreach ($categories as $category) {
      if ($category['key'] !== 'nbo') {
        $sibling_categories[] = $category;
      }
    }
    return $sibling_categories;
  }

  /**
   * {@inheritDoc}
   */
  protected function getBillingTypeByMsisdn($msisdn) {
    $info = \Drupal::service('oneapp.mobile.utils')->getInfoTokenByMsisdn($msisdn);
    $this->primaryNumber['info'] = isset($info['subscriptionType']) ?
      $this->translatePlantype($info['subscriptionType']) : $this->translatePlantype($info['billingType']);
    return $this->primaryNumber['info'];
  }

  /**
   * Get Customer Info by Msisdn.
   *
   * @param string $msisdn
   *   Msisdn.
   *
   * @return mixed
   *   Return data structure.
   *
   * @throws \ReflectionException
   */
  protected function getCustomerInfoByMsisdn($msisdn) {
    try {
      return $this->manager
        ->load('oneapp_mobile_upselling_v2_0_customer_info_endpoint')
        ->setHeaders([])
        ->setQuery(['businessUnit' => 'MOBILE'])
        ->setParams(['msisdn' => $msisdn])
        ->sendRequest();
    }
    catch (HttpException $exception) {
      $messages = $this->configBlock['messages'];
      $title = !empty($this->configBlock['label']) ? $this->configBlock['label'] . ': ' : '';
      $message = ($exception->getCode() == '404') ? $title . $messages['empty'] : $title . $messages['error'];

      $reflected_object = new \ReflectionClass(get_class($exception));
      $property = $reflected_object->getProperty('message');
      $property->setAccessible(TRUE);
      $property->setValue($exception, $message);
      $property->setAccessible(FALSE);

      throw $exception;
    }
  }

  /**
   * Get Show or not if is account is b2b.
   *
   * @param array $section
   *   Section configurations.
   * @param mixed $info
   *   Customer Info.
   *
   * @return bool
   *   Return if show or not.
   */
  protected function showIfIsB2b(array $section, $info) {
    if (isset($info->customer->partyOwner->partyType) && $info->customer->partyOwner->partyType == 'business') {
      return ((bool) $section['showIfB2b']) ? TRUE : FALSE;
    }
    return TRUE;
  }

  /**
   * Get weight for NBO category.
   *
   * @return mixed
   *   Return weight nbo.
   */
  protected function getWeigthNboCategory() {
    foreach ($this->categories as $category) {
      if (strtolower($category['key']) === 'nbo') {
        return $category['weight'];
      }
    }
  }

  /**
   * Order products by price.
   *
   * @param array $products
   *   Msisdn.
   *
   * @return string
   *   Return productsOrder
   */
  public function orderProductsByPrice($products) {
    if (isset($this->configBlock["config"]["products"]["order"]) && !empty($this->configBlock["config"]["products"]["order"])) {
      foreach ($products as $key => $product) {
        $aux[$key] = isset($product->price) ? $product->price : 0;
      }
      if ($this->configBlock["config"]["products"]["order"] == "SORT_ASC") {
        array_multisort($aux, SORT_ASC, $products);
      }
      else {
        array_multisort($aux, SORT_DESC, $products);
      }
    }
    return $products;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormattedCategory($category) {
    $category = trim($category);
    $category = strtolower($category);
    return strtr($category, " ", "_");
  }

  /**
   * {@inheritdoc}
   */
  protected function getAllCategoriesFormatted(array $ids) {
    $categories = \Drupal::entityTypeManager()->getStorage('mobile_offers_category_entity')
      ->loadByProperties(['uuid' => $ids]);
    $rows = [];
    $count = 0;

    foreach ($categories as $section) {
      $row = [];
      $row['id'] = $section->id();
      $row['label'] = $section->label();
      $row['weight'] = $section->getWeight();
      $row['defaultCurrency'] = $section->getDefaultCurrency();
      $row['show'] = $section->getShow();
      $row['nbo'] = $section->getNbo();
      $row['expand'] = $section->getExpanded();
      $row['subcategories'] = $section->getSubCategories();
      $row['key'] = $section->getKey();
      $rows[$count] = $row;
      $count++;
      array_push($this->categories, $row);
    }
    // Order category array by weight.
    $this->orderByWeight($rows);
    return $rows;
  }

  /**
   * Realize validations for match or exclude transformations of products
   */
  public function matchToAutoPackets(&$response, $msisdn) {
    $is_type_client_allowed = $this->isTypeClientAllowed($this->primaryNumber['info']);
    if ($this->isEnabledAutoPackets() && $is_type_client_allowed) {
      $this->autopacksService = \Drupal::service('oneapp_mobile_payment_gateway_autopackets.v2_0.autopackets_services');
      if ($this->autopacksService->isActiveAutoPackets()) {
        $config_for_autopackets = $this->autopacksService->getConfigForAutoPackets();
        if ($config_for_autopackets['order'] == 'all') {
          $this->applyAllAutoPackets($response, $config_for_autopackets);
        }
        if ($config_for_autopackets['order'] == 'match') {
          $this->applyMatchAutoPackets($response, $config_for_autopackets);
        }
        if ($config_for_autopackets['order'] == 'exclude') {
          $this->applyExcludeAutoPackets($response, $config_for_autopackets);
        }
      }
    }
  }

  /**
   * Realize set class atribute in offerName object for products
   */
  public function applyMatchAutoPackets(&$products, $config_auto_packets) {
    $ids_for_autopackets = $config_auto_packets['ids'];
    $class_for_auto_packts = $config_auto_packets['class'];
    $min_amount = floatval($config_auto_packets['min_amount']);
    foreach ($products as $idSect => $section) {
      if (isset($section['categories'])) {
        foreach ($section['categories'] as $idCateg => $category) {
          if (!empty($category['products'])) {
            foreach ($category['products'] as $idProd => $product) {
              $product_amount = floatval($product['price']['value'][0]->amount);
              $is_allowed_validity = $this->autopacksService->isAllowedValityAutopacks($product['validity']['value']['validity'],
                $product['validity']['value']['validityUnit']);
              if (in_array($product['offerId']['value'], $ids_for_autopackets) &&
                $product_amount >= $min_amount && $product['description']['allowsPayAutopacket'] && $is_allowed_validity) {
                $products[$idSect]['categories'][$idCateg]['products'][$idProd]['offerName']['class'] = $class_for_auto_packts;
              }
              unset($products[$idSect]['categories'][$idCateg]['products'][$idProd]['description']['allowsPayAutopacket']);
            }
          }
        }
      }
    }
  }

  /**
   * Realize set class atribute in offerName object for products
   */
  public function applyExcludeAutoPackets(&$products, $config_auto_packets) {
    $ids_for_autopackets = $config_auto_packets['ids'];
    $class_for_auto_packts = $config_auto_packets['class'];
    $min_amount = floatval($config_auto_packets['min_amount']);
    foreach ($products as $idSect => $section) {
      if (isset($section['categories'])) {
        foreach ($section['categories'] as $idCateg => $category) {
          if (!empty($category['products'])) {
            foreach ($category['products'] as $idProd => $product) {
              $product_amount = floatval($product['price']['value'][0]->amount);
              $is_allowed_validity = $this->autopacksService->isAllowedValityAutopacks($product['validity']['value']['validity'],
                $product['validity']['value']['validityUnit']);
              if (!in_array($product['offerId']['value'], $ids_for_autopackets) &&
                $product_amount >= $min_amount && $product['description']['allowsPayAutopacket'] && $is_allowed_validity) {
                $products[$idSect]['categories'][$idCateg]['products'][$idProd]['offerName']['class'] = $class_for_auto_packts;
              }
              unset($products[$idSect]['categories'][$idCateg]['products'][$idProd]['description']['allowsPayAutopacket']);
            }
          }
        }
      }
    }
  }

  /**
   * Realize set class atribute in offerName object for products
   */
  public function applyAllAutoPackets(&$products, $config_auto_packets) {
    $class_for_auto_packts = $config_auto_packets['class'];
    $min_amount = floatval($config_auto_packets['min_amount']);
    foreach ($products as $idSect => $section) {
      if (isset($section['categories'])) {
        foreach ($section['categories'] as $idCateg => $category) {
          if (!empty($category['products'])) {
            foreach ($category['products'] as $idProd => $product) {
              $product_amount = floatval($product['price']['value'][0]->amount);
              $is_allowed_validity = $this->autopacksService->isAllowedValityAutopacks($product['validity']['value']['validity'],
                $product['validity']['value']['validityUnit']);
              if ($product_amount >= $min_amount && $product['description']['allowsPayAutopacket'] && $is_allowed_validity) {
                $products[$idSect]['categories'][$idCateg]['products'][$idProd]['offerName']['class'] = $class_for_auto_packts;
              }
              unset($products[$idSect]['categories'][$idCateg]['products'][$idProd]['description']['allowsPayAutopacket']);
            }
          }
        }
      }
    }
  }

  /**
   * Determinate if module autopackets is active
   */
  public function isEnabledAutoPackets() {
    $config = \Drupal::config('oneapp_mobile.config');
    $config_auto_packets = $config->get('autopackets');
    return (bool) (isset($config_auto_packets) && $config_auto_packets['activate_autopackets'] == 1);
  }

  /**
   * GetPlanType form AccountInfo.
   *
   * @param mixed $account_info
   *   Account Info object from Client.
   *
   * @return string
   *   planType.
   */
  protected function isTypeClientAllowed($account_info) {
    $array = [];
    $config_autopack = \Drupal::config("oneapp_mobile.config")->get("autopackets");
    $config_orderdetails = \Drupal::config("oneapp.payment_gateway.mobile_autopackets.config")->get("orderDetails");
    if ($config_autopack != NULL && $config_orderdetails != NULL) {
      switch ($account_info) {
        case 'prepaid':
          if ($config_autopack['autopackets_plan_types']['prepaid']) {
            $array = ['prepaid'];
          }
          break;

        case 'hybrid':
          if ($config_autopack['autopackets_plan_types']['hybrid']) {
            $array = ['hybrid'];
          }
          break;

        case 'postpaid':
          if ($config_autopack['autopackets_plan_types']['postpaid']) {
            $array = ['postpaid'];
          }
          break;

        default:
          $array = [];
          break;
      }
      $is_allowed = in_array($account_info, $array);
      if ($is_allowed && is_array($config_orderdetails['paymentMethods']['fields'])) {
        foreach ($config_orderdetails['paymentMethods']['fields'] as $methods) {
          if ($methods['show'] == 1 && $methods["show_" . $this->primaryNumber['info']] == 1) {
            return $is_allowed;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Determinate if one offer can by paid with creditcard.
   */
  public function allowsPayAutopaket($offer) {
    if (isset($offer->acquisitionMethods)) {
      foreach ($offer->acquisitionMethods as $acquisition_method) {
        if (is_array($acquisition_method->acquisitionTypeId)) {
          foreach ($acquisition_method->acquisitionTypeId as $type_id) {
            if ($type_id == 4) {
              return TRUE;
            }
          }
        }
        elseif ($acquisition_method->acquisitionTypeId == 4) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * inheritDoc.
   */
  protected function translatePlantype($plan_info) {
    $plans_search = ['HIB', 'POS', 'PRE'];
    $plans_replace = ['hybrid', 'postpaid', 'prepaid'];
    return str_replace($plans_search, $plans_replace, $plan_info);
  }

}
