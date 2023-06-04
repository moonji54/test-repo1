<?php

namespace Drupal\soapbox_field_plugins\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'raw_value' formatter.
 *
 * @FieldFormatter(
 *   id = "raw",
 *   label = @Translation("Raw Value"),
 *   field_types = {
 *    "string_long",
 *   }
 * )
 */
class RawValueFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    foreach ($items as $delta => $item) {
      $value = $item->getValue();
      return [
        '#theme' => 'raw_formatter',
        '#value' => reset($value),
      ];
    }

    return [];
  }

}
