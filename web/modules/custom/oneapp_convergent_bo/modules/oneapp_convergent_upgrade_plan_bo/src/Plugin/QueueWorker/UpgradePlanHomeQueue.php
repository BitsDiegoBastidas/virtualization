<?php

namespace Drupal\oneapp_convergent_upgrade_plan_bo\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\oneapp_convergent_upgrade_plan_bo\Services\UpgradePlanLogBoModelService;

/**
* Upgrade Plan Bolivia Home Queue Worker.
*
* @QueueWorker(
*   id = "upgrade_plan_bo_home_queue",
*   title = @Translation("Upgrade Plan Bolivia Home Queue"),
*   cron = {"time" = 30}
* )
*/
final class UpgradePlanHomeQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {


  /**
  * The database connection.
  *
  * @var \Drupal\Core\Database\Connection
  */
  protected $database;


  /**
  * The entity type manager.
  *
  * @var \Drupal\Core\Entity\EntityTypeManagerInterface
  */
  protected $entityTypeManager;


  /**
  * Main constructor.
  *
  * @param array $configuration
  *   Configuration array.
  * @param mixed $plugin_id
  *   The plugin id.
  * @param mixed $plugin_definition
  *   The plugin definition.
  * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
  *   The entity type manager.
  * @param \Drupal\Core\Database\Connection $database
  *   The connection to the database.
  */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface
    $entity_type_manager,
    Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }


  /**
  * Processes an item in the queue.
  *
  * @param object $data
  *   The queue item data.
  *
  * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
  * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
  * @throws \Drupal\Core\Entity\EntityStorageException
  * @throws \Exception
  */
  public function processItem($data) {
    $adf_block_cofig_service = \Drupal::service("adf_block_config.config_block");
    $config_block = $adf_block_cofig_service->getDefaultConfigBlock('oneapp_convergent_upgrade_plan_v2_0_upgrade_block');

    if (isset($config_block['async_config']['queue'])) { // Chack if queue is paused
      if (!$config_block['async_config']['queue']['enable']) {
        throw new SuspendQueueException("The queue is paused");
      }
    }

    if (!$data) {
      return;
    }
    if (!isset($data->time)) {
      return;
    }

    if ($data->time > \Drupal::time()->getCurrentTime()) {
      throw new SuspendQueueException("Early queue, skipped");
    }

    $pending_upgrade = UpgradePlanLogBoModelService::getPendingScheduleUpdateById($data->upgrade_id);
    /** @var \Drupal\oneapp_convergent_upgrade_plan_bo\Services\v2_0\UpgradePlanSendRestLogicBo $service */
    $service = \Drupal::service('oneapp_convergent_upgrade_plan.v2_0.plan_send_rest_logic');
    $max_retries = isset($this->configBlock['async_config']['cron']['max_retries'])
      ? $this->configBlock['async_config']['cron']['max_retries']
      : 3;
    $pending_upgrade_retry = $service->processPendingUpgrade($pending_upgrade, $max_retries);

    if ($pending_upgrade_retry) { // if need to retry, add new item to be processed the queue in the future
      /** @var QueueFactory $queue_factory */
      $queue_factory = \Drupal::service('queue');
      /** @var QueueInterface $queue */
      $queue = $queue_factory->get('upgrade_plan_bo_home_queue');
      $data->time = \Drupal::time()->getCurrentTime() + 31;
      $queue->createItem($data);
    }
  }


  /**
  * Used to grab functionality from the container.
  *
  * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
  *   The container.
  * @param array $configuration
  *   Configuration array.
  * @param mixed $plugin_id
  *   The plugin id.
  * @param mixed $plugin_definition
  *   The plugin definition.
  *
  * @return static
  */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database'),
    );
  }

}
