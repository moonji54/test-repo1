<?php

namespace Drupal\soapbox_flexible_publishing\Controller;

use Drupal\Core\Url;
use Drupal\node\Controller\NodeController;

/**
 * Returns responses for Node routes.
 */
class FlexiblePublishingNodeController extends NodeController {

  /**
   * Displays add content links for available content types.
   *
   * Redirects to node/add/[type] if only one content type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the node types that can be added; however,
   *   if there is only one node type defined for the site, the function
   *   will return a RedirectResponse to the node add page for that one node
   *   type.
   */
  public function addPage() {
    $definition = $this->entityTypeManager()->getDefinition('node_type');

    $build = [];
    $build['table'] = [
      '#type' => 'table',
      '#header' => ['', $this->t('Default')],
    ];

    /** @var \Drupal\Core\Entity\EntityDisplayRepository $entity_display */
    $entity_display = \Drupal::service('entity_display.repository');
    $form_modes = $entity_display->getAllFormModes();
    if (empty($form_modes['node'])) {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('The flexible publishing add page is not available until at least one form mode has been created.'),
      ];
    }
    foreach ($form_modes['node'] as $form_mode => $form_mode_settings) {
      $build['table']['#header'][] = $form_mode_settings['label'];
    }

    $types = $this->entityTypeManager()->getStorage('node_type')->loadMultiple();
    uasort($types, [$definition->getClass(), 'sort']);
    // Only use node types the user has access to.
    $count = 0;
    foreach ($types as $type) {
      /** @var \Drupal\node\NodeTypeInterface $type */

      $access = $this->entityTypeManager()->getAccessControlHandler('node')->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $build['table'][$count]['content_type'] = [
          '#markup' => '<b>' . $type->label() . '</b>',
        ];

        $settings = $type->getThirdPartySettings('soapbox_flexible_publishing');
        $available_form_modes = $entity_display->getFormModeOptionsByBundle('node', $type->id());

        if (isset($settings['use_templates']) && $settings['use_templates']) {
          $available_form_modes = array_intersect_key($available_form_modes, $settings['form_modes_as_templates']);
        }
        else {
          $available_form_modes = array_intersect_key($available_form_modes, ['default' => 'default']);
        }

        // Default form mode if no custom form modes are available.
        $build['table'][$count]['default'] = ['#plain_text' => ''];
        if (count($available_form_modes) == 1) {
          $build['table'][$count]['default'] = [
            '#type' => 'link',
            '#title' => $this->t('Create'),
            '#attributes' => [
              'title' => $this->t('Create @type', ['@type' => $this->t('Simple Item')]),
              'class' => [
                'button',
              ],
            ],
            '#url' => Url::fromRoute('node.add', ['node_type' => $type->id()]),
          ];
        }

        // Custom form modes.
        if (!empty($form_modes['node'])) {
          $available_form_modes = array_keys($available_form_modes);
          foreach ($form_modes['node'] as $form_mode => $form_mode_settings) {
            // Add button if available.
            if (in_array($form_mode, $available_form_modes)) {
              $build['table'][$count][$form_mode] = [
                '#type' => 'link',
                '#title' => $this->t('Create'),
                '#attributes' => [
                  'title' => $this->t('Create @type', ['@type' => $form_mode_settings['label']]),
                  'class' => [
                    'button',
                  ],
                ],
                '#url' => Url::fromRoute('node.add.' . $form_mode, ['node_type' => $type->id()]),
              ];
            }
            else {
              $build['table'][$count][$form_mode] = [
                '#plain_text' => '',
              ];
            }
          }
        }

        $count++;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    return $build;
  }

}
