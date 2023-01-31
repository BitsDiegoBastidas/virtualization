<?php

namespace Drupal\oneapp_mobile_upselling_bo\Services\v2_0;

use Drupal\oneapp\Exception\HttpException;
use Drupal\oneapp_mobile_upselling\Services\v2_0\RechargeLendingAmountsRestLogic;
use Drupal\oneapp\ApiResponse\ErrorBase;
use Drupal\oneapp\Exception\BadRequestHttpException;

/**
 * Class RechargeLendingAmountsRestLogicBo.
 */
class RechargeLendingAmountsRestLogicBo extends RechargeLendingAmountsRestLogic {

  /**
   * Get loan amounts.
   *
   * @param string $msisdn
   *   Msisdn.
   *
   * @return array
   *   Return loan amounts list.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function get($msisdn) {
    $query = \Drupal::request()->query;
    // Get query object and read loan types configurations.
    $lower_params = \Drupal::service('oneapp.mobile.utils')->formatQueryParams($query);
    $loan_type_configs = \Drupal::config('oneapp_mobile.config')->get('loan_types');
    $query_name = strtolower($loan_type_configs['loanTypeQuery']['queryKey']);
    $loan_type = isset($lower_params[$query_name]) ? $lower_params[$query_name] : '';

    // Get loan products.
    $loan_offers = $this->getLendingScoring($msisdn);

    // Emergency case.
    if ($loan_type == $loan_type_configs['emergencyLoan']['queryParamValue']) {
      $label = $loan_type_configs['emergencyLoan']['label'];
      $id = $lower_params['packageid'];
      $packages = $this->get_packets_loan_products($loan_offers, $label, $id);
      $response = $this->get_response_formatted($msisdn, $packages[0], $packages[1]);
    }
    elseif (empty($loan_offers) && !isset($loan_offers['error'])) {
      $response['data'] = $this->getEmptyState();
    }
    // By default is available balance loans.
    else {
      $target = strtolower(str_replace(' ', '', $loan_type_configs['balanceLoan']['label']));
      $packages = array_filter($loan_offers, function ($el) use ($target) {
        $source = strtolower(str_replace(' ', '', $el->productCategory));
        return strpos($source, $target) !== FALSE;
      });
      $response = $this->get_response_formatted($msisdn, $packages);
    }

    return $response;
  }

  /**
   * Get response formated for available loans.
   *
   * @param string $msisdn
   *   Msisdn.
   * @param array $scoring
   *   Array of loans.
   * @param bool $matched
   *   Matched or not packageId with loanId.
   *
   * @return array
   *   Return array of loans.
   */
  protected function get_response_formatted($msisdn, array $scoring, $matched = FALSE) {
    $config = $this->configBlock;
    $generic_error = [
      'message' => [
        'label' => $this->configBlock['message']['empty']['label'],
        'show' => (bool) $this->configBlock['message']['empty']['show'],
      ],
    ];
    $actions = $this->configResult($this->configBlock['actions']);
    $node = NULL;
    if ($scoring && !isset($scoring['error'])) {
      foreach ($scoring as $value) {
        $node['offerId'] = [
          'label' => $config['fields']['offerId']['formattedValue'],
          'show' => (bool) $config['fields']['offerId']['show'],
          'value' => $value->productID,
          'formattedValue' => $value->productID,
        ];

        $node['offerName'] = [
          'label' => $config['fields']['offerName']['formattedValue'],
          'show' => (bool) $config['fields']['offerName']['show'],
          'value' => $value->productName,
          'formattedValue' => $value->productName,
        ];
        $formatted_price = $this->utils->formatCurrency($value->price, TRUE);
        $node['description'] = [
          'label' => '',
          'show' => (bool) $config['fields']['description']['show'],
          'value' => $value->productDescription,
          'formattedValue' => $value->productDescription,
        ];

        $node['tags'] = [
          'label' => $config['fields']['tags']['formattedValue'],
          'show' => (bool) $config['fields']['tags']['show'],
          'value' => [],
          'imageName' => [],
        ];

        $validity_number = $config['fields']['validity']['formattedValue'] . ' ' . $value->validityNumber;
        if ((int) $value->validityNumber === 1) {
          $validity_number = 'Hoy';
        }
        elseif ((int) $value->validityNumber === 2) {
          $validity_number = 'Mañana';
        }
        $node['validity'] = [
          'label' => $config['fields']['validity']['formattedValue'],
          'show' => (bool) $config['fields']['validity']['show'],
          'value' => [
            'validity' => $value->validityNumber,
            'validityUnit' => $value->validityType,
          ],
          'formattedValue' => $validity_number . ' ' . $value->validityType,
        ];

        $node['price'] = [
          'label' => $config['fields']['price']['formattedValue'],
          'show' => (bool) $config['fields']['price']['show'],
          'value' => [
            'amount' => (double) $value->price,
            'currencyId' => $this->utils->getCurrencyCode(),
          ],
          'formattedValue' => $formatted_price,
        ];

        $formatted_fee = $this->utils->formatCurrency($value->lendingFee, TRUE);
        $node['fee'] = [
          'label' => $config['fields']['fee']['formattedValue'],
          'show' => (bool) $config['fields']['fee']['show'],
          'value' => [
            'amount' => (double) $value->lendingFee,
            'currencyId' => $this->utils->getCurrencyCode(),
          ],
          'formattedValue' => $formatted_fee,
        ];

        $data['products'][] = $node;
      }

      $data['skipOffer'] = ['value' => $matched ? TRUE : FALSE];
      $data['confirmation'] = $this->getConfirmationSchema($config['confirmation'], $msisdn);
    }
    elseif (empty($scoring) && !isset($scoring['error'])) {
      $data = $this->getEmptyState();
    }
    else {
      $this->sendException($generic_error['message']['label'], 404, NULL);
    }

    return [
      'data' => $data,
      'config' => $actions,
    ];
  }

  public function getEmptyState() {
    return [
      'noData' => [
        'value' => 'empty'
      ],
    ];
  }

  /**
   * Get id loan matched with loand product.
   *
   * @param string $id_offer
   *   Offer id.
   *
   * @return int|bool
   *   Return id package or false.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function get_loan_id_match($id_offer) {
    if (substr($id_offer, 0, 2) === 'tp') {
      $id_offer = explode(',', $id_offer)[1];
      return $id_offer;
    }
    else {
      $ids = \Drupal::entityQuery('paquetigos_entity')->execute();
      $loans_ids = \Drupal::entityTypeManager()->getStorage('paquetigos_entity')->loadMultiple($ids);
      foreach ($loans_ids as $loan) {
        $id_packet = $loan->getIdOffer();
        $system_offer_id = $loan->getSystemOfferId();
        if ($id_packet == $id_offer || $system_offer_id == $id_offer) {
          return $loan->getIdLoan();
        }
      }
      return FALSE;
    }

  }

  /**
   * Get emergency loan products.
   *
   * @param array $loan_offers
   *   Loan offers list.
   * @param string $label
   *   String by filter.
   *
   * @return array
   *   Return array of loan offers.
   */
  protected function get_emergency_loan_products(array $loan_offers, $label) {
    $target = strtolower(str_replace(' ', '', $label));
    $arr_target = explode(',', $target);
    return array_filter($loan_offers, function ($el) use ($arr_target) {
      $source = strtolower(str_replace(' ', '', $el->productType));
      foreach ($arr_target as $item) {
        if (strpos($source, $item) !== FALSE) {
          return TRUE;
        }
      }
      return FALSE;
    });
  }

  /**
   * Get loan packects or emergency loan if id loan does match.
   *
   * @param array $loan_offers
   *   List of offers.
   * @param string $label
   *   Label.
   * @param string $offerId
   *   Offer id.
   *
   * @return array
   *   Array formatted.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function get_packets_loan_products(array $loan_offers, $label, $offer_id) {
    $id = $this->get_loan_id_match($offer_id);

    // If find product id. Then return product detail.
    if ($id) {
      // Get package details of the loan list.
      foreach ($loan_offers as $loan) {
        if (strpos($loan->productID, (string) $id) !== FALSE) {
          return [[$loan], TRUE];
        }
      }
    }
    // Else not find product matched, response with loans emergency products.
    $packages = $this->get_emergency_loan_products($loan_offers, $label);
    if (!$packages) {
      $packages = [
        'error' => [
          'message' => [
            'label' => $this->configBlock['message']['error']['label'],
            'show' => (bool) $this->configBlock['message']['error']['show'],
          ]
        ],
      ];
    }
    return [$packages, FALSE];
  }

  /**
   * {@inheritdoc}
   */
  protected function getLendingScoring($msisdn) {
    try {
      $products = $this->rechargeLendingAmountServices->getLendingScoring($msisdn);
      return $this->orderLoanOffersByPrice($products);
    }
    catch (HttpException $e) {
      $messages = $this->configBlock['message'];
      $message = ($e->getCode() == '404') ? $messages['empty']['label'] : $messages['error']['label'];
      $reflected_object = new \ReflectionClass(get_class($e));
      $property = $reflected_object->getProperty('message');
      $property->setAccessible(TRUE);
      $property->setValue($e, $message);
      $property->setAccessible(FALSE);
      throw $e;
    }
  }

  /**
   * Envía exception.
   */
  public function sendException($msg, $code, $exception = NULL) {
    if (is_null($exception)) {
      $exception = new \Exception($msg, $code);
    }
    $error = new ErrorBase();
    $error->getError()->set('message', $msg);
    throw new BadRequestHttpException($error, $exception, $exception->getCode());
  }
}
