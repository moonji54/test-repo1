<?php

namespace Drupal\soapbox_views\Plugin\views\filter;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\Date;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Soapbox Date Filter.
 *
 * @ViewsFilter("soapbox_date")
 */
class SoapboxDate extends Date {

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|null
   */
  protected null|EntityFieldManagerInterface $entityFieldManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityFieldManager = $container->get('entity_field.manager');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    if (!empty($form['value']['type'])) {
      $form['value']['type']['#options']['mysql_date_format'] = $this->t('MySQL date format');
      $form['value']['mysql_date_format'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Format'),
        '#description' => $this->t('Please enter format using this <a href="@url" target="_blank">reference.</a>', [
          '@url' => 'https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html#function_date-format',
        ]),
        '#default_value' => $this->value['mysql_date_format'],
        '#states' => [
          'visible' => [
            ':input[name="options[value][type]"]' => [
              'value' => 'mysql_date_format',
            ],
          ],
          'required' => [
            ':input[name="options[value][type]"]' => [
              'value' => 'mysql_date_format',
            ],
          ],
        ],
      ];
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function valueSubmit($form, FormStateInterface $form_state) {
    parent::valueSubmit($form, $form_state);
    $this->options['value']['mysql_date_format'] = $form_state->getValue([
      'options',
      'value',
      'mysql_date_format',
    ]);
  }

  /**
   * Adds corresponding condition to query.
   *
   * @param string $field
   *   Table field name.
   * @param string $op
   *   Performed operation name.
   */
  protected function opAction(string $field, string $op): void {
    if (
      !empty($this->value['type']) &&
      $this->value['type'] == 'mysql_date_format' &&
      !empty($this->options['value']['mysql_date_format'])
    ) {
      // Get mysql date format configuration and input values.
      $format = $this->options['value']['mysql_date_format'];
      $value = $this->value['value'] ?? '';
      $min = ($this->value['min']) ?? '';
      $max = ($this->value['max']) ?? '';
      $operator = strtoupper($this->operator);

      // Check if field is in unix timestamp format.
      $is_unixtimestamp = FALSE;
      $storageDefinitions = $this->entityFieldManager->getFieldStorageDefinitions($this->definition['entity_type']);
      if (!empty($storageDefinitions[$this->definition['entity field']])) {
        $is_unixtimestamp = in_array($storageDefinitions[$this->definition['entity field']]->getType(), [
          'timestamp',
          'integer',
        ]);
      }

      // Add conditions to query.
      if ($op === 'opSimple') {
        $value_placeholder = $this->placeholder();
        if ($is_unixtimestamp) {
          $this->query->addWhereExpression(
            $this->options['group'],
            "FROM_UNIXTIME($field, '$format') $operator $value_placeholder",
            [$value_placeholder => $value]
          );
        }
        else {
          $this->query->addWhereExpression(
            $this->options['group'],
            "DATE_FORMAT($field, '$format') $operator $value_placeholder",
            [$value_placeholder => $value]
          );
        }
      }
      elseif ($op === 'opBetween') {
        $min_value_placeholder = $this->placeholder();
        $max_value_placeholder = $this->placeholder();
        if ($is_unixtimestamp) {
          $this->query->addWhereExpression(
            $this->options['group'],
            "FROM_UNIXTIME($field, '$format') $operator $min_value_placeholder AND $max_value_placeholder",
            [
              $min_value_placeholder => $min,
              $max_value_placeholder => $max,
            ]
          );
        }
        else {
          $this->query->addWhereExpression(
            $this->options['group'],
            "DATE_FORMAT($field, '$format') $operator $min_value_placeholder AND $max_value_placeholder",
            [
              $min_value_placeholder => $min,
              $max_value_placeholder => $max,
            ]
          );
        }
      }
      else {
        // Fallback to default behavior.
        parent::$op($field);
      }
    }
    else {
      // Fallback to default behavior.
      parent::$op($field);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {
    $this->opAction($field, __FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  protected function opBetween($field) {
    $this->opAction($field, __FUNCTION__);
  }

}
