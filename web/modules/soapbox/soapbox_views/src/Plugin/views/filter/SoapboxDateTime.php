<?php

namespace Drupal\soapbox_views\Plugin\views\filter;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime\Plugin\views\filter\Date;

/**
 * Soapbox DateTime Filter.
 *
 * This filter plugin used to override default core weak functionality.
 * Contains next patches:
 * - https://www.drupal.org/node/2868014
 * - https://www.drupal.org/node/2966735
 *
 * @ViewsFilter("soapbox_datetime")
 */
class SoapboxDateTime extends Date {

  /**
   * Mapping of granularity values to their corresponding date formats.
   *
   * @var array
   */
  protected $dateFormats = [
    'second' => 'Y-m-d\TH:i:s',
    'minute' => 'Y-m-d\TH:i',
    'hour' => 'Y-m-d\TH',
    'day' => 'd',
    'month' => 'm',
    'year' => 'Y',
  ];

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = [];
    // Only show granularity options for times if the field supports a time.
    if ($this->fieldStorageDefinition->getSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATETIME) {
      $options = [
        'second' => $this->t('Second'),
        'minute' => $this->t('Minute'),
        'hour' => $this->t('Hour'),
      ];
    }
    // All fields (datetime or date-only) will need these options.
    $options['day'] = $this->t('Day');
    $options['month'] = $this->t('Month');
    $options['year'] = $this->t('Year');

    $form['granularity'] = [
      '#type' => 'radios',
      '#title' => $this->t('Granularity'),
      '#options' => $options,
      '#description' => $this->t('The granularity is the smallest unit to use when determining whether two dates are the same; for example, if the granularity is "Year" then all dates in 1999, regardless of when they fall in 1999, will be considered the same date.'),
      '#default_value' => $this->options['granularity'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Set the date format based on granularity.
    if (isset($this->dateFormats[$this->options['granularity']])) {
      $this->dateFormat = $this->dateFormats[$this->options['granularity']];
    }

    parent::query();
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['granularity'] = [
      // The default depends on if the field is date-only or includes time.
      'default' => $this->fieldStorageDefinition->getSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATETIME ? 'second' : 'day',
    ];

    return $options;
  }

  /**
   * Override parent method, which deals with dates as integers.
   */
  protected function opBetween($field) {
    $timezone = $this->getTimezone();
    $origin_offset = $this->getOffset($this->value['min'], $timezone);

    // Although both 'min' and 'max' values are required, default empty 'min'
    // value as UNIX timestamp 0.
    $min = (!empty($this->value['min'])) ? $this->value['min'] : '@0';
    $max = $this->value['max'];

    // If year granularity is specified, then suffix with month and day to
    // force DateTimePlus to treat the input as a proper date value.
    if ($this->options['granularity'] == 'year') {
      $min = preg_replace('/^(\d{4})$/', '$1-01-01', $min);
      $max = preg_replace('/^(\d{4})$/', '$1-01-01', $max);
    }

    // Convert to ISO format and format for query. UTC timezone is used since
    // dates are stored in UTC.
    $a = new DateTimePlus($min, new \DateTimeZone($timezone));
    $a = $this->query->getDateFormat($this->query->getDateField("'" . $this->dateFormatter->format($a->getTimestamp() + $origin_offset, 'custom', DateTimeItemInterface::DATETIME_STORAGE_FORMAT, DateTimeItemInterface::STORAGE_TIMEZONE) . "'", TRUE, $this->calculateOffset), $this->dateFormat, TRUE);
    $b = new DateTimePlus($max, new \DateTimeZone($timezone));
    $b = $this->query->getDateFormat($this->query->getDateField("'" . $this->dateFormatter->format($b->getTimestamp() + $origin_offset, 'custom', DateTimeItemInterface::DATETIME_STORAGE_FORMAT, DateTimeItemInterface::STORAGE_TIMEZONE) . "'", TRUE, $this->calculateOffset), $this->dateFormat, TRUE);

    // This is safe because we are manually scrubbing the values.
    $operator = strtoupper($this->operator);
    $field = $this->query->getDateFormat($this->query->getDateField($field, TRUE, $this->calculateOffset), $this->dateFormat, TRUE);
    $this->query->addWhereExpression($this->options['group'], "$field $operator $a AND $b");
  }

  /**
   * Override parent method, which deals with dates as integers.
   */
  protected function opSimple($field) {
    $timezone = $this->getTimezone();
    $origin_offset = $this->getOffset($this->value['value'], $timezone);

    // If year granularity is specified, then suffix with month and day to
    // force DateTimePlus to treat the input as a proper date value.
    $date_value = $this->value['value'];
    if ($this->options['granularity'] == 'year') {
      $date_value = preg_replace('/^(\d{4})$/', '$1-01-01', $date_value);
    }

    if ($this->options['granularity'] == 'month') {
      $date_value = preg_replace('/^([0-1]?[0-9]?)$/', '0000-$1-01', $date_value);
    }

    // Convert to ISO. UTC timezone is used since dates are stored in UTC.
    $value = new DateTimePlus($date_value, new \DateTimeZone($timezone));
    $value = $this->query->getDateFormat($this->query->getDateField("'" . $this->dateFormatter->format($value->getTimestamp() + $origin_offset, 'custom', DateTimeItemInterface::DATETIME_STORAGE_FORMAT, DateTimeItemInterface::STORAGE_TIMEZONE) . "'", TRUE, $this->calculateOffset), $this->dateFormat, TRUE);

    // This is safe because we are manually scrubbing the value.
    $field = $this->query->getDateFormat($this->query->getDateField($field, TRUE, $this->calculateOffset), $this->dateFormat, TRUE);
    $this->query->addWhereExpression($this->options['group'], "$field $this->operator $value");
  }

  /**
   * Custom function to remove the timezone data from date.
   *
   * @param string $date_data
   *   Date for which need to remove timezone.
   *
   * @return string
   *   Date without timezone.
   */
  public function removeTimezoneFromDate($date_data) {
    $date_data_array = explode(' ', $date_data);
    $new_date_data = [];
    if (count($date_data_array) >= 5) {
      for ($i = 0; $i < 5; $i++) {
        $new_date_data[$i] = $date_data_array[$i];
      }
      $date_data = implode(" ", $new_date_data);
    }
    return $date_data;
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposed(&$form, FormStateInterface $form_state) {
    // Do not validate value if filter is not exposed or grouped.
    if (empty($this->options['exposed']) || $this->options['is_grouped']) {
      return;
    }

    $value = &$form_state->getValue($this->options['expose']['identifier']);

    // Remove the Timezone data from date from the dates.
    $custom_filtered_dates = $value;
    if (!empty($custom_filtered_dates) && is_array($custom_filtered_dates)) {
      if ($custom_filtered_dates['min'] != 'today' && $custom_filtered_dates['max'] != '+1 month') {
        $custom_filtered_dates['min'] = $this->removeTimezoneFromDate($value['min']);
        $custom_filtered_dates['max'] = $this->removeTimezoneFromDate($value['max']);
        $value = $custom_filtered_dates;
      }
    }

    if (empty($value) && empty($this->options['expose']['required'])) {
      // Who cares what the value is if it's exposed, and non-required and
      // empty.
      return;
    }

    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id'])) {
      $operator = &$form_state->getValue($this->options['expose']['operator_id']);
    }
    else {
      $operator = $this->operator;
    }
    $operators = $this->operators();

    if ($operators[$operator]['values'] === 1) {
      $convert = new DateTimePlus($value);
      if ($convert->hasErrors()) {
        $form_state->setError($form[$this->options['expose']['identifier']], $this->t('Invalid date format.'));
      }
    }
    elseif ($operators[$operator]['values'] === 2) {
      $min = new DateTimePlus($value['min']);
      if ($min->hasErrors()) {
        $form_state->setError($form[$this->options['expose']['identifier']], $this->t('Invalid date format.'));
      }
      $max = new DateTimePlus($value['max']);
      if ($max->hasErrors()) {
        $form_state->setError($form[$this->options['expose']['identifier']], $this->t('Invalid date format.'));
      }
    }
    parent::validateExposed($form, $form_state);
  }

}
