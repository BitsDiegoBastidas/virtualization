<?php
namespace Drupal\oneapp_home_scheduling_bo\Services\v2_0;

use Drupal\oneapp_home_scheduling\Services\v2_0\VisitDetailsRestLogic;

class VisitDetailsBoRestLogic extends VisitDetailsRestLogic {

  /**
   * Visit Detail
   *
   * @var array
   */
  protected $visit_detail = null;

  /**
   * Get Schedule by document number.
   *
   * @param string $id
   *   Number or contract (Or value of billing account).
   * @param string $appointment_id
   *   Id of appointment or visit.
   *
   * @return array
   */
  public function get($id, $appointment_id) {
    $this->id = $id;
    $this->appointmentId = $appointment_id;

    $this->statesList = $this->schedulingService->getStatesVisits();
    $this->classesStates = $this->schedulingService->getClassesStatesConfig();
    $visit_response = $this->schedulingService->getVisitDetailsByAppointmentId($id, $this->appointmentId);

    if (isset($visit_response->noData)) {
      return $visit_response;
    }
    else {
      if (!is_array($visit_response)) {
        $visit_response = (array) $visit_response;
        $this->visit_detail = $visit_response;
      }
      $response = $this->findAndSanitize($visit_response, $appointment_id);
      if (!isset($response->noData)) {
        $response['visitStatesList'] = $this->getSanitizeStateList(
          $this->getFilteredStatusList(),
          $response['visitDetails']['appointmentStatus']['value']);
      }
    }
    return $response;
  }

  /**
   * Get filtered status list
   *
   * @return array
   */
  public function getFilteredStatusList() {
    $status_list = $this->schedulingService->getStatesVisits();
    $filtered_status_list = [];

    foreach($status_list as $status){
      $is_show_status = boolval($status['show']);
      if($is_show_status && !$this->isTheStateContainedInTheList($status['value'], $filtered_status_list)){
        $filtered_status_list[] = $status;
      }
    }
    return $filtered_status_list;
  }

  /**
   * Check if the status is in the list of statuses to show
   *
   * @param string $current_visit_status
   * @param array $status_list
   * @return bool
   */
  public function isTheStateContainedInTheList($current_visit_status, $filtered_status_list) {
    return in_array($current_visit_status, $filtered_status_list);
  }

  /**
   * Get status List to validate start datetime
   *
   * @return array
   */
  public function getStatusListToValidateStartDatetime(){
    return ['Initialized', 'Assigned'];
  }

  /**
   * Check visit status to validate star datetime
   *
   * @return void
   */
  public function checkVisitStatusToValidateStartDatetime() {
    $status_active = $this->appointmentDataReceive->attributes->status;
    return in_array($status_active, $this->getStatusListToValidateStartDatetime());
  }

  /**
   * @param array $states_list
   * @param $status
   *
   * @return array
   */
  protected function getSanitizeStateList(array $states_list, $status) {
    usort($states_list, function ($a, $b) {
      return $a['weight'] > $b['weight'];
    });

    $active_status_class = $this->utils->getConfigGroup('scheduling')['visit_status']['visit_status_active'];
    $inactive_status_class = $this->utils->getConfigGroup('scheduling')['visit_status']['visit_status_inactive'];

    $sanitize_state_list = [];

    foreach ($states_list as $state) {
      $sanitize_state_list[] = [
        'label'    => $state["label"],
        'class'     => $this->getClassVisitStatus($state['class'], $active_status_class),
        'value'     => $state["value"],
        'formattedValue' => $state["label"],
        'show' => (bool) $state['show']
       ];
    }

    $current_visit_status = $this->appointmentDataReceive->attributes->status;

    $index_status = $this->serachIndexStatus($current_visit_status, $states_list);

    $total_position = (count($states_list) - 1);

    if(!isset($index_status)) {
      for ($i = 0; $i <= $total_position; $i++) {
        $sanitize_state_list[$i]['class'] = $this->getClassForCurrentStatus($current_visit_status);
      }
    }

    if($index_status > 0) {
      for ($i = 0; $i <= $index_status; $i++) {
          $sanitize_state_list[$i]['class'] = $this->getClassForCurrentStatus($current_visit_status);
      }
    }

    $nex_position = isset($index_status) ? ($index_status + 1) : NULL;
    if (isset($nex_position) && isset($sanitize_state_list[$nex_position])) {
      for ($i = $nex_position; $i <= $total_position; $i++) {
        $sanitize_state_list[$i]['class'] = $inactive_status_class;
      }
    }

    return $sanitize_state_list;
  }

  /**
   * Search index in visit status list
   *
   * @param string $current_visit_status
   * @param array $states_list
   * @return int|null
   */
  public function serachIndexStatus($current_visit_status, $states_list) {
    $index_status = NULL;

    $tag_for_visit_status  = $this->getTagForVisitStatus($current_visit_status);

    foreach ($states_list as  $key => $state) {
      if ($state["value"] == $current_visit_status || $state['label'] == $tag_for_visit_status) {
        $index_status = $key;
      }
    }
    return $index_status;
  }

  /**
   * Get Label for current visit status
   *
   * @param strign $current_visit_status
   * @return string|null
   */
  public function getTagForVisitStatus($current_visit_status) {
    $status_list = $this->schedulingService->getStatesVisits();
    foreach($status_list as $status) {
      if($status["value"] == $current_visit_status) {
        return $status["label"];
      }
    }
    return NULL;
  }

  /**
   * Get class for current visit status
   *
   * @param strign $current_visit_status
   * @return string|null
   */
  public function getClassForCurrentStatus($current_visit_status) {
    $status_list = $this->schedulingService->getStatesVisits();
    foreach ($status_list as $status) {
      if ($status["value"] == $current_visit_status) {
        return $status["class"];
      }
    }
    return NULL;
  }


  /**
   * Get active class for visit status
   *
   * @param string $status_class
   * @param string $active_status_class
   * @return string
   */
  public function getClassVisitStatus($status_class, $active_status_class) {
     return !empty($active_status_class) ? $active_status_class : $status_class;
  }

  /**
   * GetFormCancelation.
   */
  public function getForms() {
    return [];
  }

  /**
   * Find and sanitize into an array an appointmentId.
   *
   * @param array $arr
   *   List of visits.
   * @param string $appointment_id
   *   ID to find.
   *
   * @return array
   */
  protected function findAndSanitize(array $arr, $appointment_id) {
    $suspend_status = $this->configBlock['others']['appointmentSuspendStatus']['label'];
    $result = [];
    $appointment = NULL;
    $is_invalid_date = FALSE;

    $id = $arr["id"] ?? '';
    $appointment = $arr["appointment"];

    $appointment_status = $this->schedulingService->getAppointmentStatus($arr["attributes"]);
    if (isset($appointment) && $appointment_status != $suspend_status) {
      $this->appointmentDataReceive = (object)$arr;

      if ($this->checkVisitStatusToValidateStartDatetime()) {
        $is_invalid_date = $this->schedulingService->isDateLessThanTheCurrentDate($this->visit_detail["appointment"]->startDatetime);
      }

      if ($is_invalid_date) {
        $date_visit = $this->configBlock["messages"]["appointmentDate"]["label"];
        $journey = $this->configBlock["messages"]["appointmentHour"]["label"];
      }
      else {
        $date_format = $this->configBlock['fields']['scheduleDate']['format'];
        $time_format = $this->configBlock['fields']['scheduleJourney']['format'];
        $date_visit = isset($appointment->startDatetime) ? $this->schedulingService->getFormattedDate($appointment->startDatetime, $date_format) :
          $this->configBlock["messages"]["appointmentDate"]["label"];

        $journey = isset($appointment->startDatetime) && isset($appointment->endDatetime) ? $this->schedulingService->formatVisitJourneyPeriod($appointment->startDatetime, $appointment->endDatetime, $time_format) :
          $this->configBlock["messages"]["appointmentHour"]["label"];
      }

      $address = $this->getServiceAddress($id);
      $values = [
        'appointmentId' => $id,
        'subAppointmentId' => $this->visit_detail["attributes"]->{"sub-id"},
        'scheduleDate' => $date_visit,
        'scheduleJourney' => (isset($journey->start) && isset($journey->end)) ? t('@startJourney - @endJourney',
          ['@startJourney' => $journey->start ?? '', '@endJourney' => $journey->end ?? '']) : $journey,
        'appointmentType' => $this->visit_detail["attributes"]->{"work-order-type"},
        'appointmentStatus' => $appointment_status,
        'appointmentServices' => '',
        'appointmentAddress' => $address ?? '',
        'appointmentContractId' => '',
        'technicianDocumentId' => $this->visit_detail["relatedParty"]->id ?? '',
        'technicianName' =>  $this->visit_detail["relatedParty"]->id  ?? '',
        'technicianContractorCompany' => '',
        'technicianPicture' => '',
        'technicianPhone' => '',
        'requestContact' => '',
        'requestCall' => ''
      ];
      $result['visitDetails'] = $this->fillConfigAndData('fields', $values);
      $result['technician'] = $this->fillConfigAndData('technician', $values);
    }
    elseif (isset($appointment) && $appointment_status == $suspend_status) {
      return $this->getSuspendVisitDetail($appointment_status, $id);
    }
    else {
      return $this->schedulingService::EMPTY_STATE;
    }
    return $result;
  }

  /**
   * FillConfigAndData.
   *
   * @param string $key
   *   Key.
   * @param string $values
   *   Values.
   *
   * @return array
   */
  protected function fillConfigAndData($key, $values) {
    $result = [];
    foreach ($this->configBlock[$key] as $id => $field) {
      $result[$id] = $this->getFieldConfigAndData($key, $id, $values[$id]) ?? '';
    }
    return $result;
  }

  /**
   * @param $key
   * @param $field
   *
   * @return bool|mixed
   */
  protected function getShowForField($key, $field) {
    return $this->configBlock[$key][$field]['show'];
  }

  /**
   * GetFieldConfigAndData.
   *
   * @param string $key
   *   Key.
   * @param string $field
   *   Field.
   * @param mixed $value
   *   Value.
   *
   * @return array
   */
  protected function getFieldConfigAndData($key, $field, $value) {
    $formatted_value = $value;
    if ($field == 'appointmentStatus') {
      $value = $value->value;
      $formatted_value = $this->getFormattedStatusValue($value);
    }
    if (isset($this->configBlock[$key][$field])) {
      return [
        'label' => $this->configBlock[$key][$field]['label'],
        'value' => isset($value) ? $value : '',
        'formattedValue' => isset($value) ? $formatted_value : '',
        'show' => (bool) $this->getShowForField($key, $field),
      ];
    }
    else {
      return [];
    }
  }

  /**
   * Format the reponse with the block configuarion values (In action section).
   *
   * @param string $status
   *   Status of appointment or visit.
   *
   * @return array
   *   Return fields as array of objects.
   */
  public function getActions($status) {
    $actions = [];
    $is_valid_date = False;
    $visit_status = $this->visit_detail["attributes"]->status;

    if($this->checkVisitStatusToValidateStartDatetime()) {
      $is_valid_date = $this->schedulingService->isDateLessThanTheCurrentDate($this->visit_detail["appointment"]->startDatetime);
    }

    foreach ($this->configBlock['actions'] as $id => $action) {

      if ($id != 'rescheduleVisit') {

        $actions[$id] = [
          'label' => $action['label'],
          'type' => $action['type'],
          'url' => $action['url'],
          'show' => $this->getValueShow($action['showConditional'], $status),
        ];
      }
      else {
        $actions[$id] = [
          'label' => $is_valid_date == False ? $action['label'] : 'Agendar',
          'type' => $action['type'],
          'url' => $action['url'],
          'show' => ($this->getValueShow($action['showConditional'], $status) && $this->isStatusValidateToShowRescheduleAction($visit_status)),
        ];
      }
    }
    return $actions;
  }


  /**
   * Search status to show reschedule button
   *
   * @return array
   */
  public function isStatusValidateToShowRescheduleAction($visti_status) {
    $valid_status_list = ['Initialized', 'Assigned'];
    return in_array($visti_status, $valid_status_list);
  }
}
