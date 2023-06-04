<?php

namespace Drupal\soapbox_nodes\TwigExtension;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Class to provide an opinionated date line.
 */
class DateTwigExtension extends AbstractExtension {

  /**
   * Drupal\Core\Datetime\DateFormatter definition.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * DateTwigExtension constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   Date formatter service to format the date.
   */
  public function __construct(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Twig filters.
   *
   * @return \Twig\TwigFilter[]
   *   The Twig filters to return.
   */
  public function getFilters() {
    return [
      new TwigFilter('dateline', [
        $this,
        'getDateLine',
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTests() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOperators() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'soapbox_nodes.date.twig.extension';
  }

  /**
   * Get the dateline for meta.
   *
   * @param object|false $node
   *   Node to process.
   * @param array|object $node_date_fields
   *   The node date fields for different node bundles.
   * @param string|null $date_format
   *   The general date format to use.
   *
   * @return array|string
   *   The formatted dateline.
   */
  public function getDateLine($node = FALSE, $node_date_fields = [
    [
      'node_bundle' => 'publication',
      'date_field' => 'publication_date',
      'suffix_field' => 'field_timezone',
    ],
    [
      'node_bundle' => 'event',
      'range' => TRUE,
      'range_start' => 'field_start_date',
      'range_end' => 'field_end_date',
      'date_format' => 'some_format',
    ],
  ], $date_format = 'j F Y') {
    $build = [
      '#markup' => '',
    ];

    if (!is_object($node) || !$node instanceof NodeInterface) {
      return $build;
    }

    $date_line = '';
    if ($node_date_fields) {
      $current_node_bundle = $node->bundle();
      $times_to_ignore = [
        ' 00:00:00',
        '00:00',
        '\T00:00:00',
        '12:00 am',
      ];

      foreach ($node_date_fields as $node_date_field) {
        if ($node_date_field['node_bundle'] === $current_node_bundle) {
          $date_format_to_use = $date_format;
          if (!empty($node_date_field['date_format'])) {
            $date_format_to_use = $node_date_field['date_format'];
          }

          // Process range fields.
          if (!empty($node_date_field['range'])) {
            // Sort start date.
            $start_date = $node->get($node_date_field['range_start'])->value;
            if ($start_date) {
              $original_date = new DrupalDateTime($start_date, 'UTC');
              $date_line = $this->dateFormatter
                ->format($original_date->getTimestamp(), $date_format_to_use, '');
              $date_line = trim(str_replace($times_to_ignore, '', $date_line));
              $start_date_dmy = date('Ymd', $original_date->getTimestamp());
            }

            // Sort end date.
            $end_date = $node->get($node_date_field['range_end'])->value;
            if ($end_date) {
              $original_date = new DrupalDateTime($end_date, 'UTC');
              $end_date_line = $this->dateFormatter
                ->format($original_date->getTimestamp(), $date_format_to_use, '');
              $end_date_line = trim(str_replace($times_to_ignore, '', $end_date_line));
              $end_date_dmy = date('Ymd', $original_date->getTimestamp());

              // If same day event and the date contains time aswell
              // then only show the time range with the date.
              if ($start_date_dmy === $end_date_dmy) {
                if (stripos($end_date_line, ':') !== FALSE) {
                  $end_date_line = date('H:i', $original_date->getTimestamp());
                }
                else {
                  $end_date_line = '';
                }
              }

              // Append end date to date line
              // if it's not te same as start date line.
              if ($end_date_line && $date_line !== $end_date_line) {
                $date_line .= ' - ' . $end_date_line;
              }
            }
          }
          // Process individual date field.
          elseif (!empty($node_date_field['date_field'])) {
            $date = $node->get($node_date_field['date_field'])->value;
            if ($date) {
              $original_date = new DrupalDateTime($date);
              $date_line = $this->dateFormatter
                ->format($original_date->getTimestamp(), $date_format_to_use, '');
              $date_line = trim(str_replace($times_to_ignore, '', $date_line));
            }

          }

          // Suffix.
          if ($date_line && !empty($node_date_field['suffix_field'])) {
            $suffix = $node->get($node_date_field['suffix_field'])->value;
            if ($suffix) {
              $date_line .= ' ' . $suffix;
            }
          }

          break;
        }
      }
    }

    if ($date_line) {
      return [
        '#markup' => $date_line,
      ];
    }
    else {
      return $this->getDate($node, $date_format);
    }
  }

  /**
   * Get the date from the Unified date field using the relevant format.
   *
   * @param false|object $node
   *   Current node to process.
   * @param string $date_format
   *   Date format to use.
   *
   * @return array|string[]
   *   The date to return.
   */
  public function getDate($node = FALSE, $date_format = FALSE) {

    if (!$date_format) {
      return [];
    }

    $build = [
      '#markup' => '',
    ];
    if (!is_object($node) || !$node instanceof Node) {
      return $build;
    }

    $date = $node->get('unified_date')->value;
    if ($date) {
      if (!is_numeric($date)) {
        $date = strtotime($date);
      }

      $date = $this->dateFormatter
        ->format($date, $date_format, '');

      $build = [
        '#markup' => $date,
      ];
    }

    return $build;
  }

}
