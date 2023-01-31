<?php

namespace Drupal\oneapp_mobile_plans_bo\Services\v2_0;

use Drupal\oneapp_mobile_plans\Services\v2_0\CurrentRestLogic;

/**
 * Class CurrentRestLogicBo.
 */
class CurrentRestLogicBo extends CurrentRestLogic {

  /**
   * Responds to GET requests.
   *
   * @param string $msisdn
   *   Msisdn.
   *
   * @return array
   *   The HTTP response object.
   */
  public function get($msisdn) {
    $data = [];
    $rows = [];
    $config = $this->configBlock['config'];
    $currentPlan = $this->getCurrentPlan($msisdn);
    $accountService = \Drupal::service('oneapp.mobile.accounts');
    $planInfo = $accountService->getPlanInfo($msisdn);
    $showAdditional = TRUE;

    foreach ($this->configBlock['fields'] as $id => $field) {
      $showAdditional = $planInfo->accountState != "MO" ?
        ($currentPlan->additionalRecurrentOfferingList[0]->additionalOfferingId != "null" ? TRUE : FALSE) : FALSE;
      $data[$id] = [
        'label' => $field['label'],
        'show' => ($field['show']) ? TRUE : FALSE,
      ];

      switch ($id) {
        case 'planName':
          $data[$id]['value'] = $currentPlan->planName;
          $data[$id]['formattedValue'] = $currentPlan->planName;
          break;

        case 'accountState':
          $data[$id]['value'] = $planInfo->accountState;
          $data[$id]['formattedValue'] = $accountService->getAccountState($planInfo);
          break;

        case 'billingCycle':
          $data[$id]['value'] = "";
          $data[$id]['formattedValue'] = "";
          break;

        case 'endDate':
          $data[$id]['value'] = "";
          $data[$id]['formattedValue'] = "";
          break;

        case 'monthlyAmount':
          $totalMonthlyAmount = $currentPlan->monthlyAmount + $currentPlan->additionalMonthlyAmount;
          $data[$id]['value'] = $totalMonthlyAmount;
          $data[$id]['formattedValue'] = $this->formatCurrency($currentPlan->currencyId, $totalMonthlyAmount);
          $data[$id]['description'] = $field['description'];
          break;

        case 'basicMonthlyAmount':
          $data[$id]['show'] = !empty($field['show']) && $showAdditional ? TRUE : FALSE;
          $data[$id]['value'] = $currentPlan->monthlyAmount;
          $data[$id]['formattedValue'] = $this->formatCurrency($currentPlan->currencyId, $currentPlan->monthlyAmount);
          $data[$id]['description'] = $field['description'];
          break;

        case 'additionalMonthlyAmount':
          $data[$id]['show'] = !empty($field['show']) && $showAdditional ? TRUE : FALSE;
          $data[$id]['value'] = $currentPlan->additionalMonthlyAmount;
          $data[$id]['formattedValue'] = $this->formatCurrency($currentPlan->currencyId, $currentPlan->additionalMonthlyAmount);
          $data[$id]['description'] = $field['description'];
          break;

        case 'productOfferingList':
          foreach ($currentPlan->productOfferingList as $productList) {
            foreach ($productList->offeringDetailList as $bucket) {
              $category = strtolower($productList->offeringCategory);
              if (!empty($bucket->value)) {
                $row = [
                  'label' => $bucket->name,
                  'show' => isset($config['offeringCategory'][$category]) ?
                    !empty($config['offeringCategory'][$category]) : !empty($field['show']),
                ];
                $row['value'] = $bucket->value;
                $row['formattedValue'] = (string) (($bucket->value == 0 && is_numeric($bucket->value)) ? $config['messages']['free'] :
                  $this->formatData($productList->offeringCategory, $bucket->value));
                $rows[] = $row;
              }
            }
          }

          $data[$id] = $rows;
          break;

        case 'additionalRecurrentOfferingList':
          if (!empty($currentPlan->additionalRecurrentOfferingList) && $showAdditional) {
            foreach ($currentPlan->additionalRecurrentOfferingList as $additionalDetail) {
              foreach ($this->configBlock["additional"] as $key => $additionalField) {
                switch ($key) {
                  case 'additionalOfferingId':
                    $additional[$key] = [
                      'label' => $additionalField["label"],
                      'show' => ($additionalField["show"]) ? TRUE : FALSE,
                      'value' => !empty($additionalDetail->additionalOfferingId) ? $additionalDetail->additionalOfferingId : '',
                      'formattedValue' => !empty($additionalDetail->additionalOfferingId) ? (string) $additionalDetail->additionalOfferingId : '',
                    ];
                    break;

                  case 'additionalOfferingName':
                    $additional[$key] = [
                      'label' => $additionalField["label"],
                      'show' => ($additionalField["show"]) ? TRUE : FALSE,
                      'value' => !empty($additionalDetail->additionalOfferingName) ? $additionalDetail->additionalOfferingName : '',
                      'formattedValue' => !empty($additionalDetail->additionalOfferingName) ? (string) $additionalDetail->additionalOfferingName : '',
                    ];
                    break;

                  case 'offeringLegacyName':
                    $additional[$key] = [
                      'label' => $additionalField["label"],
                      'show' => ($additionalField["show"]) ? TRUE : FALSE,
                      'value' => !empty($additionalDetail->offeringLegacyName) ? $additionalDetail->offeringLegacyName : '',
                      'formattedValue' => !empty($additionalDetail->offeringLegacyName) ? (string) $additionalDetail->offeringLegacyName : '',
                    ];
                    break;

                  case 'priceAmount':
                    $additional[$key] = [
                      'label' => $additionalField["label"],
                      'show' => ($additionalField["show"]) ? TRUE : FALSE,
                      'value' => isset($additionalDetail->priceAmount) ? $additionalDetail->priceAmount : '',
                      'formattedValue' => isset($additionalDetail->priceAmount) ? $this->formatCurrency($currentPlan->currencyId, $additionalDetail->priceAmount) : '',
                    ];
                    break;

                  case 'validity':
                    $additional[$key] = [
                      'label' => $additionalField["label"],
                      'show' => ($additionalField["show"]) ? TRUE : FALSE,
                      'value' => !empty($additionalDetail->validity) ? $additionalDetail->validity : '',
                      'formattedValue' => !empty($additionalDetail->validity) ? (string) $additionalDetail->validity . ' ' . $additionalDetail->validityUnit : '',
                    ];
                    break;
                }
                $imageName = explode(" ", $additionalDetail->additionalOfferingName);
                $additional['tags'] = [
                  'label' => $this->configBlock['additional']['tags']['label'],
                  'show' => $this->configBlock['additional']['tags']['show'] ? TRUE : FALSE,
                  'value' => [
                    isset($imageName[0]) ? strtolower($imageName[0]) : '',
                  ],
                  'imageName' => [
                    isset($imageName[0]) ? strtolower($imageName[0]) . '.png' : '',
                  ],
                ];
              }

              $imageName = explode(" ", $additionalDetail->additionalOfferingName);
              $additional['tags'] = [
                'label' => $this->configBlock["additional"]["tags"]["label"],
                'show' => $this->configBlock["additional"]["tags"]["show"] ? TRUE : FALSE,
                'value' => [
                  isset($imageName[0]) ? strtolower($imageName[0]) : '',
                ],
                'imageName' => [
                  isset($imageName[0]) ? strtolower($imageName[0]) . '.png' : '',
                ],
              ];
              $additionalData[] = $additional;
              $data[$id] = $additionalData;
            }
          }
          else {
            $data[$id] = [];
          }
          break;
      }
    }

    return $data;
  }

  /**
   * Implements formatData.
   *
   * @param string $offeringCategory
   *   Offering category value.
   * @param string $reservedAmount
   *   Reserved amount value.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   */
  protected function formatData($offeringCategory, $reservedAmount) {
    switch ($offeringCategory) {
      case 'Data':
        return $reservedAmount . ' MB';

      default:
        if ($reservedAmount == "Ilimitados") {
          return $this->configBlock['config']['messages']['unlimited'];
        }
        else {
          return $reservedAmount;
        }
    }
  }

}
