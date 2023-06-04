<?php

namespace Drupal\soapbox_nodes;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\NodeInterface;

/**
 * Class to help render subpages.
 *
 * @package Drupal\soapbox_nodes
 */
class SubPagesHelperService {

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
   *   Entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * Get a list of sub pages.
   *
   * @param \Drupal\node\NodeInterface|object $node
   *   Node to extract sub pages from.
   * @param array|string $menu_names
   *   Menu to use sub pages.
   * @param string $entity_type_id
   *   The entity type ID for this storage.
   *
   * @return array
   *   Array of sub pages.
   */
  public function getSubPages($node, $menu_names = ['main'], $entity_type_id = 'node'): array {
    if (!$node || !($node instanceof NodeInterface)) {
      return [];
    }

    $show_sub_pages = TRUE;
    $show_sibling_pages = TRUE;

    // Allow other modules to alter.
    \Drupal::moduleHandler()
      ->alter('soapbox_nodes_subpages', $show_sub_pages, $show_sibling_pages);

    if ($show_sub_pages || $show_sibling_pages) {
      $nid = $node->id();
      $system_uri = 'entity:node/' . $nid;
      if (!is_array($menu_names)) {
        $menu_names = [$menu_names];
      }

      // Get the menu item entity from current page's uri.
      $query = \Drupal::entityQuery('menu_link_content')
        ->condition('link.uri', $system_uri)
        ->condition('menu_name', $menu_names, 'IN');

      $result = $query->execute();
      $menu_link_id = (!empty($result)) ? reset($result) : FALSE;
      if ($menu_link_id) {
        $menu_link = MenuLinkContent::load($menu_link_id);
        $menu_link_uuid = $menu_link->uuid();

        if (!empty($menu_link_uuid)) {
          if ($show_sub_pages) {
            // Get sub menu items now.
            $sub_menu_items = $this->getSubMenuItems($menu_link_uuid);
          }

          if (empty($sub_menu_items) && $show_sibling_pages) {
            if ($parent_menu_id = $menu_link->getParentId()) {
              $parent_menu_id = str_replace('menu_link_content:', '', $parent_menu_id);
              $sub_menu_items = $this->getSubMenuItems($parent_menu_id);
            }
          }

          if (!empty($sub_menu_items)) {
            if ($entity_type_id === 'menu_link_content') {
              return $sub_menu_items;
            }
            return $this->getNodeIds($sub_menu_items);
          }
        }
      }
    }

    return [];
  }

  /**
   * Get sub menu items.
   *
   * @param string|int $parent
   *   Parent menu item.
   *
   * @return array
   *   Array of sub menu items.
   */
  public function getSubMenuItems($parent): array {
    // Gets the items in the same order as they are specified in the menu.
    $query = \Drupal::entityQuery('menu_link_content')
      ->condition('parent', "menu_link_content:{$parent}")
      ->condition('enabled', TRUE)
      ->sort('weight', 'ASC')
      ->sort('title', 'ASC');

    return $query->execute();
  }

  /**
   * Get Node ID from the menu plugin IDs.
   *
   * @param array|object $sub_menu_items
   *   Array of sub menu items.
   *
   * @return array
   *   Array of node IDs.
   */
  public function getNodeIds($sub_menu_items) {
    $sub_pages = [];
    $sub_menu_items = MenuLinkContent::loadMultiple($sub_menu_items);

    // Foreach sub menu items, load the node ids.
    foreach ($sub_menu_items as $sub_menu_item) {
      $sub_page_uri = $sub_menu_item->get('link')->uri;
      $sub_page_nid = trim(str_replace('entity:node/', '', $sub_page_uri));
      $sub_pages[] = $sub_page_nid;
    }

    return $sub_pages;
  }

}
