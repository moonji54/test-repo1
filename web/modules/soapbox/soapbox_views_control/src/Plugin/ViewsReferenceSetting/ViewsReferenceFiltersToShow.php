<?php

namespace Drupal\soapbox_views_control\Plugin\ViewsReferenceSetting;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\viewsreference\Plugin\ViewsReferenceSettingInterface;

/**
 * The views reference setting "filters to show" plugin.
 *
 * @ViewsReferenceSetting(
 *   id = "filters_to_show",
 *   label = @Translation("Filters to show"),
 *   default_value = "",
 * )
 */
class ViewsReferenceFiltersToShow extends PluginBase implements ViewsReferenceSettingInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function alterFormField(array &$form_field) {
    $form_field['#title'] = $this->t('Filters to show');
    $form_field['#type'] = 'checkboxes';
    $form_field['#default_value'] = $form_field['#default_value'] ?: [];
    $form_field['#weight'] = 50;
    $form_field['#options'] = [];

    // Get the view and selected display.
    if ($this->configuration['view_name'] && $view = Views::getView($this->configuration['view_name'])) {
      if ($this->configuration['display_id'] && $view->setDisplay($this->configuration['display_id'])) {
        // For each exposed filter, let the site editor choose if it should
        // be displayed.
        if ($handlers = $view->getDisplay()->getHandlers('filter')) {
          foreach ($handlers as $key => $handler) {
            if (isset($handler->options['exposed']) && $handler->options['exposed']) {
              $form_field['#options'][$key] = (method_exists($handler, 'exposedInfo') ? $handler->exposedInfo()['label'] : $handler->adminLabel());
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterView(ViewExecutable $view, $filters_to_show) {
    // If there are no filters to show, viewsreference.module returned an
    // empty string.
    if (empty($filters_to_show) || !is_array($filters_to_show)) {
      $filters_to_show = [];
    }

    $filters_to_show = array_values($filters_to_show);
    $filters_to_show = array_filter($filters_to_show);

    if (isset($view->element['#viewsreference'])) {
      if ($handlers = &$view->getDisplay()->getHandlers('filter')) {
        foreach ($handlers as $key => $handler) {
          if (isset($handler->options['exposed'])  && $handler->options['exposed'] && !in_array($key, $filters_to_show)) {
            unset($handlers[$key]);
          }
        }
      }
    }
  }

}
