<?php

namespace Drupal\soapbox_pdf\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Handle altering the node edit / create form.
 */
class NodeFormPartialForm {

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
    $build_info = $form_state->getBuildInfo();
    $node_type = $build_info['callback_object']->getEntity()->bundle();
    $content_types = \Drupal::config('soapbox_pdf.pdf_settings')->get('content_types') ?? [];

    if (isset($form['actions']['submit'], $content_types[$node_type])) {
      $value = $content_types['page']['submit_label'] ?? ('Save and generate PDF');
      $form['actions']['generate_pdf'] = ['#value' => $value] + $form['actions']['submit'];
      $form['actions']['generate_pdf']['#submit'][] = [__CLASS__, 'submit'];
    }
  }

  /**
   * Submit handler for the generate PDF button.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function submit(array &$form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    $node = $build_info['callback_object']->getEntity();

    $content_types = \Drupal::config('soapbox_pdf.pdf_settings')->get('content_types') ?? [];
    if (isset($content_types[$node->bundle()])) {
      $plugin_id = $content_types[$node->bundle()]['plugin'];
      $manager = \Drupal::service('plugin.manager.pdf_generator');
      $pdf_generator = $manager->createInstance($plugin_id);
      $batch = [
        'operations' => $pdf_generator->batchSteps($node),
        'finished' => [__CLASS__, 'batchFinished'],
        'title' => t('PDF generating'),
        'error_message' => t("The PDF file wasn't generated"),
      ];
      batch_set($batch);
    }
  }

  /**
   * Batch function for displaying message.
   *
   * @see ::submit()
   */
  public static function batchFinished($finished, $context) {
    if (isset($context['file_link']) && $context['file_link']) {
      $message = t('The PDF <a href="@file-link">file</a> was successfully generated.', [
        '@file-link' => $context['file_link'],
      ]);
    }
    else {
      $message = t('The PDF file was successfully generated');
    }
    \Drupal::messenger()->addMessage($message);
  }

}
