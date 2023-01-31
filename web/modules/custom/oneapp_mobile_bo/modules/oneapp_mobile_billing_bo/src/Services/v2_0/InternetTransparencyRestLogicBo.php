<?php

namespace Drupal\oneapp_mobile_billing_bo\Services\v2_0;

use Drupal\oneapp_mobile_billing\Services\v2_0\InternetTransparencyRestLogic;
use Drupal\Core\StreamWrapper\PublicStream;
use DateTime;

/**
 * Class InternetTransparencyRestLogicBo.
 */
class InternetTransparencyRestLogicBo extends InternetTransparencyRestLogic {

  const KIBI = 1024;

  const KB = 1024;

  const MB = 1024 * self::KB;

  const GB = 1024 * self::MB;

  const DAYS_FOR_FILTERING = 7;

  const DATE_FORMAT = 'Y-m-d\TH:i:s';

  const TRAFFIC_DETAILS_DATE_FORMAT = 'Y-m-d';

  const HOURS_DAY = 24;

  private $query_params = [];

  private $msisdn = NULL;

  private $app_consumption_list = NULL;

  private $consumption_app_by_date_list = NULL;

  private $total_apps_list = 0;

  private $date_range_for_chart = [];

  /**
   * Responds to GET requests.
   *
   * @param string $msisdn
   *   Msisdn.
   *
   * @param array $query_params
   *
   * @return array
   *   The response to summary configurations.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   * @throws \Exception
   *   Throws exception expected.
   */
  public function get($msisdn, $query_params=[]) {
    $this->query_params = $query_params;
    $this->msisdn = $msisdn;

    $this->calculateDataUsedByApplication();

    if ((isset($this->app_consumption_list->code) && $this->app_consumption_list->code == '404' ) || empty($this->app_consumption_list->trafficDetails)) {
      return $this->makeResponseForAccountWithoutAppConsumption();
    }

    return $this->getForm();
  }

  /**
   * Create response for account without app consumption
   *
   * @return array
   */
  public function makeResponseForAccountWithoutAppConsumption() {
    $form = [];

    $form['packageHistory']['summaryConsumption']['usedData'] = [];
    $form['packageHistory']['summaryConsumption']['quota'] = [];
    $form['packageHistory']['emptyMessage'] = $this->configBlock["contentFields"]["messages"]["empty"];
    $form['packageHistory']['filters'] = $this->makeFilters();
    $form['packageHistory']['itemsDisplayByBatches'] = $this->configBlock["fields"]["total_items_batches"]["label"];
    $form['packageHistory']['appConsumption'] = [];

    return $form;
  }

  /**
   * Get data to display
   *
   * @return array
   */
  public function getForm() {
    $form = [];

    $form['packageHistory']['summaryConsumption']['usedData'] = [];
    $form['packageHistory']['summaryConsumption']['quota'] = [];

    $form['packageHistory']['filters'] = $this->makeFilters();
    $form['packageHistory']['itemsDisplayByBatches'] = $this->configBlock["fields"]["total_items_batches"]["label"];
    $form['packageHistory']['itemsDisplayByGraph'] = $this->configBlock["fields"]["number_apps_to_load_graph"]["label"];
    $form['packageHistory']['appLabels'] = $this->makeConsumptionAppLabels();
    $form['packageHistory']['appConsumption'] = $this->getConsumptionPerApp();
    $form['packageHistory']['dateRangeForChart'] = $this->date_range_for_chart;

    $form['packageHistory']['summaryConsumption']['usedData'] = [
      "label" =>  $this->configBlock["consumption"]["fields"]["remainingConsumption"]["label"],
      "show"  =>  (bool)$this->configBlock["consumption"]["fields"]["remainingConsumption"]["show"],
      "value" =>  $this->getTotalDataConsumed($form['packageHistory']['appConsumption'])['value'],
      "formattedValue" =>  $this->getTotalDataConsumed($form['packageHistory']['appConsumption'])['formattedValue'],
    ];
    $form['packageHistory']['summaryConsumption']['quota'] = [
      "label" =>  $this->configBlock["consumption"]["fields"]["quota"]["label"],
      "show"  =>  (bool)$this->configBlock["consumption"]["fields"]["quota"]["show"],
      "value" =>  $this->getFormattedValueInGB($this->app_consumption_list->totalVolumeBytes)['value'],
      "formattedValue" => $this->getFormattedValueInGB($this->app_consumption_list->totalVolumeBytes)['formattedValue'],
    ];

    $form['packageHistory']['navigationMessage'] = $this->configBlock["navigationMessage"]["message"];
    $form['packageHistory']['lastUpdatedAt'] = [
      "label" =>  $this->configBlock["fields"]["last_updated_at"]["label"],
      "show"  =>  (bool)$this->configBlock["fields"]["last_updated_at"]["show"],
      "value" =>  $this->app_consumption_list->lastUpdatedAt,
      "formattedValue" => $this->billingService->getFormattedDate($this->app_consumption_list->lastUpdatedAt, $this->configBlock["fields"]["date_time_of_the_last_update"]["format"]),
    ];

    return $form;
  }

  /**
   * Calculate data used by application
   *
   * @return void
   */
  public function calculateDataUsedByApplication() {
    if ($this->query_params['group'] == 0 || empty($this->query_params['group'])) {
      $this->app_consumption_list = $this->getConsumptionPerDay();
    }
    else {
      $this->app_consumption_list = $this->getConsumptionPerHour();
    }
  }

  /**
   * Get consumption per app
   *
   * @return array
   */
  public function getConsumptionPerApp() {
    $consumption_data_for_apps = [];
    $app_index = 0;
    $app_details_list = $this->getListOfConsumptionByApps();
    $app_detail = NULL;

    $this->getCalculationForAppConsumption($app_details_list);

    foreach ($app_details_list as $app_detail) {
      $localized_app = FALSE;
      $consumption_list = $this->makeConsumptionList($app_detail['name']);
      $used_data = array_sum($consumption_list);
      foreach ($this->configBlock["aditional"] as $index => $configured_application) {
        if ($app_detail['name'] == $configured_application['value']) {
          $localized_app = TRUE;
          $app_detail = $configured_application;
          unset($this->configBlock["aditional"][$index]);
          break;
        }
      }

      if ($localized_app) {
        $this->makeAppDataInConfigurationList($consumption_data_for_apps, $app_index, $app_detail, $used_data);
      }
      else {
        $this->getDataForAppsWithoutConfiguration($consumption_data_for_apps, $app_index, $app_detail['name'], $used_data);
      }
      $app_index++;
    }

    $this->total_apps_list = count($consumption_data_for_apps);

    usort($consumption_data_for_apps, function ($a, $b) {
      return $a['dataUsed']['value'] < $b['dataUsed']['value'];
    });

    return $consumption_data_for_apps;
  }

  /**
   * Get calculation for app consumption
   *
   * @param array $app_details_list
   * @return void
   */
  public function getCalculationForAppConsumption(array $app_details_list) {
    $consumption_date = NULL;
    if ($this->query_params['group'] == 0 || empty($this->query_params['group'])) {
      $this->consumption_app_by_date_list = $this->calculateAppConsumptionPerWeek($app_details_list);
    }
    else {
      $consumption_date = $this->getSelectecOptionDate();
      $app_consumption_per_hour = $this->calculateAppConsumptionPerHour($app_details_list, $consumption_date);
      $this->consumption_app_by_date_list = $this->checkApplicationConsumptionInHours($app_consumption_per_hour);
    }
  }

  /**
   * Check application consumption in hours
   *
   * @param array $consumption_apps
   * @return array
   */
  public function checkApplicationConsumptionInHours(array $consumption_apps) {
    $consumption_list = [];
    foreach($consumption_apps as $app_name => $consumption) {
       foreach($consumption as $date => $hours) {
          for ($i=0; $i < self::HOURS_DAY; $i++) {
            if (isset($hours[$i])) {
              $consumption_list[$app_name][$date][$i] = $hours[$i];
            }
            else {
              $consumption_list[$app_name][$date][$i] = 0;
            }
          }
       }
    }
    return $consumption_list;
  }

  /**
   * makeConsumptionList
   *
   * @param [type] $app_name
   * @return array
   */
  public function makeConsumptionList($app_name, $unit_measure = False) {
    $consumption_list = [];
    if ($this->query_params['group'] == 0 || empty($this->query_params['group'])) {
      foreach($this->consumption_app_by_date_list[$app_name] as $consumption_per_date){
        $consumption_list[] = $unit_measure ? $this->getValueUnitsMeasure($consumption_per_date)['value'] : $consumption_per_date;
      }
    }
    else {
      foreach ($this->consumption_app_by_date_list[$app_name] as $consumption_per_date) {
        foreach($consumption_per_date as $consumption) {
          $consumption_list[] = $unit_measure ? $this->getValueUnitsMeasure($consumption)['value'] : $consumption ;
        }
      }
    }
    return $consumption_list;
  }

  /**
   * Get date to filter consumption
   *
   * @return string
   */
  public function getSelectecOptionDate() {
    $date_list = $this->billingService->listOfDatesToFilterConsumption(self::DAYS_FOR_FILTERING, self::TRAFFIC_DETAILS_DATE_FORMAT);

    $period = $this->query_params['period'];

    $total_dates_list = count($date_list) - 1;

    $last_position_dates = (empty($period) || $period > $total_dates_list) ? 0 : $period;

    return $date_list[$last_position_dates]["formattedValue"];
  }

  /**
   * Create labels for apps for consumption per week
   *
   * @return array
   */
  public function createLabelsForAppsForConsumptionPerWeek() {
    $date_list = $this->billingService->listOfDatesToFilterConsumption(self::DAYS_FOR_FILTERING, self::TRAFFIC_DETAILS_DATE_FORMAT);
    $days_of_week_list = [];

    foreach($date_list as $date) {
      $timestamp = strtotime($date["formattedValue"]);
      $day = date('l', $timestamp);
      $days_of_week_list[] =  $this->getDayInSpanish($day);
    }
    return array_reverse($days_of_week_list);
  }

  /**
   * Get day in spanish
   *
   * @param string $day
   * @return array
   */
  public function getDayInSpanish($day) {
    $days = [
      'Monday' => 'lun',
      'Tuesday' => 'mar',
      'Wednesday' => 'mié',
      'Thursday' => 'jue',
      'Friday' => 'vie',
      'Saturday' => 'sáb',
      'Sunday' => 'dom',
    ];

    return $days[$day];
  }

  /**
   * Calculates weekly consumption of each app for the graph
   *
   * @param array $app_details_list
   * @return array
   */
  public function calculateAppConsumptionPerWeek(array $app_details_list) {
    $date_list = $this->billingService->listOfDatesToFilterConsumption(self::DAYS_FOR_FILTERING, self::TRAFFIC_DETAILS_DATE_FORMAT);
    $date_list = array_reverse($date_list);

    $date_format = $this->configBlock["fields"]["date_format_for_chart"]["format"];
    $this->date_range_for_chart = [
      'startDateRange' => $this->billingService->getFormattedDate($date_list[0]["formattedValue"], $date_format),
      'endDateRange' => $this->billingService->getFormattedDate($date_list[6]["formattedValue"], $date_format),
    ];

    $consumption_app_by_date_list = [];

    foreach($app_details_list as $app) {
      foreach($date_list as $date) {
        $consumed_bytes_accumulator = 0;
        foreach ($this->app_consumption_list->trafficDetails as $details) {
          if($app['name'] == $details->appName && $date['formattedValue'] == $details->date) {
            $consumed_bytes_accumulator += $details->trafficVolumeBytes;
          }
        }
        $consumption_app_by_date_list[$app['name']][$date['formattedValue']] = $consumed_bytes_accumulator;
      }
    }
    return $consumption_app_by_date_list;
  }

  /**
   * Return Labels for hourly consumption on the chart
   *
   * @return array
   */
  public function createLabelsForAppsForConsumptionPerHour() {
     return ['12', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12','1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11'];
  }

  /**
   * Calculate consumption per hour for each app
   *
   * @param array $app_details_list
   * @param string $consumption_day
   * @return array
   */
  public function calculateAppConsumptionPerHour(array $app_details_list, $consumption_day) {
    $consumption_app_by_hour_list = [];
    $app_name = NULL;

    $date_format = $this->configBlock["fields"]["date_format_for_chart"]["format"];
    $this->date_range_for_chart = [
      'startDateRange' => $this->billingService->getFormattedDate($consumption_day, $date_format),
    ];

    foreach ($app_details_list as $app) {
      $app_name = $app['name'];
        foreach ($this->app_consumption_list->trafficDetails as $details) {
          if ($app_name == $details->appName && $consumption_day == $details->date) {
            $consumption_app_by_hour_list[$app_name][$consumption_day][$details->hour] = $details->trafficVolumeBytes;
          }
        }
    }
    return $consumption_app_by_hour_list;
  }

  /**
   * Create the labels for the consumption graph
   *
   * @return array
   */
  public function makeConsumptionAppLabels() {
    if ($this->query_params['group'] == 0 || empty($this->query_params['group'])) {
      return $this->createLabelsForAppsForConsumptionPerWeek();
    }
    else {
      return $this->createLabelsForAppsForConsumptionPerHour();
    }
  }

  /**
   * Get List of consumption by apps
   *
   * @return array
   */
  public function getListOfConsumptionByApps() {
    $app_details_list = [];
    $app_index = 0;

    foreach ($this->app_consumption_list->trafficDetails as $details) {
      $index = $this->searchAppNameInList($app_details_list, $details->appName);
      if ($index > -1) {
        $app_details_list[$index] = [
          'name' => $details->appName,
        ];
      } else {
        $app_details_list[$app_index] = [
          'name' => $details->appName,
        ];
        $app_index++;
      }
    }

    return $app_details_list;
  }

  /**
   * Search app name in details list
   *
   * @param array $app_details_list
   * @param string $app_name
   * @return int
   */
  public function searchAppNameInList(array $app_details_list, $app_name) {
    foreach ($app_details_list as $key => $app) {
      if ($app['name'] == $app_name) {
        return $key;
      }
    }
    return -1;
  }

  /**
   * Calculate the total data consumed in GB
   *
   * @param array $appConsumption
   * @return array
   */
  public function getTotalDataConsumed(array $appConsumption) {
    $total_data = 0;

    foreach ($appConsumption as $consumption) {
      $total_data += $consumption['dataUsed']['value'];
    }

    return $this->getFormattedValueInGB($total_data);
  }

  /**
   * Get app data in configuration list
   *
   * @param array $consumption_data_for_apps
   * @param int $app_index
   * @param array $app
   * @param float $used_data
   * @return array
   */
  public function makeAppDataInConfigurationList(array &$consumption_data_for_apps, $app_index, $app, $used_data) {
    $consumption_data_for_apps[$app_index]['appName'] = [
      "label" => "",
      "show" => (bool)$app['show'],
      "value" => $app['value'],
      "formattedValue" => $app['label'],
    ];

    $fid = (!empty($app["banner"]["url"][0])) ?  $app["banner"]["url"][0] : 0;

    $consumption_data_for_apps[$app_index]['appIcon'] = [
      "label" => "",
      "show" => (bool)$app['show'],
      "value" => $app["banner"]["url"][0],
      "formattedValue" => $this->utilsService->getImageUrl($fid),
    ];

    $consumption_data_for_apps[$app_index]['appColor'] =  [
      "label" => "",
      "show" => true,
      "value" => $app['class'],
      "formattedValue" => $app['class'],
    ];

    $consumption_data_for_apps[$app_index]["dataUsed"] = [
      "label" => "",
      "show" => (bool)$app['show'],
      "value" => $used_data,
      "formattedValue" => $this->getValueUnitsMeasure($used_data, TRUE)['formattedValue'],
    ];

    $consumption_data_for_apps[$app_index]["percentageDataUsed"] = [
      "label" => "",
      "show" => (bool)$this->configBlock["fields"]["percentage_data_used"]["show"],
      "value" => $this->getUsedDataPercentage($used_data, $this->app_consumption_list->totalVolumeBytes)['value'],
      "formattedValue" => $this->getUsedDataPercentage($used_data, $this->app_consumption_list->totalVolumeBytes)['formattedValue'],
    ];

    $consumption_list = $this->makeConsumptionList($app['value'], TRUE);

    $consumption_data_for_apps[$app_index]["appDataset"] = [
      "label" => "",
      "show" => (bool)$app['show'],
      "value" => $consumption_list,
      "formattedValue" => $consumption_list
    ];
  }

  /**
   * Return data for apps
   *
   * @param array $consumption_data_for_apps
   * @param int $app_index
   * @param string $app_name
   * @param float $used_data
   * @return array
   */
  public function getDataForAppsWithoutConfiguration(array &$consumption_data_for_apps, $app_index, $app_name, $used_data) {
    $consumption_data_for_apps[$app_index]['appName'] = [
      "label" => "",
      "show" => TRUE,
      "value" => $app_name,
      "formattedValue" => $app_name,
    ];

    $fid = (!empty($this->configBlock["defaultSettingsApps"]["banner"]["url"][0])) ? $this->configBlock["defaultSettingsApps"]["banner"]["url"][0] : 0;

    $consumption_data_for_apps[$app_index]['appIcon'] = [
      "label" => "",
      "show" => TRUE,
      "value" => $this->configBlock["defaultSettingsApps"]["banner"]["url"],
      "formattedValue" =>  $this->utilsService->getImageUrl($fid),
    ];

    $consumption_data_for_apps[$app_index]['appColor'] =  [
      "label" => "",
      "show" => TRUE,
      "value" => $this->configBlock["defaultSettingsApps"]["color"]["value"],
      "formattedValue" => $this->configBlock["defaultSettingsApps"]["color"]["value"],
    ];

    $consumption_data_for_apps[$app_index]["dataUsed"] = [
      "label" => "",
      "show" => TRUE,
      "value" => $used_data,
      "formattedValue" => $this->getValueUnitsMeasure($used_data, TRUE)['formattedValue'],
    ];

    $consumption_data_for_apps[$app_index]["percentageDataUsed"] = [
      "label" => "",
      "show" => (bool)$this->configBlock["fields"]["percentage_data_used"]["show"],
      "value" => $this->getUsedDataPercentage($used_data, $this->app_consumption_list->totalVolumeBytes)['value'],
      "formattedValue" => $this->getUsedDataPercentage($used_data, $this->app_consumption_list->totalVolumeBytes)['formattedValue'],
    ];

    $consumption_list = $this->makeConsumptionList($app_name, TRUE);

    $consumption_data_for_apps[$app_index]["appDataset"] = [
      "label" => "",
      "show" => TRUE,
      "value" => $consumption_list,
      "formattedValue" => $consumption_list
    ];
  }

  /**
   * Returns information on data consumed per day
   *
   * @return object
   */
  public function getConsumptionPerDay() {
    $date_list = $this->billingService->listOfDatesToFilterConsumption(self::DAYS_FOR_FILTERING, self::DATE_FORMAT);

    $last_position_dates = (count($date_list) - 1);

    return $this->billingService->retrieveUsageDataHourlyAppByRange($this->msisdn,$date_list[$last_position_dates]['formattedValue'],$date_list[0]["formattedValue"]);
  }

  /**
   * Returns information on data consumed per hour
   *
   * @return object
   */
  public function getConsumptionPerHour() {
    $date_list = $this->billingService->listOfDatesToFilterConsumption(self::DAYS_FOR_FILTERING, self::DATE_FORMAT);

    $period = $this->query_params['period'];

    $total_dates_list = count($date_list) - 1;

    $last_position_dates = (empty($period) || $period > $total_dates_list) ? 0 : $period;

    return $this->billingService->retrieveUsageDataHourlyAppByRange($this->msisdn, $date_list[$last_position_dates]["formattedValue"],$date_list[0]["formattedValue"]);
  }

  /**
   * Format the reponse with the block configuarion values (In action section).
   *
   * @return array
   *   Return fields as array of objects.
   */
  public function getActions() {
    $actions = $this->configBlock['actions'];
    foreach ($actions as $name => $action) {
      if ($name == 'showMore' || $name == 'showLess'){
        $total_items_batches = $this->configBlock["fields"]["total_items_batches"]["label"];
        $actions[$name]['show'] = ($total_items_batches <= $this->total_apps_list) ? TRUE : FALSE;
      }
      else{
        $actions[$name]['show'] = (bool) $action['show'];
      }
      unset($actions[$name]["showConditional"]);
    }
    return $actions;
  }

  /**
   * Returns filters to get data
   *
   * @return array
   */
  public function makeFilters() {
    $filters = [];

    $filters['group'] = [
      "label"   =>  $this->configBlock["fields"]["group"]["label"],
      "show"    =>  (bool)$this->configBlock["fields"]["group"]["show"],
      "options" => [
        [
          "show"            => (bool)$this->configBlock["fields"]["consumption_per_day"]["show"],
          "value"           =>  0,
          "formattedValue"  =>  $this->configBlock["fields"]["consumption_per_day"]["label"],
        ],
        [
          "show"            =>  (bool)$this->configBlock["fields"]["consumption_per_hours"]["show"],
          "value"           =>  1,
          "formattedValue"  =>  $this->configBlock["fields"]["consumption_per_hours"]["label"],
        ],
      ]
    ];

    $filters['period'] = [
        "label"   =>  $this->configBlock["fields"]["period"]["label"],
        "show"    =>  (bool)$this->configBlock["fields"]["period"]["show"],
        "options" => $this->makeFilterOptions(),
      ];

    return $filters;
  }

  /**
   * Return Filter options
   *
   * @return array
   */
  public function makeFilterOptions() {
    $options = [];

    $options = $this->billingService->listOfDatesToFilterConsumption(self::DAYS_FOR_FILTERING, $this->configBlock["fields"]["date"]["format"], TRUE);

    array_push($options, [
      "optionValue"    => 0,
      "value"           => 0,
      "formattedValue"  => $this->configBlock["fields"]["days"]["label"],
    ]);

      usort($options, function ($a, $b) {
        return $a['optionValue'] > $b['optionValue'];
      });

    return $options;
  }

  /**
   * Returns value formatted in units of measure
   *
   * @param float $used_data
   * @param bool $check_unit_of_measure
   * @return array
   */
  public function getValueUnitsMeasure($used_data, $check_unit_of_measure = FALSE){

    $unit_measure = [];

    $unit_measure_label = $this->configBlock["fields"]["unit_measure"]["label"];

    $method = 'getFormattedValueIn' . $unit_measure_label;

    if (method_exists($this, $method)) {
      $unit_measure = $this->{$method}($used_data);
    }

    if ($check_unit_of_measure && $unit_measure_label == 'MB' && $unit_measure['value'] >= self::KIBI){
      $unit_measure  = $this->getFormattedValueInGB($used_data);
    }

    return $unit_measure;
  }

  /**
   * Return data in KB
   *
   * @param float $used_data
   * @param bool $unit_measure
   * @return array
   */
  public function getFormattedValueInKB($used_data) {
    $quotient = floatval($used_data / self::KB);
    $temp = number_format((float)$quotient, 1, '.', '');
    return ["value" => $temp, "formattedValue" => $temp . ' KB'];
  }

  /**
   * Return data in MB
   *
   * @param float $used_data
   * @param bool $unit_measure
   * @return array
   */
  public function getFormattedValueInMB($used_data) {
    $quotient = floatval($used_data / self::MB);
    $temp = number_format((float)$quotient, 2, '.', '');
    return ["value" => $temp, "formattedValue" => $temp . ' MB'];
  }

  /**
   * Return data in GB
   *
   * @param float $used_data
   * @param bool $unit_measure
   * @return array
   */
  public function getFormattedValueInGB($used_data) {
    $quotient = floatval($used_data / self::GB);
    $temp = number_format((float)$quotient, 2, '.', '');
    return ["value" => $temp, "formattedValue" => $temp . ' GB'];
  }

  /**
   * Returns percentage of discounts consumed per application
   *
   * @param float $consumption
   * @param float $used
   * @return array
   */
  public function getUsedDataPercentage($consumption, $used) {
    $formatted_consumed_data = $this->getValueUnitsMeasure($consumption)['value'] * 100;
    $total_formatted_data = $this->getValueUnitsMeasure($used)['value'];
    $percent = ($formatted_consumed_data / $total_formatted_data);
    $percent_round = number_format((float)$percent, 2, '.', '');
    return ['value' => $percent_round, 'formattedValue' => $percent_round . ' %'];
  }

  /**
   * Returns configuration data
   *
   * @param array $data
   * @return array
   */
  public function getDataConfig($data) {
    $data_config = [];
    if (!isset($data['noData'])) {
      $image_path_url = PublicStream::baseUrl() . '/' . $this->billingService::DIRECTORY_IMAGES_MOBILE;
      $data_config['imagePath'] = ['url' => $image_path_url];
    }
    return $data_config;
  }
}
