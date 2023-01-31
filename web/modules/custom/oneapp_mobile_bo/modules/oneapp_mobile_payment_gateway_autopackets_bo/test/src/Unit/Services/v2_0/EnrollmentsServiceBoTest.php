<?php

namespace Drupal\Tests\oneapp_mobile_payment_gateway_autopackets_bo\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\oneapp_mobile_payment_gateway_autopackets_bo\Services\EnrollmentsServiceBo;

class EnrollmentsServiceBoTest extends UnitTestCase {

  protected $manager;
  protected $awsManager;
  protected $tokenAuthorization = [];
  protected $utils;
  protected $utilsPayment;
  protected $container;
  protected $enrollmentsServiceBo;

  //setUp : donde creamos el o los objetos contra el que probaremos
  protected function setUp() {
    $this->manager = $this->createMock('Drupal\oneapp_endpoints\Services\Manager');
    $this->awsManager = $this->createMock('Drupal\aws_service\Services\v2_0\AwsApiManager');
    $this->tokenAuthorization = [];
    $this->utils = $this->createMock('Drupal\oneapp\Services\UtilsService');
    $this->utilsPayment = $this->createMock('Drupal\oneapp_convergent_payment_gateway\Services\v2_0\UtilsService');
    $this->container;

    $this->enrollmentsServiceBo;
    $enrollments_service_bo = $this->enrollmentsServiceBo =
      $this->createMock('Drupal\oneapp_mobile_payment_gateway_autopackets_bo\Services\EnrollmentsServiceBo');

    $this->enrollmentsServiceBo->expects($this->any())
      ->method('getOfferWithFormat')
      ->will($this->returnValue(
        $this->getMockFormattedOffer()
      ));
    $this->enrollmentsServiceBo->expects($this->any())
      ->method('getSubscriptionDataOfAdditionalData')
      ->will($this->returnValue(
        $this->getSubscriptionData()
      ));

    ///Definir servicios en el container
    $container = new ContainerBuilder();
    $container->set('oneapp_mobile_payment_gateway_autopackets.enrollments_service', $enrollments_service_bo);
    \Drupal::setContainer($container);
    $this->container = $container;

  }

  /**
   * @test
   * Verify that the data does return an array.
   *
   * @covers Drupal\oneapp_mobile_payment_gateway_autopackets_bo\Services\EnrollmentsServiceBo::getSubscriptionDataOfAdditionalData
   */
  public function test_GetSubscriptionDataOfAdditionalData() {
    $result = $this->enrollmentsServiceBo->getSubscriptionDataOfAdditionalData([], 77366839);
    $this->assertIsArray($result);
  }

  /**
   * @test
   * Verify that the data does return an array.
   *
   * @covers Drupal\oneapp_mobile_payment_gateway_autopackets_bo\Services\EnrollmentsServiceBo::getOfferWithFormat
   */
  public function test_GetOfferFormatted() {
    $result = $this->enrollmentsServiceBo->getOfferWithFormat(77366839, 230);
    $this->assertIsArray($result);
    $this->assertEquals(8, count($result));
  }

  /**
   * @test
   * Verify that the data does return a number.
   */
  public function test_return_suscription_duration_in_hours() {
    $enrollment_bo = new EnrollmentsServiceBo($this->manager, $this->awsManager,
      $this->tokenAuthorization, $this->utilsPayment, $this->utils);
    $offer = $this->getMockForTransactions();
    $hours = $enrollment_bo->getSuscriptionDurationInHours($offer);
    $this->assertIsNumeric($hours);
  }

  // Mock for méthod getFrecuencyFormatted.
  public function getSubscriptionData() {
    $addtional_data = $this->getMockForTransactions();
    $id = 77366839;
    $hours = 24;
    $params['subscription'] = [
      'name' => $addtional_data->name,
      'amount' => (string) $addtional_data->cost,
      'duration' => $hours,
      'productReference' => (string) $addtional_data->offerId,
      'lastOrderTimeStamp' => Date("Y-m-d\TH:i:s\Z"),
    ];
    return $params;
  }

  // Mock for offers.
  public function getMockForTransactions() {
    $transaction = "
    {\"offerId\":230,\"type\":\"PAQUETIGO\",\"cost\":2,\"currency\":\"BS\",\"name\":\"55MB\",\"description\":\"Paquete diario de 55MBxBs2x24Horas. Los Megabytes no utilizados seran acumulados 2 meses.\",\"category\":\"INTERNET\",\"validity\":\"24 HORAS \",\"validityNumber\":\"24\",\"validityType\":\"horas \",\"additionalData\":{\"acquisitionMethods\":[{\"id\":4,\"paymentMethodName\":\"TARJETA DE CREDITO\"},{\"id\":1,\"paymentMethodName\":\"CREDITO TIGO\"},{\"id\":3,\"paymentMethodName\":\"PAYMENT TM\"}]},\"productReference\":\"230\",\"paymentTokenId\":17771}
    ";
    return json_decode($transaction);
  }

  // Mock for method formated offers.
  public function getMockFormattedOffer() {
    $offer = $this->getMockForTransactions();
    $validity['value'] = isset($offer->validityNumber) ? $offer->validityNumber : $offer->durationTime;
    $validity['formattedValue'] = isset($offer->validityNumber) ? $offer->validityNumber . ' ' . $offer->validityType :  '';
    $data = [
      'offerId' => isset($offer->packageId) ? $offer->packageId: '',
      'offerName' => isset($offer->name) ? $offer->name : '',
      'description' => isset($offer->description) ? $offer->description : '',
      'categoryName' => $offer->category,
      'validity' => $validity['formattedValue'],
      'amount' => $offer->cost . ' USD',
      'nextPayment' => "cada 24 horas",
      'frequency' => $validity['formattedValue'],
    ];
    return $data;
  }

}
