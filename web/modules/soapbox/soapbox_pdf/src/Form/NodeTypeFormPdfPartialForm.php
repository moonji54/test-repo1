<?php

namespace Drupal\soapbox_pdf\Form;

use Drupal\media\Entity\Media;
use Drupal\Core\Form\FormStateInterface;

/**
 * Handle extending the Node Type form to provide PDF settings.
 */
class NodeTypeFormPdfPartialForm {

  /**
   * Implements form_alter.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @see soapbox_pdf.module
   */
  public static function formAlter(array &$form, FormStateInterface $form_state) {
    // Get current content type object.
    $build_info = $form_state->getBuildInfo();
    $type = $build_info['callback_object']->getEntity()->id();

    // Get array of enabled content types with enabled pdf generation.
    $content_types = \Drupal::config('soapbox_pdf.pdf_settings')->get('content_types') ?? [];

    // Generate array with media fields.
    $media_fields = [];
    $pdf_medias = self::getAvailableMediaBundles();
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $type);
    foreach ($fields as $field_name => $field) {
      // @todo Add a new condition to display only media with PDF type in the source field.
      if ($field->getType() === 'entity_reference'
        && $field->getSetting('target_type') === 'media') {
        $handler_settings = $field->getSetting('handler_settings');

        // Ignore non-PDF media fields.
        if (!empty($handler_settings['target_bundles'])
          && empty(array_intersect($handler_settings['target_bundles'], $pdf_medias))) {
          continue;
        }
        $media_fields[$field_name] = $field->getLabel();
      }
    }

    // Generate an array of annotated plugins for generating PDF.
    $plugins = [];
    $pdf_manager = \Drupal::service('plugin.manager.pdf_generator');
    foreach ($pdf_manager->getDefinitions() as $pid => $plugin) {
      $plugins[$pid] = $plugin['label'];
    }

    // Generate array of enabled view mode options by bundle.
    $node_display = \Drupal::service('entity_display.repository');
    $view_modes = $node_display->getViewModeOptionsByBundle('node', $type);

    $form['pdf_generation'] = [
      '#type' => 'details',
      '#title' => t('PDF Generation'),
      '#group' => 'additional_settings',
      'pdf_enable' => [
        '#title' => t('Allow PDF generation for this node type'),
        '#type' => 'checkbox',
        '#default_value' => isset($content_types[$type]),
      ],
      'pdf_field_save' => [
        '#title' => t('Media Reference field'),
        '#description' => t('Media file field to be used for storing the generated PDF'),
        '#type' => 'select',
        '#options' => $media_fields,
        '#default_value' => $content_types[$type]['field_save'] ?? FALSE,
        '#states' => [
          'visible' => [
            ':input[name="pdf_enable"]' => ['checked' => TRUE],
          ],
        ],
      ],
      'pdf_plugin' => [
        '#title' => t('Plugin'),
        '#type' => 'select',
        '#options' => $plugins,
        '#default_value' => $content_types[$type]['plugin'] ?? FALSE,
        '#states' => [
          'visible' => [
            ':input[name="pdf_enable"]' => ['checked' => TRUE],
          ],
        ],
      ],
      'pdf_view_modes' => [
        '#title' => t('View Modes'),
        '#type' => 'select',
        '#options' => $view_modes,
        '#default_value' => $content_types[$type]['view_mode'] ?? FALSE,
        '#states' => [
          'visible' => [
            ':input[name="pdf_enable"]' => ['checked' => TRUE],
          ],
        ],
      ],
      'pdf_use_pagejs' => [
        '#title' => t('Use PageJS'),
        '#type' => 'checkbox',
        '#default_value' => isset($content_types[$type]['pagejs']),
        '#states' => [
          'visible' => [
            ':input[name="pdf_enable"]' => ['checked' => TRUE],
          ],
        ],
      ],
      'pdf_submit_label' => [
        '#title' => t('Override submit label'),
        '#type' => 'textfield',
        '#default_value' => $content_types[$type]['submit_label'] ?? '',
        '#states' => [
          'visible' => [
            ':input[name="pdf_enable"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];
    $form['actions']['submit']['#validate'][] = [__CLASS__, 'validate'];
    $form['actions']['submit']['#submit'][] = [__CLASS__, 'submit'];
  }

  /**
   * Get media bundles that can be used for saving PDF files.
   *
   * @return array
   *   Array of media bundles.
   */
  public static function getAvailableMediaBundles() {
    $bundles = &drupal_static(__FUNCTION__);
    if (isset($bundles) && is_array($bundles)) {
      return $bundles;
    }

    $bundles = [];
    $all_bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('media');
    foreach ($all_bundles as $bundle_name => $bundle) {
      $media = Media::create(['bundle' => $bundle_name]);
      $source_field = $media->getSource()->getConfiguration()['source_field'];
      $field_definition = $media->get($source_field)->getFieldDefinition();
      if ($field_definition->getType() === 'file') {
        $extensions = $field_definition->getSetting('file_extensions') ?? NULL;
        if (isset($extensions) && !in_array('pdf', explode(' ', $extensions))) {
          continue;
        }
        $bundles[] = $bundle_name;
      }
    }
    return $bundles;
  }

  /**
   * Validate function for saving settings related to PDF.
   *
   * @see soapbox_pdf_form_node_type_edit_form_alter
   */
  public static function validate($form, &$form_state) {
    if ($form_state->getValue('pdf_enable')) {
      foreach (['pdf_field_save', 'pdf_plugin', 'pdf_view_modes'] as $r_field) {
        if (!$form_state->getValue($r_field)) {
          $element = &$form['pdf_generation'][$r_field];
          $form_state->setError($element, t('"@label" is required', ['@label' => $element['#title']]));
        }
      }
    }
  }

  /**
   * Submit function for saving settings related to PDF.
   *
   * @see soapbox_pdf_form_node_type_edit_form_alter
   */
  public static function submit($form, $form_state) {
    $settings = \Drupal::configFactory()->getEditable('soapbox_pdf.pdf_settings');

    // Get current content type object.
    $build_info = $form_state->getBuildInfo();
    $type = $build_info['callback_object']->getEntity()->id();

    // Get list of all content types with enabled pdf generation.
    $content_types = $settings->get('content_types') ?? [];

    if ($form_state->getValue('pdf_enable')) {
      $content_types[$type] = [
        'field_save' => $form_state->getValue('pdf_field_save'),
        'plugin' => $form_state->getValue('pdf_plugin'),
        'view_mode' => $form_state->getValue('pdf_view_modes'),
        'pagejs' => $form_state->getValue('pdf_use_pagejs'),
        'submit_label' => $form_state->getValue('pdf_submit_label'),
      ];
    }
    else {
      unset($content_types[$type]);
    }
    $settings->set('content_types', $content_types);
    $settings->save();
  }

}
