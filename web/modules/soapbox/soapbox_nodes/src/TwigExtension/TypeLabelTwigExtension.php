<?php

namespace Drupal\soapbox_nodes\TwigExtension;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class NodeTwigExtension.
 */
class TypeLabelTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('type_label', [
        $this,
        'getSubtype',
      ]),
    ];
  }

  /**
   * Get the type label from subtype taxonomies.
   *
   * @param \Drupal\node\NodeInterface|bool $node
   *   The node object.
   * @param array|object $taxonomies
   *   The subtype taxonomy terms used for type meta.
   *
   * @return array
   *   The render array.
   */
  public function getSubtype($node = FALSE, $taxonomies = []): array {

    $build = [
      '#markup' => '',
    ];

    if (!is_object($node) || !$node instanceof NodeInterface) {
      return $build;
    }

    if ($taxonomies) {
      foreach ($taxonomies as $node_bundle => $taxonomy_field) {
        if ($node_bundle === $node->bundle()) {
          $build['#markup'] = $this->getLabelFromFirstTaxonomyTerm($node,
            $taxonomy_field);
          break;
        }
      }
    }

    // Use the entity bundle label (ie, content type label) if not yet set.
    if (!$build['#markup'] && isset($node->type->entity)) {
      $build['#markup'] = $this->getTranslatedLabel($node->type->entity, $node->type->entity->label());
    }

    return $build;
  }

  /**
   * Get the first taxonomy term label.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node object.
   * @param string $field
   *   The field machine name.
   *
   * @return string
   *   The taxonomy label or empty.
   */
  protected function getLabelFromFirstTaxonomyTerm(Node $node, $field): string {
    // Ensure field exists and has value to prevent fatal errors.
    if ($node->hasField($field) && $subtypes = $node->get($field)) {
      foreach ($subtypes as $subtype_reference) {
        // Ensure referenced entity hasn't been deleted.
        if (isset($subtype_reference->entity) && $subtype_reference->entity instanceof TermInterface) {
          $subtype = $subtype_reference->entity;

          /** @var \Drupal\taxonomy\TermInterface $subtype */
          return $this->getTranslatedLabel($subtype, $subtype->getName());
        }
      }
    }

    // Nothing found, return label.
    return $this->getTranslatedLabel($node->type->entity, $node->type->entity->label());
  }

  /**
   * Get translated label for node/subtype.
   *
   * @param object|array $entity
   *   Entity to get the translation for.
   * @param string $default
   *   Default label.
   *
   * @return mixed|string|null
   *   Translated label if there is translation, otherwise default.
   */
  public function getTranslatedLabel($entity, string $default): string {
    $current_language = \Drupal::languageManager()
      ->getCurrentLanguage()
      ->getId();
    $entity_language = $entity->language()->getId();
    if ($current_language !== $entity_language && $entity->hasTranslation($current_language)) {
      $translation = $entity->getTranslation($current_language);
      if ($translation instanceof TermInterface) {
        return $translation->getName();
      }
      else {
        return $translation->label();
      }
    }

    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getTests() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOperators() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'soapbox_nodes.type_label.twig.extension';
  }

}
