<?php

namespace Drupal\soapbox_nodes;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;

/**
 * Class to help handle languages.
 */
class LanguagesHelperService {

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Render\RendererInterface definition.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * SubPagesHelperService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer
  ) {

    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * Get the languages array.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node being viewed.
   *
   * @return array
   *   Array of translation information.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getLanguages(NodeInterface $node) {
    if (!$node) {
      return [];
    }

    if (is_array($node) && !empty($node['node'])) {
      $node = $node['node'];
    }

    if (empty($node)) {
      $node = \Drupal::routeMatch()->getParameter('node');
    }

    if (!$node instanceof NodeInterface) {
      return [];
    }

    $language_manager = \Drupal::languageManager();
    $languages = $language_manager->getLanguages();
    $current_lang = $language_manager->getCurrentLanguage()->getId();
    $native_languages = $language_manager->getNativeLanguages();
    $standard_languages = $language_manager->getStandardLanguageList();
    $translations = [];

    if (!empty($languages)) {
      foreach ($languages as $language) {
        $language_id = $language->getId();

        if ($node->hasTranslation($language_id)) {
          $translations[$language_id] = [
            'language_id' => $language_id,
            'language' => $language,
            'content' => $node->getTranslation($language_id),
            'url' => $node->getTranslation($language_id)->toUrl()->toString(),
            'current' => $language_id == $current_lang,
            'native_name' => $native_languages[$language_id]->getName(),
            'english_name' => $standard_languages[$language_id][0],
          ];
        }
      }
    }

    return $translations;
  }

}
