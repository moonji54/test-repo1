<?php

namespace Drupal\soapbox_nodes\TwigExtension;

use Drupal\node\NodeInterface;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\Unicode;
use Drupal\paragraphs\ParagraphInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class ExcerptTwigExtension for Node.
 */
class ExcerptTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('get_excerpt', [
        $this,
        'getNodeExcerpt',
      ]),
    ];
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
    return 'soapbox_nodes.excerpt.twig.extension';
  }

  /**
   * Get the excerpt of node using relevant node fields.
   *
   * @param Drupal\node\Entity\Node|bool $node
   *   The node object.
   * @param array|object $node_fields
   *   The node fields used for generating excerpts.
   * @param array|object $paragraph_fields
   *   The paragrpah fields used for generating excerpts.
   * @param int $limit
   *   The char length to limit the text to.
   *
   * @return bool[]|string[]
   *   The generated excerpt.
   */
  public function getNodeExcerpt($node = FALSE, $node_fields = [
    'field_short_description',
    'field_description',
  ], $paragraph_fields = [
    [
      'field' => 'field_components',
      'type' => 'wysiwyg',
      'wysiwyg_body' => 'field_body',
    ],
  ], $limit = 300): array {
    $build = [
      '#markup' => '',
    ];

    if (!is_object($node) || !$node instanceof NodeInterface) {
      return $build;
    }

    // Set excerpt from the node fields.
    if ($node_fields) {
      foreach ($node_fields as $node_field) {
        if ($node->hasField($node_field) && $description = $node->get($node_field)) {
          if ($description && $description = $description->value) {
            $excerpt = $description;
            break;
          }
        }
      }
    }

    // Loop through paragraph fields and get first Wysiwyg excerpt.
    if (empty($excerpt) && $paragraph_fields) {
      foreach ($paragraph_fields as $paragraph_field) {
        if ($node->hasField($paragraph_field['field']) && $components = $node->get($paragraph_field['field'])) {
          foreach ($components as $component) {
            $component_entity = $component->entity;
            if ($component_entity && $component_entity->getType() === $paragraph_field['type']
                && $component_entity->hasField($paragraph_field['wysiwyg_body'])
                && $component_entity->get($paragraph_field['wysiwyg_body']) instanceof ParagraphInterface) {
              $body = $component_entity->get($paragraph_field['wysiwyg_body'])->value;
              if (!empty($body)) {
                $excerpt = $this->buildExcerpt($body, $limit);
                break;
              }
            }
          }
        }
      }
    }

    // Set excerpt from default drupal body field if it exists.
    if (empty($excerpt) && $node->hasField('field_body') && $body = $node->get('field_body')) {
      if ($body && $body = $body->value) {
        if (!empty($body)) {
          $excerpt = $this->buildExcerpt($body, $limit);
        }
      }
    }

    if (!empty($excerpt)) {
      $build = [
        '#markup' => $excerpt,
      ];
    }

    return $build;
  }

  /**
   * Truncates the text by a certain limit.
   *
   * @param string $content
   *   Text to truncate.
   * @param int $char_length
   *   Length to limit the excerpt text to.
   *
   * @return bool|string
   *   The truncated text.
   */
  protected function buildExcerpt($content, $char_length = 300) {
    $content = strip_tags($content, '<br><br/><br />');
    $content = PlainTextOutput::renderFromHtml($content);
    $html = '';

    if (!empty($content) && strlen($content) > 0) {
      if (strlen($content) > $char_length) {
        $html = Unicode::truncate($content, $char_length, TRUE, TRUE, 1);
      }
      else {
        $html = Unicode::truncate($content, strlen($content), TRUE, FALSE, 1);
      }
    }

    return $html;
  }

}
