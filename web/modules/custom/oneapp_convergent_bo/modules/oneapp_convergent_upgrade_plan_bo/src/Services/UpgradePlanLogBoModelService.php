<?php


namespace Drupal\oneapp_convergent_upgrade_plan_bo\Services;

use Drupal\Core\Database\Statement;
use Drupal\Core\Database\Query\Select;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Query\SelectExtender;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\Database\Query\PagerSelectExtender;

class UpgradePlanLogBoModelService {

  // Order status constants
  const ORDER_STATUS_NOT_REQUIRED = 'No requerida';
  const ORDER_STATUS_NOT_GENERATED = 'No generada';
  const ORDER_STATUS_NOT_SCHEDULED = 'No agendada';
  const ORDER_STATUS_SCHEDULED = 'Agendada';

  // Upgrade status constants
  const UPGRADE_STATUS_PENDING = 'No realizado';
  const UPGRADE_STATUS_DONE = 'Realizado';

  protected static $tableName = 'oneapp_convergent_upgrade_plan_bo_log';
  protected static $tableAlias = 'log';
  protected static $tableSchema = [
    'description' => 'Stores upgrade plan logs',
    'fields' => [
      'id' => [
        'type' => 'serial',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'size' => 'medium',
      ],
      'client_name' => [
        'type' => 'varchar',
        'length' => 100,
        'description' => 'Full name of the client',
      ],
      'client_email' => [
        'type' => 'varchar',
        'length' => 100,
        'description' => 'Client email for notifications',
      ],
      'service_number' => [
        'type' => 'varchar',
        'length' => 100,
        'description' => 'Service line number',
      ],
      'id_plan' => [
        'type' => 'varchar',
        'length' => 100,
        'description' => 'Identificador del plan',
      ],
      'name_plan' => [
        'type' => 'varchar',
        'length' => 100,
        'description' => 'Nombre del plan',
      ],
      'data' => [
        'type' => 'text',
        'size' => 'normal',
        'description' => '',
      ],
      'contract_id' => [
        'type' => 'varchar',
        'length' => 25,
        'description' => '',
      ],
      'billing_account' => [
        'type' => 'varchar',
        'length' => 25,
        'description' => '',
      ],
      'order_id' => [
        'type' => 'varchar',
        'length' => 25,
        'description' => 'Identificador de orden de trabajo/visita',
      ],
      'order_status' => [
        'type' => 'varchar',
        'length' => 100,
        'description' => 'Estado de la orden de trabajo/visita',
      ],
      'ticket_zendesk' => [
        'type' => 'varchar',
        'length' => 100,
        'description' => '',
      ],
      'id_ticket_zendesk' => [
        'type' => 'varchar',
        'length' => 100,
        'description' => '',
      ],
      'scheduling_attempts' => [
        'type' => 'int',
        'not null' => FALSE,
        'description' => 'Intentos de agendamiento de orden de trabajo/visita',
      ],
      'date_visit' => [
        'type' => 'varchar',
        'length' => 19,
        'mysql_type' => 'datetime',
        'description' => 'Fecha de la orden de trabajo/visita',
      ],
      'upgrade_status' => [
        'type' => 'varchar',
        'length' => 100,
        'description' => 'Estado del proceso de upgrade',
      ],
      'date' => [
        'type' => 'varchar',
        'length' => 19,
        'mysql_type' => 'datetime',
        'description' => 'Fecha de transacción',
      ],
      'business_unit' => [
        'type' => 'varchar',
        'length' => 10,
        'description' => 'Unidad de negocio (HOME, MOBILE)',
      ],
      'client_adf_name' => [
        'type' => 'varchar',
        'length' => 100,
        'description' => '',
      ],
      'document_number' => [
        'type' => 'varchar',
        'length' => 25,
        'description' => '',
      ],
      'current_plan_name' => [
        'type' => 'varchar',
        'length' => 50,
        'description' => '',
      ],
    ],
    'primary key' => ['id'],
    'indexes' => [],
    'foreign keys' => [],
  ];

  public static function createTable() {
    /** @var \Drupal\Core\Database\Driver\mysql\Connection $db_connection */
    $db_connection = \Drupal::database();

    /** @var \Drupal\Core\Database\Driver\mysql\Schema $schema */
    $schema = $db_connection->schema();

    if ($schema->tableExists(self::$tableName)) {
      foreach (self::$tableSchema['fields'] as $field => $def) {
        if (!$schema->fieldExists(self::$tableName, $field)) {
          $schema->addField(self::$tableName, $field, $def);
        }
      }
    } else {
      $schema->createTable(self::$tableName, self::$tableSchema);
    }
  }

  /**
   * @param array $filter
   * @param int $limit
   * @return array
   */
  public static function getTableReport($params = [], $limit = 20, $sort_by_headers = TRUE) {

    $headers = self::getTableReportHeaders();

    /** @var Select $query */
    $query = \Drupal::database()
      ->select(self::$tableName, self::$tableAlias)
      ->fields(self::$tableAlias, array_keys($headers));

    // Builds data from request params to build conditions
    if (!empty($conditions = self::getTableReportConditions($params))) {
      // Prepare orConditionGroup
      $or_group = $query->orConditionGroup();
      // Prepare andConditionGroup
      $and_group = $query->andConditionGroup();
      foreach ($conditions as $condition) {
        if ($condition['type'] == 'or_group') {
          $or_group->condition(self::$tableAlias . '.' . $condition['field'], $condition['value'], $condition['operator']);
        }
        if ($condition['type'] == 'and_group') {
          $and_group->condition(self::$tableAlias . '.' . $condition['field'], $condition['value'], $condition['operator']);
        }
      }
      if ($or_group->count()) {
        $query->condition($or_group);
      }
      if ($and_group->count()) {
        $query->condition($and_group);
      };
    }

    if (!empty($limit)) {
      /** @var SelectExtender $query */
      $query = $query->extend(PagerSelectExtender::class)->limit($limit);
    }

    if (!empty($sort_by_headers)) {
      /** @var SelectExtender $query */
      $query = $query->extend(TableSortExtender::class)->orderByHeader($headers);
    }

    /** @var Statement $stm */
    try {
      $stm = $query->execute();
    } catch (\Exception $e) {
      if ($e->getPrevious()) {
        if ($e->getPrevious()->getCode() == '42S02') {
          self::createTable();
          $stm = $query->execute();
        } else {
          throw $e;
        }
      } else {
        throw $e;
      }
    }

    $records = !empty($stm) ? $stm->fetchAll(\PDO::FETCH_NUM) : [];

    return array_values($records);
  }

  /**
   * @return array
   */
  public static function getTableReportHeaders() {
    return [
      'id' => ['data' => t('Id'), 'field' => 'id', 'sort' => 'DESC'],
      'client_name' => ['data' => t('Client'), 'field' => 'client_name'],
      'service_number' => ['data' => t('Service Number'), 'field' => 'service_number'],
      'id_plan' => ['data' => t('Id Plan'), 'field' => 'id_plan'],
      'name_plan' => ['data' => t('Name Plan'), 'field' => 'name_plan'],
      'data' => ['data' => t('Data'), 'field' => 'data'],
      'contract_id' => ['data' => t('Contract Id'), 'field' => 'contract_id'],
      'order_id' => ['data' => t('Order Id'), 'field' => 'order_id'],
      'order_status' => ['data' => t('Order Status'), 'field' => 'order_status'],
      'ticket_zendesk' => ['data' => t('Ticket Zendesk'), 'field' => 'ticket_zendesk'],
      'id_ticket_zendesk' => ['data' => t('Id Ticket Zendesk'), 'field' => 'id_ticket_zendesk'],
      'date_visit' => ['data' => t('Date Visit'), 'field' => 'date_visit'],
      'upgrade_status' => ['data' => t('Upgrade Status'), 'field' => 'upgrade_status'],
      'date' => ['data' => t('Date'), 'field' => 'date'],
      'business_unit' => ['data' => t('B. Unit'), 'field' => 'business_unit'],
    ];
  }

  /**
   * Builds data from request params to build conditions
   * @param $params
   * @return array
   */
  public static function getTableReportConditions($params) {
    if (!empty($params['business_unit'])) {
      $conditions[] = ['type' => 'and_group', 'field' => 'business_unit', 'value' => $params['business_unit'], 'operator' => '='];
    }
    if (!empty($params['search'])) {
      $conditions[] = ['type' => 'or_group', 'field' => 'client_name', 'value' => "%{$params['search']}%", 'operator' => 'LIKE'];
      $conditions[] = ['type' => 'or_group', 'field' => 'service_number', 'value' => "%{$params['search']}%", 'operator' => 'LIKE'];
      $conditions[] = ['type' => 'or_group', 'field' => 'bundle_plan', 'value' => "%{$params['search']}%", 'operator' => 'LIKE'];
      $conditions[] = ['type' => 'or_group', 'field' => 'name_plan', 'value' => "%{$params['search']}%", 'operator' => 'LIKE'];
      $conditions[] = ['type' => 'or_group', 'field' => 'lead_id', 'value' => "%{$params['search']}%", 'operator' => 'LIKE'];
      $conditions[] = ['type' => 'or_group', 'field' => 'contract_id', 'value' => "%{$params['search']}%", 'operator' => 'LIKE'];
    }
    if (!empty($params['start_date'])) {
      $start_date = "{$params['start_date']} 00:00:00";
      $conditions[] = ['type' => 'and_group', 'field' => 'date', 'value' => $start_date, 'operator' => '>='];
    }
    if (!empty($params['end_date'])) {
      $end_date = "{$params['end_date']} 23:59:59";
      $conditions[] = ['type' => 'and_group', 'field' => 'date', 'value' => $end_date, 'operator' => '<='];
    }
    return $conditions ?? [];
  }

  /**
   * @param array $params
   * @return object[]
   */
  public static function getPendingScheduleUpdates($max_retries = 0, $max_records = 0) {

    $filters = [
      self::$tableAlias . '.order_status =\''. self::ORDER_STATUS_NOT_GENERATED . '\'',
      self::$tableAlias . '.upgrade_status =\''. self::UPGRADE_STATUS_DONE . '\''
    ];
    // ! Agregar la condición de los 35 segundos

    if ($max_records > 0) {
      $filters[] = self::$tableAlias . '.scheduling_attempts < ' . $max_retries;
    }

    /** @var Select $query */
    $query = \Drupal::database()
      ->select(self::$tableName, self::$tableAlias)
      ->fields(self::$tableAlias, [])
      ->where(implode(' AND ', $filters));

    if ($max_records > 0) {
      $query->range(0, $max_records);
    }

    /** @var \Drupal\Core\Database\StatementWrapper $stm */
    $stm = $query->execute();

    return $stm->fetchAll();
  }

  public static function getPendingScheduleUpdatebyId($id = null) {

    if (is_null($id)) {
      return null;
    }

    $filter = [
      self::$tableAlias . '.id =\''. $id . '\'',
    ];

    /** @var Select $query */
    $query = \Drupal::database()
      ->select(self::$tableName, self::$tableAlias)
      ->fields(self::$tableAlias, [])
      ->where(implode(' AND ', $filter));

    /** @var \Drupal\Core\Database\StatementWrapper $stm */
    $stm = $query->execute();

    return $stm->fetch();
  }

  public static function updateLog($fields) {
    if (!isset($fields['id'])) {
      return false;
    }

    $id = $fields['id'];
    unset($fields['id']);
    try {
      $return = \Drupal::database()
        ->update(self::$tableName)
        ->fields($fields)
        ->condition('id', $id)
        ->execute();
      return $return;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

}
