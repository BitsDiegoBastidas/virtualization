<?php

namespace Drupal\Tests\oneapp_mobile_payment_gateway_packets_bo\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\oneapp_mobile_payment_gateway_packets_bo\Services\v2_0\PaymentGatewayPacketsRestLogicBo;

class PaymentGatewayPacketsRestLogicBoTest extends UnitTestCase {

  /**
   * Property to store service.
   *
   * @var mixed
   */
  protected $container;

  /**
   * Property to store service.
   *
   * @var mixed
   */
  protected $paymentGatewayPacketsBoService;

  //setUp : donde creamos el o los objetos contra el que probaremos
  protected function setUp() {

    $payment_gateway_packets_bo_service = $this->paymentGatewayPacketsBoService =
      $this->createMock('Drupal\oneapp_mobile_payment_gateway_packets_bo\Services\v2_0\PaymentGatewayPacketsRestLogicBo');

    $this->paymentGatewayPacketsBoService->expects($this->any())
      ->method('getFrecuencyFormatted')
      ->will($this->returnValue(
        $this->getFrecuencyByOffer()
      ));

    ///Definir servicios en el container
    $container = new ContainerBuilder();
    $container->set('oneapp_mobile_payment_gateway_packets.v2_0.payment_gateway_packets_rest_logic', $payment_gateway_packets_bo_service);
    \Drupal::setContainer($container);

  }

  /**
   * Verify that the data does return an array.
   *
   * @covers Drupal\oneapp_mobile_payment_gateway_packets_bo\Services\v2_0\PaymentGatewayPacketsRestLogicBo::getFrecuencyFormatted
   */
  public function testGetFrecuencyFormatted() {
    $offer = [];
    $result = $this->paymentGatewayPacketsBoService->getFrecuencyFormatted($offer);
    $this->assertIsArray($result);
    $this->assertArrayHasKey('value', $result);
    $this->assertArrayHasKey('formattedValue', $result);
  }

  // Mock for méthod getFrecuencyFormatted.
  public function getFrecuencyByOffer() {
    $offer = $this->getMockForOffer();
    $validity = isset($offer->validity) ? $offer->validity : $offer->validityNumber . ' ' . $offer->validityType;
    switch (str_replace(' ', '', $offer->validityType)) {
      case 'Horas':
      case 'HORAS':
      case 'horas':
        $value = $offer->validityNumber;
        if ($value / 24 >= 1) {
          $days = intval($value / 24);
          $formatted_value = ($days == 1) ? "'@day día', ['@day' => $days]" :
            "'@day días', ['@day' => $days]";
        }
        else {
          $formatted_value = ($value == 1) ? "'@hour hora', ['@hour' => $value]" : "'@hours horas', ['@hours' => $value]";
        }
        return [
          'value' => $value,
          'formattedValue' => $formatted_value,
        ];
        break;

      case 'Días':
      case 'DIAS':
      case 'Día':
      case 'día':
        $hours = $offer->validityNumber * 24;
        $formatted_value = ($offer->validityNumber == 1) ? "'@day día', ['@day' => $offer->validityNumber]" :
          "'@day días', ['@day' => $offer->validityNumber]";
        return [
          'value' => $hours,
          'formattedValue' => $formatted_value,
        ];
        break;

      case 'mes':
      case 'Mes':
        $hours = $offer->validityNumber * 30 * 24;
        $days = $offer->validityNumber * 30;
        $formatted_value = ($days == 1) ? "'@day día', ['@day' => $days]" : "'@day días', ['@day' => $days]";
        return [
          'value' => $hours,
          'formattedValue' => $formatted_value,
        ];
        break;

    }
    switch (str_replace(' ', '', $offer->validityNumber)) {
      case 'Hoy':
      case 'hoy':
        try {
          $validity = explode(') ', $offer->validityType);
          $date = new \DateTime($validity[1]);
          $now = new \DateTime();
          $dif = $date->diff($now);
          $validity = $dif->h;
          if ($validity / 24 >= 1) {
            $days = intval($validity / 24);
            $formatted_value = ($days == 1) ? "'@day día', ['@day' => $days]" :
              "'@day días', ['@day' => $days]";
          }
          else {
            $formatted_value = ($validity == 1) ? "'@hour hora', ['@hour' => $validity]" : "'@hours horas', ['@hours' => $validity]";
          }
          return [
            'value' => $validity,
            'formattedValue' => $formatted_value,
          ];
        }
        catch (\Exception $e) {
          $validity = 0;
        }
        break;

      case 'mañana':
      case 'Mañana':
        try {
          $validity = explode(') ', $offer->validityType);
          $date = new \DateTime($validity[1]);
          $now = new \DateTime();
          $dif = $date->diff($now);
          $validity = $dif->h + 24;
          if ($validity / 24 >= 1) {
            $days = intval($validity / 24);
            $formatted_value = ($days == 1) ? "'@day día', ['@day' => $days]" :
              "'@day días', ['@day' => $days]";
          }
          else {
            $formatted_value = ($validity == 1) ? "'@hour hora', ['@hour' => $validity]" : "'@hours horas', ['@hours' => $validity]";
          }
          return [
            'value' => $validity,
            'formattedValue' => $formatted_value,
          ];
        }
        catch (\Exception $e) {
          $validity = 24;
        }
        break;
    }
    return [
      'value' => $validity,
      'formattedValue' => $offer->validityNumber . ' ' . $offer->validityType,
    ];
  }

  // Mock for offers.
  public function getMockForOffer() {
    $offer = "
    {\"offerId\":230,\"type\":\"PAQUETIGO\",\"cost\":2,\"currency\":\"BS\",\"name\":\"55MB\",\"description\":\"Paquete diario de 55MBxBs2x24Horas. Los Megabytes no utilizados seran acumulados 2 meses.\",\"category\":\"INTERNET\",\"validity\":\"24 HORAS \",\"validityNumber\":\"24\",\"validityType\":\"horas \",\"additionalData\":{\"acquisitionMethods\":[{\"id\":4,\"paymentMethodName\":\"TARJETA DE CREDITO\"},{\"id\":1,\"paymentMethodName\":\"CREDITO TIGO\"},{\"id\":3,\"paymentMethodName\":\"PAYMENT TM\"}]}}
    ";
    return json_decode($offer);
  }
}
