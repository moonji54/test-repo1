<?php

namespace Drupal\soapbox_pdf\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Defines an interface for PDF provider plugins.
 */
interface PdfGeneratorInterface extends PluginInspectionInterface {

  /**
   * Generate PDF file based on node page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   */
  public function generateMainPdf(NodeInterface $node);

  /**
   * Return final PDF file.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node object to get saved PDF file.
   *
   * @return \Drupal\file\FileInterface|null
   *   PDF file object.
   */
  public function getFinalPdf(NodeInterface $node);

  /**
   * Save temporary PDF file into node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node object to save PDF.
   * @param \Drupal\user\UserInterface|null $user
   *   The user object is used as media author.
   */
  public function saveFinalPdf(NodeInterface $node, ?UserInterface $user = NULL);

  /**
   * Returns array with batch operations.
   */
  public function batchSteps(NodeInterface $node);

}
