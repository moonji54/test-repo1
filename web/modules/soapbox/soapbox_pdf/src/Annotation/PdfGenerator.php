<?php

namespace Drupal\soapbox_pdf\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a PDF provider item annotation object.
 *
 * @see \Drupal\soapbox_pdf\Plugin\PdfGeneratorManager
 * @see plugin_api
 *
 * @Annotation
 */
class PdfGenerator extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
