<?php

namespace Drupal\soapbox_flexible_publishing\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Flexible publishing node type form alter.
 */
class FlexiblePublishingNodeTypeFormAlter {

  /**
   * Alter the node type edit form.
   *
   * @param array $form
   *   The node type edit form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function formAlter(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\node\NodeTypeInterface $type */
    $type = $form_state->getFormObject()->getEntity();
    $form['templates'] = [
      '#type' => 'details',
      '#title' => t('Template settings'),
      '#group' => 'additional_settings',
    ];
    $form['templates']['use_templates'] = [
      '#type' => 'checkbox',
      '#title' => t('Use multiple templates for this content type'),
      '#default_value' => $type->getThirdPartySetting('soapbox_flexible_publishing', 'use_templates', FALSE),
      '#description' => t('Allows form modes to be used as separate templates.'),
    ];

    /** @var \Drupal\form_mode_manager\FormModeManager $form_mode_manager */
    $form_mode_manager = \Drupal::service('form_mode.manager');
    $form_modes = $form_mode_manager->getFormModesIdByEntity('node');
    if ($form_modes) {
      $form['templates']['disable_form_mode_contextual_links'] = [
        '#type' => 'checkbox',
        '#title' => t('Disable form mode contextual links'),
        '#default_value' => $type->getThirdPartySetting('soapbox_flexible_publishing', 'disable_form_mode_contextual_links', FALSE),
        '#description' => t('Disable the "Edit as (form mode name)" contextual links.'),
        '#states' => [
          'visible' => [
            ':input[name="use_templates"]' => ['checked' => TRUE],
          ],
        ],
      ];

      // Build options array with labels for the form modes.
      $form_modes = array_combine($form_modes, $form_modes);
      /** @var \Drupal\Core\Entity\EntityDisplayRepository $entity_display */
      $entity_display = \Drupal::service('entity_display.repository');
      $available_form_modes = $entity_display->getFormModeOptionsByBundle('node', $type->id());
      $form_modes = array_intersect_key($available_form_modes, $form_modes);

      $form['templates']['form_modes_as_templates'] = [
        '#type' => 'checkboxes',
        '#title' => t('Form modes to use as templates'),
        '#default_value' => $type->getThirdPartySetting('soapbox_flexible_publishing', 'form_modes_as_templates', []),
        '#options' => $form_modes,
        '#description' => t('The form modes which should result in a separate template.'),
        '#states' => [
          'visible' => [
            ':input[name="use_templates"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }
    else {
      $form['templates']['form_modes_as_templates'] = [
        '#plain_text' => t('No form modes have yet been set up for this content type.'),
      ];
    }

    $form['#entity_builders'][] = 'soapbox_flexible_publishing_form_node_type_form_builder';
  }

  /**
   * Save the additional fields from the node type edit form.
   *
   * @param string $entity_type
   *   The entity type machine name.
   * @param \Drupal\node\NodeTypeInterface $type
   *   The node type.
   * @param array $form
   *   The f array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function formAlterSave($entity_type, NodeTypeInterface $type, array &$form, FormStateInterface $form_state) {
    $form_modes = array_filter($form_state->getValue('form_modes_as_templates', []));
    if ($form_modes) {
      $type->setThirdPartySetting('soapbox_flexible_publishing', 'use_templates', $form_state->getValue('use_templates', FALSE));
      $type->setThirdPartySetting('soapbox_flexible_publishing', 'form_modes_as_templates', $form_modes);
      $type->setThirdPartySetting('soapbox_flexible_publishing', 'disable_form_mode_contextual_links', $form_state->getValue('disable_form_mode_contextual_links', FALSE));
    }
    elseif ($form_state->getValue('use_templates')) {
      \Drupal::messenger()->addWarning(t('"Use Templates" was not set as no form modes were selected.'));
    }
  }

}
