<?php

namespace Drupal\oneapp_home_scheduling_bo\Services\v2_0;

use Drupal\oneapp_home_scheduling\Services\v2_0\ScheduledVisitsRestLogic;

class ScheduledVisitsBoRestLogic extends ScheduledVisitsRestLogic {

  /**
   * Get Schedule by document number.
   *
   * @param string $id
   *   Number or contract (Or value of billing account).
   *
   * @return array
   */
  public function get($id) {
    $visit_list = [];
    $scheduled_visits = $this->schedulingService->getScheduledVisitsById($id);

    if (!empty($scheduled_visits) && is_array($scheduled_visits)) {
      foreach ($scheduled_visits as $visit) {
        $status = $visit->attributes->status;
        if ($status != "Canceled" && $status != "Completed") {
          $row = [];
          $appointment_status = $this->schedulingService->getAppointmentStatus($visit->attributes);

          $is_invalid_date = False;

          if ($this->checkVisitStatusToValidateStartDatetime($status)) {
            $is_invalid_date = $this->schedulingService->isDateLessThanTheCurrentDate($visit->appointment->startDatetime);
          }

          if ($is_invalid_date) {
            $date_visit = $this->configBlock["messagesFields"]["appointmentDate"]["label"];
            $journey = $this->configBlock["messagesFields"]["appointmentHour"]["label"];
          }
          else {
            $date_format = $this->configBlock['fields']['scheduleDate']['format'];
            $time_format = $this->configBlock['fields']['scheduleJourney']['format'];
            $date_visit = isset($visit->appointment->startDatetime) ?
              $this->schedulingService->getFormattedDate($visit->appointment->startDatetime, $date_format) : $this->configBlock["messagesFields"]["appointmentDate"]["label"];
            $journey = isset($visit->appointment->startDatetime) && isset($visit->appointment->endDatetime) ?
              $this->schedulingService->getFormattedVisitSchedule($visit->appointment->startDatetime, $visit->appointment->endDatetime, $time_format) :
              $this->configBlock["messagesFields"]["appointmentHour"]["label"];
          }

          $id = $visit->id;
          $atributes = $visit->attributes;
          $sub_id = $atributes->{'sub-id'};
          foreach ($this->configBlock['fields'] as $field_name => $field) {
            switch ($field_name) {
              case 'appointmentId':
                $row[$field_name]['label'] = $field['label'];
                $row[$field_name]['show'] = (bool) $field['show'];
                $row[$field_name]['value'] = $id;
                $row[$field_name]['formattedValue'] = $id;
                break;

              case 'subAppointmentId':
                $row[$field_name]['label'] = $field['label'];
                $row[$field_name]['show'] = (bool) $field['show'];
                $row[$field_name]['value'] = $sub_id; //poner id|subid
                $row[$field_name]['formattedValue'] = $sub_id;
                break;

              case 'scheduleDate':
                $row[$field_name]['label'] = $field['label'];
                $row[$field_name]['show'] = (bool) $field['show'];
                $row[$field_name]['value'] = $date_visit;
                $row[$field_name]['formattedValue'] = $date_visit;
                break;

              case 'scheduleJourney':
                $row[$field_name]['label'] = $field['label'];
                $row[$field_name]['show'] = (bool) $field['show'];
                $row[$field_name]['value'] = (isset($journey->start) && isset($journey->end)) ?
                  t(
                    '@startJourney - @endJourney',
                    ['@startJourney' => $journey->start ?? '', '@endJourney' => $journey->end ?? '']
                  ) : $journey;
                $row[$field_name]['formattedValue'] = (isset($journey->start) && isset($journey->end)) ?
                  t(
                    '@startJourney - @endJourney',
                    ['@startJourney' => $journey->start ?? '', '@endJourney' => $journey->end ?? '']
                  ) : $journey;
                break;

              case 'appointmentStatus':
                $row[$field_name]['label'] = $field['label'];
                $row[$field_name]['show'] = (bool) $field['show'];
                $row[$field_name]['class'] = $appointment_status->class;
                $row[$field_name]['value'] = $appointment_status->value;
                $row[$field_name]['formattedValue'] = $appointment_status->label;
                break;

              default:
                break;
            }
          }
          $row['appointmentType'] = [
            'label' => '',
            'value' => $atributes->{'work-order-type'},
            'formattedValue' => $atributes->{'work-order-type'},
            'show' => (bool) $field['show'],
          ];

          $actions = $this->configBlock['actions'];
          foreach ($actions as $action => $value) {
            $row[$action]['value'] = (bool) $value["showConditional"][$appointment_status->value];
          }
          array_push($visit_list, $row);
        }
      }

      if(empty($visit_list)){
        return $this->schedulingService::HIDE_STATE;
      }

      $scheduled_response = ["visitList" => $visit_list];
    }
    else {
      $scheduled_response = $this->schedulingService::HIDE_STATE;
    }
    return $scheduled_response;
  }

  /**
   * Check visit status to validate start datetime
   *
   * @param string $current_visit_status
   * @return bool
   */
  public function checkVisitStatusToValidateStartDatetime($current_visit_status) {
    return in_array($current_visit_status, $this->getStatusListToValidateStartDatetime());
  }

  /**
   * Get status List to validate start datetime
   *
   * @return array
   */
  public function getStatusListToValidateStartDatetime() {
    return ['Initialized', 'Assigned'];
  }

  /**
   * Format the reponse with the block configuration values (In action section).
   *
   * @return array
   *   Return fields as array of objects.
   */
  public function getActions() {
    $actions = $this->configBlock['actions'];
    foreach ($actions as $name => $action) {
      $actions[$name]['show'] = (bool) $action['show'];
      unset($actions[$name]["showConditional"]);
    }
    return $actions;
  }
}
