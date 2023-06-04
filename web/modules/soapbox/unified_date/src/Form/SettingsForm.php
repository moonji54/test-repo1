<?php

namespace Drupal\unified_date\Form;

/**
 * @file
 * Contains \Drupal\unified_date\Form\SettingsForm.
 */

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure menu link weight settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The unified date manager.
   *
   * @var \Drupal\unified_date\UnifiedDateManager
   */
  protected $unifiedDateManager;

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->unifiedDateManager = $container->get('unified_date.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unified_date_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['unified_date.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('unified_date.settings');

    $node_types = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();
    if ($node_types) {
      foreach (array_keys($node_types) as $node_type) {
        $form[$node_type] = [
          '#type'     => 'select',
          '#options'  => $this->unifiedDateManager->getNodeDateFields($node_type),
          '#title'    => $this->t('Node type "@node_type"', [
            '@node_type' => $node_type,
          ]),
          '#default_value' => $config->get($node_type),
          '#description' => $this->t('All node types without date fields selected will used the "Created" date.'),
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config              = $this->config('unified_date.settings');
    $this->entityTypeManager = \Drupal::entityTypeManager();

    // Loop through node types and store field selection in config.
    $node_types = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();
    if ($node_types) {
      foreach (array_keys($node_types) as $node_type) {
        $config->set($node_type, $form_state->getValue($node_type));
      }
    }

    // Save the config.
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
