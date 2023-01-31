<?php

namespace Drupal\oneapp_convergent_upgrade_plan_bo\Plugin\Block\v2_0;

use Drupal\Core\Form\FormStateInterface;
use Drupal\oneapp_convergent_upgrade_plan\Plugin\Block\v2_0\UpgradeBlock;

class UpgradeBoBlock extends UpgradeBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    $config_block = parent::defaultConfiguration();

    $config_block['schedulingConfig']['delay_for_get_scheduled_visits'] =
      $config_block['schedulingConfig']['delay_for_get_scheduled_visits']
      ?? 10;
    $config_block['recommendedOffers']['upgradeProcessMessage']['message'] =
      $config_block['recommendedOffers']['upgradeProcessMessage']['message']
      ?? 'Esto puedo tardar algunos segundos|Por favor espera que termine el proceso';

    $group_fields = 'emailSetting';

    $config_block[$group_fields]['eml_success']['#title'] = $config_block[$group_fields]['eml_success']['#title'] ?? '';
    $config_block[$group_fields]['eml_success_wo']['#title'] = $config_block[$group_fields]['eml_success_wo']['#title'] ?? '';
    $config_block[$group_fields]['eml_unsuccess_wo']['#title'] = $config_block[$group_fields]['eml_unsuccess_wo']['#title'] ?? '';
    $config_block[$group_fields]['eml_success_sch']['#title'] = $config_block[$group_fields]['eml_success_sch']['#title'] ?? '';
    $config_block[$group_fields]['eml_unsuccess_sch']['#title'] = $config_block[$group_fields]['eml_unsuccess_sch']['#title'] ?? '';
    $config_block[$group_fields]['config']['enableEmailSend'] = 1;

    $group_fields = 'zendesk';
    $config_block[$group_fields]['subj_update_error'] = $config_block[$group_fields]['subj_update_error']
      ?? 'Error al actualizar el plan de Upgrade Plan';
    $config_block[$group_fields]['subj_beneficiary_line'] = $config_block[$group_fields]['subj_beneficiary_line']
      ?? 'Error al activar línea beneficiaria para el UpgradePlan';
    $config_block[$group_fields]['subj_wo_not_found'] = $config_block[$group_fields]['subj_wo_not_found']
      ?? 'No se encontró Orden de trabajo para el UpgradePlan';
    $config_block[$group_fields]['subj_no_dates_available'] = $config_block[$group_fields]['subj_no_dates_available']
      ?? 'No se encontró agenda disponible para el UpgradePlan';
    $config_block[$group_fields]['subj_reschedule_error'] = $config_block[$group_fields]['subj_reschedule_error']
      ?? 'Ocurrió un error al reagendar el Upgrade';

    return $config_block;
  }

  /**
   * {@inheritdoc}
   */
  public function adfBlockSubmit($form, FormStateInterface $form_state) {
    parent::adfBlockSubmit($form, $form_state);
    $this->configuration['async_config'] = $form_state->getValue('async_config');
    $this->configuration['test_config'] = $form_state->getValue('test_config');
  }

  /**
   * {@inheritdoc}
   */
  public function adfBlockForm($form, FormStateInterface $form_state) {

    $form = parent::adfBlockForm($form, $form_state);

    $upgrade_utils_service =& $this->upgradePlanUtils;

    // Upgrade config
    $group_fields = 'recommendedOffers';
    $current_config = $upgrade_utils_service->getFieldConfigValue($this->configuration[$group_fields], NULL, []);

    $message = 'Mensaje para mostrar durante el proceso de upgrade.';
    $message .= ' <strong> Use el caracter pipe (|) para separar los mensajes en la respuesta de la API<strong>';
    $form[$group_fields]['upgradeProcessMessage'] = [
      '#type' => 'details',
      '#title' => t('Upgrade Process Message'),
      'message' => [
        '#type' => 'textarea',
        '#title' => t('Message'),
        '#description' => t($message),
        '#default_value' => $current_config['upgradeProcessMessage']['message']
      ]
    ];

    // Tickets zendesk config
    $group_fields = 'zendesk';
    $config_actions = $upgrade_utils_service->getFieldConfigValue($this->configuration[$group_fields], NULL, []);
    $form_zendesk = array_slice($form[$group_fields], 0, 3);
    $form_zendesk['enableZendesk'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Habilitar la generación de tickets Zendesk"),
      '#default_value' => $upgrade_utils_service->getFieldConfigValue($config_actions['enableZendesk'], NULL, 1),
      '#description' => $this->t('Si esta activado se generan los tickets Zendesk'),
    ];
    $form_zendesk['subj_update_error'] = [
      '#type' => 'textfield',
      '#title' => 'Asunto: Error al actualizar el plan',
      '#default_value' => $upgrade_utils_service->getFieldConfigValue($config_actions['subj_update_error'], NULL, ''),
      '#description' => '',
    ];
    $form_zendesk['subj_beneficiary_line'] = [
      '#type' => 'textfield',
      '#title' => 'Asunto: Error al actualizar la línea beneficiaria',
      '#default_value' => $upgrade_utils_service->getFieldConfigValue($config_actions['subj_beneficiary_line'], NULL, ''),
      '#description' => '',
    ];
    $form_zendesk['subj_wo_not_found'] = [
      '#type' => 'textfield',
      '#title' => 'Asunto: Orden de trabajo no encontrada',
      '#default_value' => $upgrade_utils_service->getFieldConfigValue($config_actions['subj_wo_not_found'], NULL, ''),
      '#description' => '',
    ];
    $form_zendesk['subj_no_dates_available'] = [
      '#type' => 'textfield',
      '#title' => 'Asunto: No hay agenda disponible',
      '#default_value' => $upgrade_utils_service->getFieldConfigValue($config_actions['subj_no_dates_available'], NULL, ''),
      '#description' => '',
    ];
    $form_zendesk['subj_reschedule_error'] = [
      '#type' => 'textfield',
      '#title' => 'Asunto: Error al reagendar',
      '#default_value' => $upgrade_utils_service->getFieldConfigValue($config_actions['subj_reschedule_error'], NULL, ''),
      '#description' => '',
    ];
    $form[$group_fields] = $form_zendesk + array_slice($form[$group_fields], 4);

    // Alter upgrade plan confirmation
    $group_fields = 'confirmationUpgradePlan';
    $config_actions = $upgrade_utils_service->getFieldConfigValue(
      $this->configuration[$group_fields], NULL, []);

    $form[$group_fields]['cardConfirmation']['fields']['title']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Título de activación exitosa'),
      '#default_value' => $upgrade_utils_service->
        getFieldConfigValue($config_actions['cardConfirmation']['fields']['title']['label'], NULL, ''),
      '#description' => $this->t('¡Servicio activado con exito!'),
    ];
    $form[$group_fields]['cardConfirmation']['fields']['desc']['label']['#title'] = "Descripción de activación exitosa";


    $form[$group_fields]['cardConfirmation']['fields']['titleWO']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Título de activación exitosa con Orden de Trabajo'),
      '#default_value' => $upgrade_utils_service->
        getFieldConfigValue($config_actions['cardConfirmation']['fields']['titleWO']['label'], NULL, '¡Servicio activado con exito!'),
      '#description' => $this->t('¡Servicio activado con exito, le contactaremos para la visita!'),
    ];
    $form[$group_fields]['cardConfirmation']['fields']['titleWO']['show'] = [
      '#type' => 'checkbox',
      '#default_value' => $upgrade_utils_service->
        getFieldConfigValue($config_actions['cardConfirmation']['fields']['titleWO']['show'], NULL, 1),
    ];
    $form[$group_fields]['cardConfirmation']['fields']['descWO']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Descripción de activación exitosa con Orden de Trabajo'),
      '#default_value' => $upgrade_utils_service->
        getFieldConfigValue(
          $config_actions['cardConfirmation']['fields']['descWO']['label'],
          NULL,
          'Su plan ha sido actualizado éxitosamente, en breve recibiras un correo con la información de la visita técnica'),
      '#description' => $this->t('En breve le llegará la cita para la visita técnica'),
    ];

    // alter emailSettings form
    $group_fields = 'emailSetting';
    $eml_current_config = $upgrade_utils_service->getFieldConfigValue($this->configuration[$group_fields], NULL, []);

    $form[$group_fields]['config']['enableEmailSend'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Habilitar el envío de correos"),
      '#default_value' => $upgrade_utils_service->
        getFieldConfigValue($this->configuration[$group_fields]['config']['enableEmailSend'], NULL, 0),
      '#description' => $this->t('Si esta activado se hace el envío de correos'),
    ];

    $tokens_description =  'Tokens: <br> [oneapp_upgrade:userName] - Nombre completo del cliente';
    $tokens_description .= '<br> [oneapp_upgrade:currentPlan] - Nombre del plan actual (anterior)';
    $tokens_description .= '<br> [oneapp_upgrade:newPlan] - Nombre del nuevo plan';
    $tokens_description .= '<br> [oneapp_upgrade:date] - Fecha de inicio del nuevo plan';
    $tokens_description .= '<br> [oneapp_upgrade:price] - Precio del nuevo plan';
    $tokens_descriptions['eml_success'] = $tokens_description;
    $tokens_descriptions['eml_success_wo'] = $tokens_description;
    $tokens_descriptions['eml_unsuccess_wo'] = $tokens_description;

    $tokens_description =  'Tokens: <br> [oneapp_upgrade:userName] - Nombre completo del cliente';
    $tokens_description .= '<br> [oneapp_upgrade:currentPlan] - Nombre del plan actual';
    $tokens_description .= '<br> [oneapp_upgrade:newPlan] - Nombre del plan seleccionado (al que se intentaba actualizar)';
    $tokens_description .= '<br> [oneapp_upgrade:price] - Precio del nuevo plan';
    $tokens_descriptions['eml_unsuccess'] = $tokens_description;

    $tokens_description =  'Tokens: <br> [oneapp_upgrade:userName] - Nombre completo del cliente';
    $tokens_description .= '<br> [oneapp_upgrade:currentPlan] - Nombre del plan actual (anterior)';
    $tokens_description .= '<br> [oneapp_upgrade:newPlan] - Nombre del nuevo plan';
    $tokens_description .= '<br> [oneapp_upgrade:dateStart] - Fecha de inicio de rango de la visita';
    $tokens_description .= '<br> [oneapp_upgrade:dateEnd] - Fecha de finalización de rango de la visita';
    $tokens_descriptions['eml_success_sch'] = $tokens_description;

    $tokens_description =  'Tokens: <br> [oneapp_upgrade:userName] - Nombre completo del cliente';
    $tokens_description .= '<br> [oneapp_upgrade:currentPlan] - Nombre del plan actual (anterior)';
    $tokens_description .= '<br> [oneapp_upgrade:newPlan] - Nombre del nuevo plan';
    $tokens_descriptions['eml_unsuccess_sch'] = $tokens_description;
    // Success upgrade without WO - eml_success
    $form[$group_fields]['eml_success'] = $form[$group_fields]['single'];
    unset($form[$group_fields]['single']);
    $form[$group_fields]['eml_success']['#title'] = $this->t('Correo de upgrade satisfactorio');

    // Unsuccessful upgrade - eml_unsuccess
    $form[$group_fields]['eml_unsuccess'] = $form[$group_fields]['email_error'];
    unset($form[$group_fields]['email_error']);
    $form[$group_fields]['eml_unsuccess']['#title'] = $this->t('Correo de upgrade no satisfactorio');

    // Success upgrade with WO - eml_success_wo
    $form[$group_fields]['eml_success_wo'] = $form[$group_fields]['single_promo'];
    unset($form[$group_fields]['single_promo']);
    $form[$group_fields]['eml_success_wo']['#title'] = $this->t('Correo de upgrade satisfactorio con Orden de Trabajo');

    // Unsuccess upgrade with WO - eml_unsuccess_wo
    $form[$group_fields]['eml_unsuccess_wo'] = $form[$group_fields]['eml_success_wo'];
    $form[$group_fields]['eml_unsuccess_wo']['#title'] = $this->t('Correo de error al generar Orden de Trabajo');


    // Successful scheduling - eml_success_sch
    $form[$group_fields]['eml_success_sch'] = $form[$group_fields]['eml_success_wo'];
    $form[$group_fields]['eml_success_sch']['#title'] = $this->t('Correo de agendamiento de OT satisfactorio');

    // Unsuccessful scheduling - eml_unsuccess_sch
    $form[$group_fields]['eml_unsuccess_sch'] = $form[$group_fields]['eml_success_wo'];
    $form[$group_fields]['eml_unsuccess_sch']['#title'] = $this->t('Correo de agendamiento de OT no satisfactorio');

    foreach (['eml_success', 'eml_unsuccess', 'eml_success_wo', 'eml_unsuccess_wo', 'eml_success_sch', 'eml_unsuccess_sch'] as $field_grp) {
      $form[$group_fields][$field_grp]['subject']['#default_value'] = isset($eml_current_config[$field_grp]['subject'])
                                                                        ? $eml_current_config[$field_grp]['subject']
                                                                        : '';
      $form[$group_fields][$field_grp]['body']['#default_value'] = isset($eml_current_config[$field_grp]['body']['value'])
                                                                        ? $eml_current_config[$field_grp]['body']['value']
                                                                        : '';
      $form[$group_fields][$field_grp]['#description'] = $tokens_descriptions[$field_grp];
    }

    // async configuration form
    $group_fields = 'async_config';
    $config_actions = $upgrade_utils_service->getFieldConfigValue($this->configuration[$group_fields], NULL, []);
    $form[$group_fields] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración de procesamiento asincrono'),
      '#open' => FALSE,
    ];

    $sub_group_fields = 'cron';
    $form[$group_fields][$sub_group_fields] = [
      '#type' => 'details',
      '#title' => $this->t('CRON de drupal'),
    ];
    $form[$group_fields][$sub_group_fields]['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Habilitar el procesamiento asincrono de upgrades pendientes de orden de trabajo"),
      '#default_value' => $upgrade_utils_service->getFieldConfigValue($config_actions[$sub_group_fields]['enable'], NULL, 1),
      '#description' => $this->t('Si esta activado se procesaran los upgrades pendientes de orden de trabajo'),
    ];
    $form[$group_fields][$sub_group_fields]['max_retries'] = [
      '#type' => 'textfield',
      '#title' => 'Cantidad de reintentos para obtener una orden de trabajo',
      '#default_value' => $upgrade_utils_service->getFieldConfigValue($config_actions[$sub_group_fields]['max_retries'], NULL, 3),
      '#description' =>
        'Cantidad de veces que se intenta obtener la orden de trabajo antes de marcarla como no encontrada.  Valor recomendado 3',
    ];
    $form[$group_fields][$sub_group_fields]['pending_upgrade_process_limit'] = [
      '#type' => 'textfield',
      '#title' => 'Upgrades pendientes a procesar en cada ciclo',
      '#default_value' => $upgrade_utils_service
        ->getFieldConfigValue($config_actions[$sub_group_fields]['pending_upgrade_process_limit'], NULL, 100),
      '#description' => 'Cantidad upgrades que se procesaran en cada llamado asincrono',
    ];

    $sub_group_fields = 'queue';
    $form[$group_fields][$sub_group_fields] = [
      '#type' => 'details',
      '#title' => $this->t('Queue (cola)'),
    ];
    $form[$group_fields][$sub_group_fields]['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Habilitar el consumo de la cola"),
      '#default_value' => $upgrade_utils_service->getFieldConfigValue($config_actions[$sub_group_fields]['enable'], NULL, 1),
      '#description' => $this->t('Si esta activado se procesaran los upgrades pendientes de orden de trabajo que están encolados,
      si está desactivado la cola estará pausada'),
    ];

    // test configuration form
    $group_fields = 'test_config';
    $config_actions = $upgrade_utils_service->getFieldConfigValue($this->configuration[$group_fields], NULL, []);
    $form[$group_fields] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración para realización de pruebas'),
      '#open' => FALSE,
    ];
    $form[$group_fields]['emulate_upgrade'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Emular la actualización del plan"),
      '#default_value' => $upgrade_utils_service->getFieldConfigValue($config_actions['emulate_upgrade'], NULL, 0),
      '#description' => $this->t('No consumirá la API que actualizar el plan (No actualiza el plan en ApiGee'),
    ];
    $form[$group_fields]['emulate_upgrade_exception'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Emular error al actualizar el plan"),
      '#default_value' => $upgrade_utils_service->getFieldConfigValue($config_actions['emulate_upgrade_exception'], NULL, 0),
      '#description' => $this->t('Se emula un error al momento de consumir la API que actualiza el plan'),
    ];
    $form[$group_fields]['emulate_beneficiary_line_failure'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Emular error al asignar linea beneficiaria"),
      '#default_value' => $upgrade_utils_service->getFieldConfigValue($config_actions['emulate_beneficiary_line_failure'], NULL, 0),
      '#description' => $this->t('Se emula un error al momento de consumir la API que asigna la línea beneficiaria'),
    ];

    return $form;
  }

}
