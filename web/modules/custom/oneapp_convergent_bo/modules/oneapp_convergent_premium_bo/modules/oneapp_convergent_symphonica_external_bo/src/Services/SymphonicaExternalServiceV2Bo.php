<?php

namespace Drupal\oneapp_convergent_symphonica_external_bo\Services;

use Drupal\oneapp_convergent_symphonica_external\Services\SymphonicaExternalServiceV2;

/**
 * Class SymphonicaExternalServiceV2Bo.
 */

class SymphonicaExternalServiceV2Bo extends SymphonicaExternalServiceV2 {

  const TYPE_MOBILE = 'mobile';
  const TYPE_HOME = 'home';
  const ACTION_CHANGE_SERVICE = 'update';

  /**
   * {@inheritdoc}
   */
  public function callComExternalOrders($body) {
    return $this->manager
      ->load('oneapp_convergent_symphonica_external_v2_0_com_orders_endpoint')
      ->setParams([])
      ->setHeaders(['x-crmSystem' => $this->type, 'Content-Type' => 'application/json'])
      ->setBody($body)
      ->setQuery([])
      ->sendRequest();
  }

  /**
   * {@inheritdoc}
   */
  protected function getBodyApiCom($id, $offer_id, $action) {
    $date = date('Y-m-d\TH:i:s'.substr((string)microtime(), 1, 4).'\Z');
    return [
      'input' => [
        'included' => [
          'newInstance' => [
            'attributes' => [
              'referenceNumber' => $this->externalId,
              'parentAgreementId' => NULL,
              'requestedStartDatetime' => $date,
              'createdAt' => $date,
              'salesInfo' => [
                'channel' => '10',
                'chainId' => NULL,
                'dealerId' => NULL,
                'salespersonId' => NULL,
                'batchId' => NULL,
                'salesType' => $action == self::ACTION_CHANGE_SERVICE ? 'upsell' : 'acquisition',
              ],
              'requestedCompletionDatetime' => $date,
              'priority' => '4',
              'callbackUrl' => NULL,
            ],
            'relationships' => [
              'customerAccountId' => $this->type == self::TYPE_HOME ? $id : '',
              'taskId' => ''
            ],
            'included' => [
              'orderItems' => [
                [
                  'attributes' => [
                    'id' => '1',
                    'action' => $action, // add, terminate, update
                    'requestedStartDatetime' => NULL,
                    'reason' =>[
                      'name' => '',
                      'isDefault' => null,
                      'language' => '',
                      'validFor' => [
                        'startDatetime' => $date,
                        'endDatetime' => $date,
                      ],
                      'value' => ''
                    ],
                    'requestedCompletionDatetime' => NULL,
                    'priority' => NULL,
                    'quantity' => '1',
                    'completedAt' => NULL,
                    'targetAgreementId' => NULL,
                  ],
                  'relationship' => [
                    'billingAccount' => [
                      'id' => '',
                      'accountId' => '',
                      'accountType' => NULL,
                      'billingProfile' => [
                        'id' => ''
                      ]
                    ]
                  ],
                  'included' => [
                    'orderProduct' => [
                      'attributes' => [
                        'targetProductId' => '',
                        'inputtedCharacteristics' => $this->getInputtedCharacteristics($id, $action),
                        'enhancedCharacteristics' => []
                      ],
                      'relationships' => [
                        'orderItemId' => '',
                        'discountIds' => '',
                        'productOfferingId' => $this->offerId,
                      ],
                      'included' => [
                        'realizingResources' => [
                          'attributes' => [
                            'resourceId' => $this->type == self::TYPE_MOBILE ? $id : '',
                            'resourceType' => $this->type == self::TYPE_MOBILE ? 'msisdn' : '',
                            'id' => '',
                          ]
                        ]
                      ],
                    ]
                  ]
                ]
              ]
            ]
          ]
        ]
      ]
    ];
  }

  protected function getInputtedCharacteristics($id, $action) {
    $config = \Drupal::config('oneapp_endpoints.settings')->getRawData();
    $inputted_characteristics = [
      [
        'key' => 'SUPPLIER',
        'value' => $this->ottServiceId,
      ],
      [
        'key' => 'SUBS_TYPE',
        'value' => '1.0'
      ],
      [
        'key' => 'SKU',
        'value' => $this->sku,
      ],
      [
        'key' => 'CUSTOM_INFO_BILLING_ID',
        'value' => $this->offerId,
      ],
      [
        'key' => 'CUSTOM_INFO_ORGANIZATION_CODE',
        'value' => strtoupper($config['country_iso']),
      ],
      [
        'key' => 'CUSTOM_INFO_LINE_TYPE',
        'value' => $this->type,
      ]
    ];
    $inputted_keys = array_column($inputted_characteristics, 'key');
    if ($this->type == self::TYPE_MOBILE) {
      $inputted_characteristics[] = [
        'key' => 'MSISDN',
        'value' => $id,
      ];
    }
    if ($this->type == self::TYPE_HOME) {
      $pos_line_type = array_search('CUSTOM_INFO_LINE_TYPE', $inputted_keys);
      $inputted_characteristics[$pos_line_type] = [
        'key' => 'CUSTOM_INFO_LINE_TYPE',
        'value' => 'fixed',
      ];
    }
    if ($action == self::ACTION_CHANGE_SERVICE) {
      $inputted_characteristics[] = [
        'key' => 'current_offer',
        'value' => $this->optional['oldOfferId'],
      ];
      $pos_billing_id = array_search('CUSTOM_INFO_BILLING_ID', $inputted_keys);
      $inputted_characteristics[$pos_billing_id] = [
        'key' => 'CUSTOM_INFO_BILLING_ID',
        'value' => $this->optional['oldOfferId'],
      ];
    }
    return $inputted_characteristics ?? [];
  }

}