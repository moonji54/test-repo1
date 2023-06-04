<?php

namespace Drupal\soapbox_flexible_publishing;

use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Flexible publishing model manager class.
 */
class FlexiblePublishingManager {

  /**
   * The node type or boolean. Null if not yet checked.
   *
   * @var null|false|NodeTypeInterface
   */
  protected $currentNodeType = NULL;

  /**
   * The form mode or boolean. Null if not yet checked.
   *
   * @var null|string
   */
  protected $currentFormMode = NULL;

  /**
   * Get the current node type.
   *
   * @return \Drupal\node\Entity\NodeTypeInterface|bool
   *   The node type or false.
   */
  public function getCurrentNodeType() {
    if ($this->currentNodeType !== NULL) {
      return $this->currentNodeType;
    }
    $route_match = \Drupal::routeMatch();

    // Depending on route, node type may be an object or string.
    $node_type = $route_match->getParameter('node_type');
    if ($node_type instanceof NodeTypeInterface) {
      $this->currentNodeType = $node_type;
      return $this->currentNodeType;
    }
    if ($node_type && is_string($node_type)) {
      /** @var \Drupal\node\NodeTypeInterface $type */
      $node_type = NodeType::load($node_type);
      $this->currentNodeType = $node_type;
      return $this->currentNodeType;
    }

    // If we don't have node type, maybe we have a node which we can get node
    // type from.
    $node = $route_match->getParameter('node');
    if ($node instanceof NodeInterface) {
      /** @var \Drupal\node\NodeTypeInterface $type */
      $node_type = NodeType::load($node->getType());
      $this->currentNodeType = $node_type;
      return $this->currentNodeType;
    }

    $this->currentNodeType = FALSE;
    return $this->currentNodeType;
  }

  /**
   * Get the current form mode.
   *
   * @return string
   *   The current form mode.
   */
  public function getCurrentFormMode() {
    if ($this->currentFormMode !== NULL) {
      return $this->currentFormMode;
    }
    $route_match = \Drupal::routeMatch();
    $route_name = $route_match->getRouteName();

    // Ensure that the route is a standard content edit route.
    if (
      !str_starts_with($route_name, 'node.add')
      && !str_starts_with($route_name, 'form_mode_manager.node.content_translation_add')
      && !str_starts_with($route_name, 'entity.node.edit_form')
      && !str_starts_with($route_name, 'entity.node.content_translation_edit')
    ) {
      return NULL;
    }

    // Extract the form mode from the route.
    $form_mode = str_replace([
      'node.add',
      'entity.node.edit_form',
      'form_mode_manager.node.content_translation_add',
      'entity.node.content_translation_add',
      'entity.node.content_translation_edit',
    ], '', $route_name);
    $form_mode = ltrim($form_mode, '.');

    // Double check that the form mode determined is actually an available form
    // mode.
    /** @var \Drupal\Core\Entity\EntityDisplayRepository $entity_display */
    $entity_display = \Drupal::service('entity_display.repository');
    $available_form_modes = $entity_display->getFormModeOptions('node');
    if (in_array($form_mode, array_keys($available_form_modes))) {
      $this->currentFormMode = $form_mode ?: '';
    }
    return $this->currentFormMode;
  }

  /**
   * Maybe redirect to node edit screen in a particular form mode.
   */
  public function maybeRedirectToNodeEditInFormMode() {

    // If we are editing an existing node, ensure the form mode matches the
    // selected template.
    $route_match = \Drupal::routeMatch();
    $route_name = $route_match->getRouteName();

    if (strpos($route_name, 'entity.node.edit_form') === 0 || strpos($route_name, 'node.add.') === 0 || strpos($route_name, 'entity.node.content_translation_add') === 0) {

      $node_type = $this->getCurrentNodeType();
      if (!$node_type) {
        return;
      }

      // Determine if flexible publishing template system is in use and user has
      // access to it.
      $settings = $node_type->getThirdPartySettings('soapbox_flexible_publishing');
      if (!isset($settings['use_templates']) || !$settings['use_templates'] || !\Drupal::currentUser()->hasPermission('use flexible publishing templates')) {
        return;
      }

      // If we have an existing node, redirect to the allowed form mode.
      $node = $route_match->getParameter('node');
      if ($node instanceof NodeInterface) {
        $form_mode = $this->getCurrentFormMode();

        // Get the template, possibly from the translation.
        $template = $node->get('template')->value;
        if ($languages = $node->getTranslationLanguages()) {
          foreach ($languages as $language_code => $language) {
            if ($translation = $node->getTranslation($language_code)) {
              if ($template = $translation->get('template')->value) {
                continue;
              }
            }
          }
        }

        if ($template && $form_mode !== $template) {
          if ($route_name == 'entity.node.content_translation_add') {
            if ($template !== 'default') {

              // Content translation add form, redirect to correct form mode.
              $route_name = 'form_mode_manager.node.content_translation_add.' . $template;
              $url = new Url($route_name, [
                'node' => $node->id(),
                'source' => $route_match->getRawParameter('source'),
                'target' => $route_match->getRawParameter('target'),
              ]);
              $response = new RedirectResponse($url->toString(), 302);
              $response->send();
            }
          }
          else {

            // Default language edit form, redirect to correct form mode.
            $route_name = 'entity.node.edit_form';
            if ($template !== 'default') {
              $route_name .= '.' . $template;
            }
            $url = new Url($route_name, ['node' => $node->id()]);
            $response = new RedirectResponse($url->toString(), 302);
            $response->send();
          }
        }
      }
    }
  }

  /**
   * Maybe get the form mode selection form.
   *
   * If no form mode has been selected and this node type is meant to use
   * specific form modes, force the user to select a form mode first.
   *
   * @param array $form
   *   The node edit form.
   *
   * @return bool
   *   False if not redirecting to form mode selection.
   */
  public function maybeSetFormModeSelectionForm(array &$form) {
    $route_match = \Drupal::routeMatch();
    if (strpos($route_match->getRouteName(), 'node.add') === 0) {

      $node_type = $this->getCurrentNodeType();
      if (!$node_type) {
        return FALSE;
      }

      // Determine if flexible publishing template system is in use and user has
      // access to it.
      $settings = $node_type->getThirdPartySettings('soapbox_flexible_publishing');
      if (!isset($settings['use_templates']) || !$settings['use_templates'] || !\Drupal::currentUser()->hasPermission('use flexible publishing templates')) {
        return FALSE;
      }

      // If we have a form mode selected already, if it is one of the allowed
      // ones, bail.
      $form_mode = $this->getCurrentFormMode();
      if ($form_mode && in_array($form_mode, $settings['form_modes_as_templates'])) {
        return FALSE;
      }

      // If no display is selected, let the user select one.
      /** @var \Drupal\Core\Entity\EntityDisplayRepository $entity_display */
      $entity_display = \Drupal::service('entity_display.repository');
      $available_form_modes = $entity_display->getFormModeOptionsByBundle('node', $node_type->id());
      if (count($available_form_modes) > 1) {
        $url = new Url('flexible_publishing.form_mode_selection', ['node_type' => $node_type->id()]);
        $response = new RedirectResponse($url->toString(), 302);
        $response->send();
      }
    }
    return FALSE;
  }

  /**
   * Maybe add the template info advanced tab.
   *
   * @param array $form
   *   The form.
   *
   * @return array
   *   The updated form.
   */
  public function maybeAddTemplateInfoToForm(array $form) {
    $route_match = \Drupal::routeMatch();
    $route_name = $route_match->getRouteName();
    if (strpos($route_name, 'entity.node.edit_form') !== 0 && strpos($route_name, 'node.add.') !== 0) {
      return $form;
    }

    $node = $route_match->getParameter('node');
    if ($node instanceof NodeInterface) {
      $node_type = $this->getCurrentNodeType();
      if (!$node_type) {
        return $form;
      }

      // Determine if flexible publishing template system is in use and user has
      // access to it.
      $settings = $node_type->getThirdPartySettings('soapbox_flexible_publishing');
      if (!isset($settings['use_templates']) || !$settings['use_templates'] || !\Drupal::currentUser()->hasPermission('use flexible publishing templates')) {
        return $form;
      }

      /** @var \Drupal\soapbox_flexible_publishing\Form\FlexiblePublishingNodeEditFormAlter $node_edit_form_alter */
      $node_edit_form_alter = \Drupal::service('soapbox_flexible_publishing.node_edit_form_alter');
      return $node_edit_form_alter->formTemplateInfoAdvancedTab($form, $node, $node_type);
    }
    return $form;
  }

  /**
   * Save the active form mode so we know which template to use and display.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node about to be saved.
   */
  public function nodePresaveSaveTemplate(NodeInterface $node) {
    if ($form_mode = $this->getCurrentFormMode()) {
      $node->template = $form_mode;
    }
  }

}
