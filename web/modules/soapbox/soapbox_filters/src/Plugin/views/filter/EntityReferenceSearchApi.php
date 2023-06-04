<?php

namespace Drupal\soapbox_filters\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\views\filter\SearchApiFilterTrait;
use Drupal\views\Plugin\views\filter\EntityReference;

/**
 * Filter by entity reference from search api content datasource.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("entityreference_search_api_filter")
 *
 * @see \Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid
 */
class EntityReferenceSearchApi extends EntityReference {

  use SearchApiFilterTrait;

  /**
   * Gets the target entity type ID referenced by this field.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   Entity type.
   */
  protected function getReferencedEntityType(): EntityTypeInterface {
    $entity_type_manager = \Drupal::entityTypeManager();
    return $entity_type_manager->getDefinition('node');
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    switch ($this->options['widget']) {
      case self::WIDGET_SELECT:
        $this->valueFormAddSelect($form, $form_state);
        break;

      case self::WIDGET_AUTOCOMPLETE:
        $this->valueFormAddAutocomplete($form, $form_state);
        break;
    }
  }

}
