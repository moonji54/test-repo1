<?php

namespace Drupal\soapbox_flexible_publishing\Form;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Flexible publishing node edit form.
 */
class FlexiblePublishingNodeEditFormAlter {

  /**
   * Form mode selection form.
   *
   * @param array $form
   *   The node edit form.
   * @param array $settings
   *   The node type settings.
   * @param array $available_form_modes
   *   The available form modes.
   *
   * @return array
   *   The updated form.
   */
  public function formModeSelectionForm(array &$form, array $settings, array $available_form_modes) {
    $keys = [
      '#parents',
      '#type',
      '#action',
      '#tree',
    ];
    $form = array_intersect_key($form, array_combine($keys, $keys));

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
    return $form;
  }

  /**
   * Add template form mode to advanced tab.
   *
   * @param array $form
   *   The node edit form.
   * @param \Drupal\node\NodeInterface $node
   *   The node being edited.
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   Node type.
   *
   * @return array
   *   Updated form.
   */
  public function formTemplateInfoAdvancedTab(array $form, NodeInterface $node, NodeTypeInterface $node_type) {
    /** @var \Drupal\Core\Entity\EntityDisplayRepository $entity_display */
    $entity_display = \Drupal::service('entity_display.repository');
    $available_form_modes = $entity_display->getFormModeOptionsByBundle('node', $node_type->id());
    $template = $node->get('template')->value;
    if (is_array($available_form_modes) && $template && isset($available_form_modes[$template])) {
      $form['flexible_publishing_info'] = [
        '#type' => 'details',
        '#title' => t('Template information'),
        '#group' => 'advanced',
        '#open' => FALSE,
        'form_mode' => [
          '#plain_text' => $available_form_modes[$template],
        ],
        '#weight' => -1,
      ];
    }
    return $form;
  }

}
