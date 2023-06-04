<?php

namespace Drupal\soapbox_pdf\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the PDF generator plugin manager.
 */
class PdfGeneratorManager extends DefaultPluginManager {

  /**
   * Constructs a new PdfGeneratorManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/PdfGenerator', $namespaces, $module_handler, 'Drupal\soapbox_pdf\Plugin\PdfGeneratorInterface', 'Drupal\soapbox_pdf\Annotation\PdfGenerator');
    $this->alterInfo('soapbox_pdf_pdf_generator_plugin_info');
    $this->setCacheBackend($cache_backend, 'soapbox_pdf_pdf_generator_plugin_plugins');
  }

}
