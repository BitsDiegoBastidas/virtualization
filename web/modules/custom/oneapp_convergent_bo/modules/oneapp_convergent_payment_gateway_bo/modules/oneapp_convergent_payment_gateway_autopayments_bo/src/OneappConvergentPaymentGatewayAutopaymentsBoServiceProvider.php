<?php

namespace Drupal\oneapp_convergent_payment_gateway_autopayments_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
/**
 * Modifies the classes of oneapp_convergent_payment_gateway_autopayments_bo.
 *
 * @package Drupal\oneapp_convergent_payment_gateway_autopayments_bo
 */
class OneappConvergentPaymentGatewayAutopaymentsBoServiceProvider extends ServiceProviderBase {
  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $details_invoices_enrollments_service = $container->getDefinition('recurring_payment_gateway.v2_0.details_invoice_enrollment_rest_logic');
    $details_invoices_enrollments_service->setClass('Drupal\oneapp_convergent_payment_gateway_autopayments_bo\Services\v2_0\DetailsInvoiceEnrollmentRestLogicBo');
    $enrollments_service = $container->getDefinition('oneapp_convergent_payment_gateway.recurring_payments.v2_0.enrollments');
    $enrollments_service->setClass('Drupal\oneapp_convergent_payment_gateway_autopayments_bo\Services\EnrollmentsServiceBo');
  }
}
