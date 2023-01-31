<?php
namespace Drupal\oneapp_home_scheduling_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of oneapp_home_scheduling_bo.
 *
 * @package Drupal\oneapp_home_scheduling_bo
 */
class OneappHomeSchedulingBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $scheduled_visits = $container->getDefinition('oneapp_home_scheduling.v2_0.scheduled_visits_rest_logic');
    $scheduled_visits->setClass('Drupal\oneapp_home_scheduling_bo\Services\v2_0\ScheduledVisitsBoRestLogic');
    $visit_details = $container->getDefinition('oneapp_home_scheduling.v2_0.visit_details_rest_logic');
    $visit_details->setClass('Drupal\oneapp_home_scheduling_bo\Services\v2_0\VisitDetailsBoRestLogic');
    $scheduling_service = $container->getDefinition('oneapp_home_scheduling.v2_0.scheduling_service');
    $scheduling_service->setClass('Drupal\oneapp_home_scheduling_bo\Services\SchedulingServiceBo');
    $reschedule_visit = $container->getDefinition('oneapp_home_scheduling.v2_0.visit_reschedule_rest_logic');
    $reschedule_visit->setClass('Drupal\oneapp_home_scheduling_bo\Services\v2_0\VisitRescheduleBoRestLogic');
  }

}
