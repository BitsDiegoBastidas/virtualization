<?php

namespace Drupal\oneapp_home_scheduling_bo\Services;

use DateTime;
use Drupal\oneapp\ApiResponse\ErrorBase;
use Drupal\oneapp\Exception\HttpException;
use Drupal\oneapp\Exception\NotFoundHttpException;
use Drupal\oneapp_home_scheduling\Services\SchedulingService;
use Drupal\Core\Datetime\Entity\DateFormat;

/**
 * Class SchedulingServiceBo.
 */
class SchedulingServiceBo extends SchedulingService {

  /**
   * Check if the visit date is less than the current date
   *
   * @param string $date_visit
   * @return bool
   */
  public function isDateLessThanTheCurrentDate($date_visit) {

    $current_datetime = new DateTime();
    $current_date_formatted = $current_datetime->format("Y-m-d");
    $current_date = new DateTime($current_date_formatted);

    $visit_datetime = new DateTime($date_visit);
    $visit_date_formatted = $visit_datetime->format("Y-m-d");
    $visit_date = new DateTime($visit_date_formatted);


    return ($current_date > $visit_date);
  }

  /**
   * Get Formatted Date
   *
   * @param string $date
   * @param string $current_format_date
   * @return string
   */
  public function getFormattedDate($date, $current_format_date) {
    $format_pattern = null;
    $list_format_dates = DateFormat::loadMultiple();
    $date_formatter_service = \Drupal::service('date.formatter');

    foreach ($list_format_dates as $name => $format) {
      if ($name == $current_format_date) {
        $format_pattern = $format->getPattern();
        break;
      }
    }

    return $date_formatter_service->format(strtotime($date), 'custom', $format_pattern, date_default_timezone_get());
  }

  /**
   * formatted date
   *
   * @param string $date
   * @param string $format_date
   * @return void
   */
  public function formattedDate($date, $format_date) {
    $date_visit = new DateTime($date);
    return $date_visit->format($format_date);
  }

  /**
   * Get appointment Status logic (refinar y dejar en utils).
   * @param object $visit
   *
   * @return object
   *   appointment status.
   */
  public function getAppointmentStatus($visit) {
    $status = (isset($visit->status) ? $visit->status : '');
    $states_list = $this->getStatesVisits();
    $index = array_search($status, array_column($states_list, 'value'));
    return (object) $states_list[$index];
  }

  /**
   * @param $start_date_time
   * @param $end_date_time
   * @param $date_format
   *
   * @return object
   */
  public function formatCreationDate($start_date_time, $end_date_time, $date_format) {
    $data_time = $start_date_time ? $start_date_time : $end_date_time;
    $schedule_date = substr($data_time, 0, 10);
    return $this->utils->getFormattedValue($date_format, $schedule_date);
  }

  /**
   * FormatVisitDateTime.
   *
   * @param string $appointment_date_time
   *   AppointmentDateTime.
   * @param string $date_format
   *   dateFormat.
   *
   * @return object
   */
  public function formatVisitDate($appointment_date_time, $date_format) {
    $schedule_date = substr($appointment_date_time, 0, 10);
    return $this->utils->getFormattedValue($date_format, $schedule_date);
  }

  /**
   * FormatVisitDateTime.
   *
   * @param string $appointment_date_time
   *   AppointmentDateTime.
   * @param string $time_format
   *   timeFormat.
   *
   * @return object
   */
  public function formatVisitJourneyPeriod($appointment_start_date_time,$appointment_end_date_time, $time_format) {
    $start_date = explode(" ", substr($appointment_start_date_time, -8, 8));
    $end_date = explode(" ", substr($appointment_end_date_time, -8, 8));
    $start_journey = $this->utils->getFormattedValue($time_format, $start_date[0]);
    $end_journey = $this->utils->getFormattedValue($time_format, $end_date[0]);
    return (object) [
      'start' => $start_journey,
     'end' => $end_journey,
    ];
  }

  /**
   * FormatVisitDateTime.
   *
   * @param string $appointment_date_time
   *   AppointmentDateTime.
   * @param string $time_format
   *   timeFormat.
   *
   * @return object
   */
  public function getFormattedVisitSchedule($appointment_start_date_time, $appointment_end_date_time, $time_format) {
    $start_journey = $this->getFormattedDate($appointment_start_date_time, $time_format);
    $end_journey = $this->getFormattedDate($appointment_end_date_time, $time_format);
    return (object) [
      'start' => $start_journey,
      'end' => $end_journey,
    ];
  }

  /**
   * @param $id
   * @param $appointmentId
   *
   * @return array|null
   */
  public function getScheduledEndpointBySubAppointmentId($id, $appointmentId) {
    $visit_details = null;
    try {
      $response = $this->manager
        ->load('oneapp_home_scheduling_v2_0_scheduled_visits_endpoint')
        ->setParams([
          'id' => $id
        ])
        ->setHeaders([])
        ->setQuery([])
        ->sendRequest();
      foreach ($response->Appointment as $appointment) {
        if ($appointment->id->id == $appointmentId) {
          $visit_details = $appointment;
          break;
        }
      }
      return $visit_details;
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * @param $id
   * @param $appointmentId
   *
   * @return array|null
   */
  public function getVisitDetailsByAppointmentId($id, $appointmentId) {
    $visit_details = null;
    try {
      $response = $this->manager
        ->load('oneapp_home_scheduling_v2_0_visit_details_endpoint')
        ->setParams([
          'id' => $id
        ])
        ->setHeaders([])
        ->setQuery([])
        ->sendRequest();
      foreach ($response->Appointment as $appointment) {
        if ($appointment->id == $appointmentId) {
          $visit_details = $appointment;
          break;
        }
      }
      return $visit_details;
    } catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Retorna el detalle de la visita segun el subappointment id
   *
   * @param string $id
   *   Billing account Id.
   * @param string $appointment_id
   *   appointmentId.
   * @param string $query_params
   *   queryParams.
   *
   * @return HttpException|\Exception
   *   Subscriptions.
   */
  public function getVisitDetailsByIdSubApointment($id, $appointment_id, $query_params) {
    $visit_details = NULL;
    try {
      $response = $this->manager
        ->load('oneapp_home_scheduling_v2_0_visit_details_endpoint')
        ->setParams(['id' => $id])
        ->setHeaders([])
        ->setQuery([])
        ->sendRequest();
      // Filtra el listado de visitas por el Id de la Visita.
      foreach ($response->Appointment as $appointment) {
        if ($appointment->id == $appointment_id && $appointment->subAppointmentID == $query_params['subAppointmentId']) {
          $visit_details = $appointment;
          break;
        }
      }
      return $visit_details;
    }
    catch (\Exception $e) {
      return $visit_details;
    }
  }

  /**
   * Retorna el listado de las fechas y horaios disponibles.
   *
   * @param string $id
   *   Billing account Id.
   * @param string $external_appointment_id
   *   externalAppointmentId.
   * @param string $products_id
   *   productsId.
   * @param string $startDate
   *   startDate.
   * @param string $endDate
   *   endDate.
   *
   * @return array
   *   Subscriptions.
   */
  public function retrieveAvailableDatesByRange($id, $appointment_id, $sub_appointment_id, $start_date, $end_date) {
    try {
      return $this->manager
        ->load('oneapp_home_scheduling_v2_0_visit_available_reschedule_endpoint')
        ->setParams([
          'id' => $id,
          'externalAppointmentId' => $appointment_id,
          'productsId' => $sub_appointment_id,
        ])
        ->setHeaders([])
        ->setQuery([
          'startDateTime' => $start_date,
          'endDateTime' => $end_date,
        ])
        ->sendRequest();
    }
    catch (\Exception $e) {
      return [
        'error' => TRUE,
        'code' => 404,
        'message' => $e->getMessage(),
        'status' => 'failed',
        'response' => json_decode($e->getPrevious()->getResponse()->getBody()->__toString() ?? "", TRUE)
      ];
    }

  }

  /**
   * Split DateTime.
   *
   * @param string $appointment_date_time
   *   AppointmentDateTime.
   * @param string $date_format
   *   dateFormat.
   * @param string $time_format
   *   timeFormat.
   *
   * @return array
   */
  public function splitDateTimePeriod($appointment_start_date_time,$appointment_end_date_time, $date_format, $time_format) {
    $date = substr($appointment_start_date_time, 0, 10);

    $time = strtotime($date);
    $journey_start = substr($appointment_start_date_time, -15, 15);
    $journey_end = substr($appointment_end_date_time, -15, 15);
    $time_parts_start = explode('-', $journey_start);
    $time_parts_end = explode('-', $journey_end);
    $start = isset($time_parts_start[0]) ? $time_parts_start[0] : 'now';
    $end = isset($time_parts_end[0]) ? $time_parts_end[0] : 'now';
    $time_start = strtotime($start);
    $time_end = strtotime($end);
    return [
      'date' => date($date_format, $time),
     'journey' => date($time_format, $time_start) . '-' . date($time_format, $time_end),
    ];
  }

  /**
   * Get Visit Details by Id.
   *
   * @param string $id
   *   Document number to do query.
   * @param string $appointment_id
   *   Visit Appointment Id .
   * @return object
   *   appoinment.
   */
  public function getVisitDetailById($id, $appointment_id) {
    $visit_details = NULL;
    try {
      $response = $this->manager
        ->load('oneapp_home_scheduling_v2_0_scheduled_visits_endpoint')
        ->setParams(['id' => $id])
        ->setHeaders([])
        ->setQuery([])
        ->sendRequest();
      // Filtra el listado de visitas por el Id de la Visita.
      foreach ($response->Appointment as $appointment) {
        if ($appointment->id == $appointment_id) {
          $visit_details = $appointment;
          break;
        }
      }
      return $visit_details;
    } catch (\Exception $e) {
      return $visit_details;
    }
  }

  /**
   * @param array $params
   * @param array $query
   * @param array $headers
   * @return object|null
   */
  public function sendRescheduleVisitEndpoint(array $params, array $query = [], array $headers = []) {
    try {
      return $this->manager
        ->load('oneapp_home_scheduling_v2_0_visit_reschedule_endpoint')
        ->setParams($params)
        ->setHeaders($headers)
        ->setQuery($query)
        ->sendRequest();
    }
    catch (\Exception $e) {
      return (object) [
        'error' => TRUE,
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
        'status' => 'failed',
        'response' => json_decode($e->getPrevious()->getResponse()->getBody()->__toString() ?? "", TRUE)
      ];
    }
  }
}
