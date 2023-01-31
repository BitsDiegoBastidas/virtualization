<?php

namespace Drupal\oneapp_home_scheduling_bo\Services\v2_0;

use DateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\oneapp\ApiResponse\ErrorBase;
use Drupal\oneapp\Exception\HttpException;
use Drupal\oneapp\Exception\NotFoundHttpException;
use Drupal\oneapp_home_scheduling\Services\v2_0\VisitRescheduleRestLogic;
use Exception;

class VisitRescheduleBoRestLogic extends VisitRescheduleRestLogic {
  /**
   * Get form by Appoinment Id.
   *
   * @param string $id
   *   Number or contract (Or value of billing account).
   * @param string $appointment_id
   *   Id of appointment visit.
   *
   * @return array
   *
   * @throws Exception
   */
  public function get($id, $appointment_id) {
    $this->dateFormat = $this->configBlock['others']['appointmentDate']['format'];
    $this->timeFormat = $this->configBlock['others']['appointmentDateTime']['format'];
    $this->statesList = $this->schedulingService->getStatesVisits();
    $this->getAppointmentData($id, $appointment_id);
    if ((is_array($this->availableDates) && isset($this->availableDates['status']) && $this->availableDates['status'] == 'failed')
      || (is_object($this->availableDates) && isset($this->availableDates->availableTimeslots) && empty($this->availableDates->availableTimeslots))) {
      $hide = TRUE;
      return $this->utils->getEmptyState($hide);
    }
    return [
      'appointmentAddress' => $this->getAppointmentAddress(),
      'appointmentId' => $this->getAppointmentId(),
      'appointmentType' => $this->getAppointmentType(),
      'appointmentStatus' => $this->getAppointmentStatus(),
      'form' => $this->getForm(),
    ];
  }

  /**
   * Send reschedule data.
   *
   * @param string $id
   *   Number or contract (Or value of billing account).
   * @param string $appointment_id
   *   Id of appointment visit.
   * @param array $query_params
   *   Query  string params in rest.
   *
   * @return array
   */
  public function patch($id, $appointment_id, array $query_params) {
    $visit_details = $this->schedulingService->getVisitDetailById($id, $appointment_id);
    $this->visitId = $visit_details->id;
    if ($this->isValidVisitDetails($visit_details)) {
      // Get Parameters to send in the Url.
      $params = $this->getRescheduleVisitParams($id, $visit_details);
      // Get Parameters to send in the query.
      $query = $this->getRescheduleQuery($query_params);
      // Additional headers can be configured if needed.
      $headers = [];
      // Send the confirmation of the rescheduling of the visit.
      $config_reschedule_visit = (object)$this->utils->getConfigGroup('scheduling')['reschedule_visit'];
      $origin = $this->origin ?? (getallheaders()['Origin'] ?? '');
      // Return data according to the response.
      try {
        $this->schedulingService->sendRescheduleVisit($params, $query, $headers);
        $success = (object)$config_reschedule_visit->success;

        $date_format = $this->configBlock["date"]["scheduleDateEmail"]["format"];
        $time_format = $this->configBlock["date"]["scheduleJourneyEmail"]["format"];
        $journey_visit_list = $this->getDateTimeForReschedulingJourney($query_params);

        $date_visit = isset($query["startDateTime"]) ?
        $this->schedulingService->getFormattedDate($query["startDateTime"], $date_format)  : '';

        $journey = (object)[
          'start' => $this->schedulingService->getFormattedDate($journey_visit_list["startDateTime"], $time_format),
          'end'   => $this->schedulingService->getFormattedDate($journey_visit_list["endDateTime"], $time_format)
        ];

        $schedule_journey = (isset($journey->start) && isset($journey->end)) ? "{$journey->start} - {$journey->end}" : $journey;
        $appointment_status = $this->schedulingService->getAppointmentStatus($visit_details->attributes);

        $success = (object) $config_reschedule_visit->success;
        $params = [
          'date' => $date_visit,
          'hour' => $schedule_journey,
          'status' => $appointment_status->label,
          'type_visit' => $visit_details->attributes->{"work-order-type"}
        ];

        $this->schedulingService->sendEmail('reschedule', $this->visitId, $params);

        return [
          'status' => 'success',
          'message' => [
            'title' => $success->title,
            'body' => $this->getSuccessMessageWithEmail($success->message),
            'icon_class' => $success->icon,
          ],
          'actions' => [
            'backVisits' => [
              'label' => $success->link_label,
              'type' => 'link',
              'url' => $this->getUrlByOrigin($origin, $success),
              'show' => (bool)$success->link_show,
            ],
          ],
        ];
      }
      catch(Exception $e) {
        $failed = (object)$config_reschedule_visit->failed;
        return [
          'status' => 'failed',
          'message' => [
            'title' => $failed->title,
            'body' => $failed->message,
            'icon_class' => $failed->icon,
          ],
          'actions' => [
            'backVisits' => [
              'label' => $failed->link_label,
              'type' => 'link',
              'url' => $this->getUrlByOrigin($origin, $failed),
              'show' => (bool)$failed->link_show,
            ],
          ],
        ];
      }
    }
  }

  /**
   * Compare domini with origin header.
   *
   * @param string $origin
   * @param array $urls
   * @return mixed|string
   */
  public function getUrlByOrigin($origin, $urls) {
    $config = \Drupal::config('oneapp.component.config')->get('webview_webcomponent')['configOrigen'];
    $url_condition = [$urls];
    $url = '';

    if (!empty($config['oneappOrigin'])) {
      if ($config['oneappOrigin'] == $origin) {
        $url = (!empty($url_condition[0]->link_url_one_app)) ? $url_condition[0]->link_url_one_app : '';
      }
    }

    if (!empty($config['selfcareOrigin'])) {
      if ($config['selfcareOrigin'] == $origin) {
        $url = (!empty($url_condition[0]->link_url_selfcare)) ? $url_condition[0]->link_url_selfcare : '';
      }
    }

    return $url;
  }

  /**
   * @param $message
   *
   * @return string
   */
  protected function getSuccessMessageWithEmail($message) {
    $email = $this->schedulingService->getEmailFromToken();
    return str_replace('@email', $email, $message);
  }

  /**
   * Retorna el listado de las fechas y horaios disponibles.
   *
   * @param string $id
   *   Billing account Id.
   * @param string $appointment_id
   *   appointmentId.
   */
  private function getAppointmentData($id, $appointment_id) {
    $var_appointment = $this->schedulingService->getVisitDetailById($id, $appointment_id);

    if (isset($var_appointment)) {
      $this->appointment = $var_appointment;
      $sub_id =  $this->appointment->attributes->{"sub-id"};

      $range_date = $this->configBlock['others']['confReschedule']['days'];
      $range_date +=1;
      $format_date = $this->configBlock['others']['dateTimeForRescheduling']['format'];

      $system_date = date("d-m-Y 08:00:00");
      $system_date_end = date("d-m-Y 23:59:59");

      $time_zone = $this->configBlock['others']['confReschedule']['timeZone'];

      $start_date = \Drupal::service('date.formatter')
        ->format(strtotime($system_date), 'custom', $format_date, $time_zone);
      $end_date = \Drupal::service('date.formatter')
        ->format(strtotime("+$range_date day", strtotime($system_date_end)), 'custom', $format_date, $time_zone);

      $this->availableDates = $this->schedulingService->retrieveAvailableDatesByRange($id, $appointment_id, $sub_id, $start_date,$end_date);
      if ($this->availableDates->availableTimeslots) {
        return $this->availableDates->availableTimeslots;
      }
      else {
        return null;
      }
    }
    else {
      $this->launchNotFoundException();
    }
  }

  /**
   * LaunchNotFoundException.
   */
  private function launchNotFoundException() {
    $messages = $this->configBlock['message'];
    $title = !empty($this->configBlock['label']) ? $this->configBlock['label'] . ': ' : '';
    $message = $title . $messages['empty']['label'];
    $error_base = new ErrorBase();
    $error_base->getError()->set('message', $message);
    throw new HttpException(404, $error_base);
  }

  /**
   * Get Form.
   *
   * @return array
   *   form.
   *
   * @throws Exception
   */
  protected function getForm() {
    $form = [];
    $start_date_valid_for = $this->availableDates->availableTimeslots[0]->validFor->startDatetime;
    $end_date_valid_for = $this->availableDates->availableTimeslots[0]->validFor->endDatetime;
    $splitted_time = $this->schedulingService->splitDateTimePeriod(
      $start_date_valid_for,$end_date_valid_for, $this->dateFormat, $this->timeFormat);
    $fields = $this->configBlock['fields'];
    foreach ($fields as $id => $field) {
      $form[$id] = $this->getFormField($field);
    }
    $date = substr($start_date_valid_for, 0, 10);
    $datetime = new DateTime($date);
    $date_format = date_format($datetime , $this->dateFormat);
    $form['scheduleDate']['value'] = $date_format;
    $form['scheduleDate']['formattedValue'] = $date_format;
    $form['scheduleDate']['filters'] = $this->getSanitizeAvailableDates();
    $form['scheduleJourney']['value'] = $this->getValueJourney($start_date_valid_for,$end_date_valid_for);
    $form['scheduleJourney']['formattedValue'] = $splitted_time['journey'];
    $form['scheduleJourney']['options'] = $this->getSanitizeAvailableJournies();
    $form['calendarLabel'] = $this->getCalendarLabel();
    return $form;
  }

  /**
   * Get Form Field.
   *
   * @param object $field
   *   Field.
   *
   * @return array
   *   form field specs
   */
  protected function getFormField($field) {
    $form_field = [];
    $arr_field_validations = ['required', 'minLength', 'maxLength'];
    foreach ($field as $id => $element) {
      if (in_array($id, $arr_field_validations)) {
        $value = $element;
        if ($id == 'required') {
          $value = (bool) $value;
        }
        $form_field['validations'][$id] = $value;
      }
      else {
        $value = $element;
        if ($id == 'show') {
          $value = (bool) $value;
        }
        $form_field[$id] = $value;
      }
    }
    return $form_field;
  }

  /**
   * GetSanitizeAvailableJournies.
   */
  private function getSanitizeAvailableJournies() {
    $date_time_format = $this->configBlock['others']['dateTimeForRescheduling']['format'];
    $response = [];
    $available_time_slots = $this->availableDates->availableTimeslots;
    foreach ($available_time_slots as $slot) {
      $date = substr($slot->validFor->startDatetime, 0, 10);
      $response[] = [
        'scheduleDate' => $this->parseDate($date),
        'value' => $this->parseDateTime($slot->validFor->startDatetime, $slot->validFor->endDatetime, $date_time_format),
        'formattedValue' => $this->parseTime($slot->validFor->startDatetime) . '-' . $this->parseTime($slot->validFor->endDatetime),
      ];
    }
    return $response;
  }

  /**
   * GetSanitizeAvailableDates.
   */
  private function getSanitizeAvailableDates() {
    $response = [
      'options' => [],
    ];
    $diff_date = [];
    $available_ime_slots = $this->availableDates->availableTimeslots;
    foreach ($available_ime_slots as $slot) {
      $start_datee = substr($slot->validFor->startDatetime, 0, 10);
      $end_datee = substr($slot->validFor->endDatetime, 0, 10);
      $start_date = $this->parseDate($start_datee);
      $end_date = $this->parseDate($end_datee);
      if (!in_array($start_date, $diff_date)) {
        $diff_date[] = $start_date;
      }
      if (!in_array($end_date, $diff_date)) {
        $diff_date[] = $end_date;
      }
    }
    foreach ($diff_date as $date) {
      $response['options'][] = [
        'value' => $date,
        'formattedValue' => $date,
      ];
    }
    return $response;
  }

  /**
   * Parse dateTime var in date format.
   *
   * @param string $date_time
   *   dateTime to parse.
   *
   * @return string
   *   Return string in date format
   */
  private function parseDate($date_time) {
    $time = strtotime($date_time);
    return date($this->dateFormat, $time);
  }

  /**
   * Parse dateTime var in time format.
   *
   * @param string $date_time
   *   dateTime to parse.
   *
   * @return string
   *   Return sting in time format
   */
  private function parseTime($date_time) {
    $time_zone = $this->configBlock['others']['appointmentDateTime']['timeZone'];
    $date_time = str_replace('Z', $time_zone, $date_time);
    $time = strtotime($date_time);
    return date($this->timeFormat, $time);
  }

  /**
   * Parse dateTime var in time format.
   *
   * @param string $date_time
   *   dateTime to parse.
   *
   * @return string
   *   Return sting in time format
   */
  private function parseDateTime($date_time_start, $date_time_end, $format) {
    $date_startt = substr($date_time_start, 0, 16);
    $date_endd = substr($date_time_end, 0, 16);
    $time_start = strtotime($date_startt);
    $time_end = strtotime($date_endd);
    date($format, strtotime(substr($date_time_start, 0, 10)));
    $time_format = explode('\T', $format)[1];
    return date($format, $time_start) . '-' . date($time_format, $time_end);
  }

  /**
   * Get Value of Journey
   *
   * @param string $date_time
   *   dateTime to parse.
   *
   * @return string
   *   Return string in time format
   */
  private function getValueJourney($date_time_start,$end_time_start) {
    if (isset($this->configBlock['others']['dateTimeForRescheduling']['format'])) {
      $format = $this->configBlock['others']['dateTimeForRescheduling']['format'];
      $formats = explode('\T', $format);
      $date_format = $formats[0];
      $time_format = $formats[1];
      $splitted_time = $this->schedulingService->splitDateTimePeriod($date_time_start,$end_time_start, $date_format, $time_format);
      return  $splitted_time['date'] . 'T' .  $splitted_time['journey'];
    }
    else {
      return '';
    }
  }

  /**
   * Format the reponse with the block configuarion values (In action section).
   *
   * @return array
   *   Return fields as array of objects.
   */
  public function getActions() {
    $actions = [];
    foreach ($this->configBlock['actions'] as $id => $action) {
      $actions[$id] = [
        'label' => $action['label'],
        'type' => $action['type'],
        'url' => $action['url'],
        'show' => (bool) $action['show'],
      ];
    }
    return $actions;
  }

  /**
   * Get Address array response.
   *
   * @return array
   *   Return fields as array of objects.
   */
  private function getAppointmentAddress() {
    return [
      'label' => $this->configBlock['others']['appointmentAddress']['label'],
      'show' => (bool) $this->configBlock['others']['appointmentAddress']['show'],
      'value' => $this->appointment->address ?? '',
      'formattedValue' => $this->appointment->address ?? '',
    ];
  }

  /**
   * Get Address array response.
   *
   * @return array
   *   Return fields as array of objects.
   */
  private function getAppointmentId() {
    return [
      'label' => $this->configBlock['others']['appointmentId']['label'],
      'show' => (bool) $this->configBlock['others']['appointmentId']['show'],
      'value' => $this->appointment->id,
      'formattedValue' => $this->appointment->id,
    ];
  }

  /**
   * Returns if Valid Visit Details.
   *
   * @param object $visit_details
   *   Visit Details.
   *
   * @return bool
   */
  public function isValidVisitDetails($visit_details) {
    return true;
  }

  /**
   * Returns Params Visit Details.
   *
   * @param object $visit_details
   *   Visit Details.
   *
   * @return array
   */
  public function getRescheduleVisitParams($id, $visit_details) {

    return [
      'id' => $id,
      'appointmentId' => $visit_details->id,
      'externalId' => $visit_details->attributes->{"sub-id"},
    ];
  }

  /**
   * Returns Query Visit Details.
   *
   * @param array $query_params
   *   Query params.
   *
   * @return array
   */
  public function getRescheduleQuery(array $query_params) {
    $dateTime = $query_params['dateTime'];
    $date_parts = explode('T', $dateTime);
    $date = $date_parts[0];
    $journey_parts = explode('-', $date_parts[1]);
    $startDateTime = $date. 'T' . $journey_parts[0]. "-" .$journey_parts[1];
    $endDateTime = $date. 'T' . $journey_parts[2]. "-" .$journey_parts[3];
    return [
      'startDateTime' => $startDateTime,
      'endDateTime' => $endDateTime
    ];
  }


  /**
   * Returns Query Visit Details.
   *
   * @param array $query_params
   *   Query params.
   *
   * @return array
   */
  public function getDateTimeForReschedulingJourney(array $query_params) {
    $dateTime = $query_params['dateTime'];
    $date_parts = explode('T', $dateTime);
    $date = $date_parts[0];
    $journey_parts = explode('-', $date_parts[1]);
    $startDateTime = $date . 'T' . $journey_parts[0];
    $endDateTime = $date . 'T' . $journey_parts[2];
    return [
      'startDateTime' => $startDateTime,
      'endDateTime' => $endDateTime
    ];
  }

  /**
   * Returns Calendar Label
   *
   */
  private function getCalendarLabel() {
    return [
      'label' => '',
      'show' => (bool) $this->configBlock['others']['calendarLabel']['show'],
      'value' => $this->configBlock['others']['calendarLabel']['label'],
      'formattedValue' => $this->configBlock['others']['calendarLabel']['label'],
    ];
  }

  /**
   * Returns Appointment Type data
   *
   */
  private function getAppointmentType() {
    return [
      'label' => '',
      'value' => $this->appointment->attributes->{"work-order-type"},
      'formattedValue' => $this->appointment->attributes->{"work-order-type"},
      'show' => FALSE,
    ];
  }

  /**
   * getAppointmentStatus.
   *
   * @param string $value
   *   Value.
   *
   * @return array
   */
  protected function getAppointmentStatus() {
    $formatted_value = '';
    $value = $this->appointment->attributes->status;
    foreach ($this->statesList as $state) {
      if ($state['value'] == $value) {
        $formatted_value = $state['label'];
      }
    }
    return [
      'label' => '',
      'value' => $value,
      'formattedValue' => $formatted_value,
      'show' => FAlSE,
    ];
  }
}
