<?php

namespace Drupal\soapbox_flexible_publishing\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class FlexiblePublishingAddContentForm.
 */
class FlexiblePublishingFormModeSelectionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flexible_publishing_form_mode_selection_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Ensure the selected template is still an allowed template.
    /** @var \Drupal\soapbox_flexible_publishing\FlexiblePublishingManager $flexible_publishing_manager */
    $flexible_publishing_manager = \Drupal::service('soapbox_flexible_publishing.manager');
    if ($node_type = $flexible_publishing_manager->getCurrentNodeType()) {
      $settings = $node_type->getThirdPartySettings('soapbox_flexible_publishing');

      // If no display is selected, let the user select one.
      /** @var \Drupal\Core\Entity\EntityDisplayRepository $entity_display */
      $entity_display = \Drupal::service('entity_display.repository');
      $available_form_modes = $entity_display->getFormModeOptionsByBundle('node', $node_type->id());
      if (count($available_form_modes) > 1) {

        $form['title'] = [
          '#plain_text' => t('Which format would you like to use?'),
        ];
        $form['container'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['container-inline'],
          ],
        ];
        // Ensure the selected template is still an allowed template.
        /** @var \Drupal\soapbox_flexible_publishing\FlexiblePublishingManager $flexible_publishing_manager */
        $flexible_publishing_manager = \Drupal::service('soapbox_flexible_publishing.manager');
        if ($node_type = $flexible_publishing_manager->getCurrentNodeType()) {
          foreach ($settings['form_modes_as_templates'] as $form_mode) {
            if (!array_key_exists($form_mode, $available_form_modes)) {
              continue;
            }
            $route_name = 'node.add.' . $form_mode;
            $form['container'][$form_mode] = [
              '#type' => 'link',
              '#url' => Url::fromRoute($route_name, ['node_type' => $node_type->id()]),
              '#title' => $available_form_modes[$form_mode],
              '#attributes' => [
                'class' => [
                  'button',
                  'button--primary',
                ],
              ],
            ];
          }
        }
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
