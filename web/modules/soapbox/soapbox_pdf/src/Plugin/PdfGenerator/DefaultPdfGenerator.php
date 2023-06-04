<?php

namespace Drupal\soapbox_pdf\Plugin\PdfGenerator;

use Drupal\node\NodeInterface;
use Drupal\soapbox_pdf\Plugin\PdfGeneratorBase;

/**
 * Provides default PDF generator.
 *
 * @PdfGenerator(
 *   id = "default",
 *   label = @Translation("Default"),
 * )
 */
class DefaultPdfGenerator extends PdfGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function batchSteps(NodeInterface $node) {
    $operations = [
      [
        [__CLASS__, 'runPluginStep'],
        [$this->pluginId, 'generateMainPdf', $node],
      ],
      [
        [__CLASS__, 'runPluginStep'],
        [$this->pluginId, 'saveFinalPdf', $node, NULL],
      ],
      [
        [__CLASS__, 'runPluginStep'],
        [$this->pluginId, 'generateBatchPdfLink', $node],
      ],
    ];
    // Allowing other modules to add batch operations.
    $this->moduleHandler->alter('soapbox_pdf_node_operations', $node, $operations);

    return $operations;

  }

  /**
   * {@inheritdoc}
   */
  public function generateMainPdf(NodeInterface $node) {
    $options = [
      'json' => [
        'url' => $this->getNodePdfPage($node),
        'options' => [
          'displayHeaderFooter' => FALSE,
          'printBackground' => TRUE,
          'format' => 'Letter',
          'margin' => (object) [
            // Set top and bottom to allow for a header.
            'top' => '0',
            'bottom' => '0',
            'left' => '0',
            'right' => '0',
          ],
          'scale' => 1.0,
          'preferCSSPageSize' => TRUE,
        ],
        'gotoOptions' => [
          'timeout' => 0,
          'waitUntil' => 'networkidle0',
        ],
      ],
      'query' => [
        'return-type' => 'pdf',
      ],
      'sink' => $this->getTemporaryFilePath($node),
    ];
    $this->doRequest(self::CLIENT_URL, $options, self::REQUEST_TYPES_GENERATE);
  }

}
