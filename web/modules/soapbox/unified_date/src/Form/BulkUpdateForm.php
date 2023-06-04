<?php

namespace Drupal\unified_date\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure file system settings for this site.
 */
class BulkUpdateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unified_date_bulk_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = [];

    $form['#update_callbacks'] = [];

    $form['update'] = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Select the node types of paths for which to set the unified date'),
      '#options'       => [],
      '#default_value' => [],
    ];

    $config              = $this->config('unified_date.settings');
    $entity_type_manager = \Drupal::entityTypeManager();

    $node_types = $entity_type_manager
      ->getStorage('node_type')
      ->loadMultiple();
    if ($node_types) {
      $options = [];

      // Get all node types.
      foreach ($node_types as $node_type => $node_definition) {
        $options[$node_type] = $node_definition->label();
      }

      $form['node_types'] = [
        '#type'          => 'checkboxes',
        '#options'       => $options,
        '#default_value' => array_keys($options),
        '#title'         => $this->t('Node types'),
        '#required'      => TRUE,
      ];
    }

    $form['actions']['#type']  = 'actions';
    $form['actions']['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Update Unified Dates'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValues();
    $batch    = [
      'title'      => $this->t('Bulk updating Unified Dates'),
      'operations' => [
        [
          'Drupal\unified_date\Form\BulkUpdateForm::batchStart',
          [
            $settings,
          ],
        ],
        [
          'Drupal\unified_date\Form\BulkUpdateForm::batchProcess',
          [
            $settings,
          ],
        ],
      ],
      'finished'   => 'Drupal\unified_date\Form\BulkUpdateForm::batchFinished',
    ];
    batch_set($batch);
  }

  /**
   * Batch callback; initialize the number of updated unified dates.
   */
  public static function batchStart(&$context) {
    $context['results']['updates'] = 0;
  }

  /**
   * Batch processing callback.
   */
  public static function batchProcess($settings, &$context) {
    /** @var \Drupal\pathauto\AliasTypeBatchUpdateInterface $alias_type */
    $unified_date_manager = \Drupal::service('unified_date.batch_processor');
    $unified_date_manager->processBatch($settings, $context);
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      if ($results['updates']) {
        \Drupal::service('messenger')->addMessage(\Drupal::translation()
          ->formatPlural($results['updates'], 'Generated 1 Unified Date.',
            'Generated @count Unified Dates.'));
      }
      else {
        \Drupal::service('messenger')
          ->addMessage(t('No Unified Dates to generate.'));
      }
    }
    else {
      $error_operation = reset($operations);
      \Drupal::service('messenger')
        ->addMessage(t('An error occurred while processing the Unified Dates with arguments : @args'),
          [
            '@operation' => $error_operation[0],
            '@args'      => print_r($error_operation[0]),
          ]);
    }
  }

}
