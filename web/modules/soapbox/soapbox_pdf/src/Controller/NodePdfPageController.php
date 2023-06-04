<?php

namespace Drupal\soapbox_pdf\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines controller for generating PDF file.
 */
class NodePdfPageController extends ControllerBase {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Render node page to generate PDF.
   *
   * @return string
   *   Return PDF node content.
   */
  public function view(NodeInterface $node) {
    $pdf_settings = $this->configFactory->get('soapbox_pdf.pdf_settings');
    $content_types = $pdf_settings->get('content_types') ?? [];

    // Return page not found error if PDF generating isn't enabled for this
    // content type.
    if (empty($content_types[$node->getType()])) {
      throw new NotFoundHttpException();
    }

    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    $build = [
      '#theme' => 'node_pdf_content',
      '#node_view' => $view_builder->view($node, $content_types[$node->getType()]['view_mode']),
      '#cache' => [
        'tags' => [
          'node:' . $node->id(),
        ],
      ],
    ];

    // Add PageJS library.
    if (!empty($content_types[$node->getType()]['pagejs'])) {
      $build['#attached']['library'][] = 'soapbox_pdf/pagedjs';
    }

    return $build;
  }

}
