<?php

/**
 * @file
 * Documentation related to soapbox_pdf.
 */

/**
 * Alter request option before sending to client API.
 */
function hook_soapbox_pdf_request_alter($url, array &$options, $type) {
  $options['test'] = TRUE;
}

/**
 * Adding some new operation to PDF generation batch.
 */
function hook_soapbox_pdf_node_operations_alter($node, &$operations) {
  $operations[] = [
    'operation_callback',
    [$node],
  ];
}
