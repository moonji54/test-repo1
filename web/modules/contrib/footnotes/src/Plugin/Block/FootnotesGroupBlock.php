<?php

namespace Drupal\footnotes\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Footnotes Group' block.
 *
 * @Block(
 *   id = "footnotes_group",
 *   admin_label = @Translation("Footnotes Group"),
 * )
 */
class FootnotesGroupBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface;
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * Construct a new FootnotesGroupBlock object.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritDoc}
   */
  public function build() {

    // Set cacheability of block.
    $cache = [
      'contexts' => [
        'languages:language_interface',
        'route',
        'theme',
      ],
      'tags' => [
        'block_view',
        'config:block.block.footnotesgroup',
      ],
    ];
    $params = $this->routeMatch->getParameters()->all();
    foreach ($params as $param) {
      if ($param instanceof ContentEntityInterface) {
        $cache['keys'][0] = 'footnotegroup';
        $cache['keys'][] = $param->getEntityTypeId();
        $cache['keys'][] = $param->id();
        $cache['tags'][] = $param->getEntityTypeId() . ':' . $param->id();
      }
    }

    // Use lazy building so that the output can be generated after the text
    // filters run. This calls the footnotes.group service.
    // @see \Drupal\footnotes\FootnotesGroup::buildFooter()
    return [
      '#create_placeholder' => TRUE,
      '#lazy_builder' => ['footnotes.group:buildFooter', []],
      '#cache' => $cache,
    ];
  }
}
