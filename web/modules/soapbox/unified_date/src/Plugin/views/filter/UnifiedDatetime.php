<?php

namespace Drupal\unified_date\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\Date;

/**
 * Content type filter date filter for unified_datetime field.
 *
 * It's a copy of default date handler but with custom group form.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("unified_datetime")
 */
class UnifiedDatetime extends Date {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['group_info']['contains']['custom_range']['default'] = FALSE;
    $options['expose']['contains']['min_label'] = ['default' => ''];
    $options['expose']['contains']['max_label'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    if ($this->isAGroup()) {
      $form['group_info']['custom_range'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Add custom range'),
        '#default_value' => $this->options['group_info']['custom_range'],
      ];
    }
    if ($this->isExposed()) {
      $form['expose']['min_label'] = [
        '#type' => 'textfield',
        '#default_value' => $this->options['expose']['min_label'],
        '#title' => $this->t('Min label'),
        '#size' => 40,
      ];
      $form['expose']['max_label'] = [
        '#type' => 'textfield',
        '#default_value' => $this->options['expose']['max_label'],
        '#title' => $this->t('Max label'),
        '#size' => 40,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    if ($form_state->get('exposed')
      && in_array($this->operator, $this->operatorValues(2))) {
      $form['value']['min']['#title'] = empty($this->options['expose']['min_label'])
        ? $form['value']['min']['#title']
        : $this->options['expose']['min_label'];

      $form['value']['max']['#title'] = empty($this->options['expose']['max_label'])
        ? $form['value']['max']['#title']
        : $this->options['expose']['max_label'];
    }
    else {
      $form['value']['type']['#options']['strtotime'] = $this->t('Strtotime');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function groupForm(&$form, FormStateInterface $form_state) {
    if (!empty($this->options['group_info']['optional'])
      && !$this->multipleExposedInput()) {
      $groups = ['All' => $this->t('- Any -')];
    }
    foreach ($this->options['group_info']['group_items'] as $id => $group) {
      if (!empty($group['title'])) {
        $groups[$id] = $id != 'All' ? $this->t('@group_title', [
          '@group_title' => $group['title'],
        ]) : $group['title'];
      }
    }

    if (count($groups)) {

      $value = $this->options['group_info']['identifier'];
      $wrapper = $value . '_wrapper';
      $this->buildValueWrapper($form, $wrapper);

      $form[$wrapper][$value]['#tree'] = TRUE;
      $form[$wrapper][$value]['group'] = [
        '#type' => $this->options['group_info']['widget'],
        '#default_value' => $this->group_info,
        '#options' => $groups,
      ];
      if (!empty($this->options['group_info']['multiple'])) {
        if (count($groups) < 5) {
          $form[$wrapper][$value]['group']['#type'] = 'checkboxes';
        }
        else {
          $form[$wrapper][$value]['group']['#type'] = 'select';
          $form[$wrapper][$value]['group']['#size'] = 5;
          $form[$wrapper][$value]['group']['#multiple'] = TRUE;
        }
        unset($form[$wrapper][$value]['group']['#default_value']);
        $user_input = $form_state->getUserInput();
        if (empty($user_input)) {
          $user_input[$value] = $this->group_info;
          $form_state->setUserInput($user_input);
        }
      }

      $this->options['expose']['label'] = '';

      $form[$wrapper][$value]['group']['#options']['custom'] = $this->t('Specific dates');

      if (!empty($this->options['max_label'])) {
        $form['value']['max']['#title'] = $this->options['max_label'];
      }

      $form[$wrapper][$value]['min'] = [
        '#type' => 'textfield',
        '#title' => empty($this->options['expose']['min_label']) ? $this->t('Min') : $this->options['expose']['min_label'],
        '#size' => 30,
        '#default_value' => $this->value['min'],
        '#states' => [
          'visible' => [
            ":input[name='{$value}[group]']" => ['value' => 'custom'],
          ],
        ],
      ];
      $form[$wrapper][$value]['max'] = [
        '#type' => 'textfield',
        '#title' => empty($this->options['expose']['max_label']) ? $this->t('Max') : $this->options['expose']['max_label'],
        '#size' => 30,
        '#default_value' => $this->value['max'],
        '#states' => [
          'visible' => [
            ":input[name='{$value}[group]']" => ['value' => 'custom'],
          ],
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }
    if ($this->isAGroup()) {
      $identifier = $this->options['group_info']['identifier'];
      if (isset($input[$identifier]['group']) && $input[$identifier]['group'] === 'custom'
        && (!empty($input[$identifier]['min']) || !empty($input[$identifier]['max']))) {
        return TRUE;
      }
      elseif (isset($input[$identifier]['group'])) {
        $input[$identifier] = $input[$identifier]['group'];
      }
    }

    return parent::acceptExposedInput($input);
  }

  /**
   * {@inheritdoc}
   */
  public function convertExposedInput(&$input, $selected_group_id = NULL) {
    if ($this->isAGroup()) {
      $identifier = $this->options['group_info']['identifier'];
      if ($input[$identifier]['group'] === 'custom') {
        if (!empty($input[$identifier]['min']) && !empty($input[$identifier]['max'])) {
          $this->options['group_info']['group_items']['custom'] = [
            'operator' => 'between',
            'value' => [
              'min' => $input[$identifier]['min'],
              'max' => $input[$identifier]['max'],
            ],
          ];
        }
        elseif (!empty($input[$identifier]['min']) || !empty($input[$identifier]['max'])) {
          $is_min = !empty($input[$identifier]['min']);
          $this->options['group_info']['group_items']['custom'] = [
            'operator' => $is_min ? '>' : '<',
            'value' => $is_min ? $input[$identifier]['min'] : $input[$identifier]['max'],
          ];
        }
      }
      $input[$identifier] = $input[$identifier]['group'];
    }
    return parent::convertExposedInput($input, $selected_group_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function opBetween($field) {
    if ($this->value['type'] === 'strtotime') {
      $a = intval(strtotime($this->value['min']));
      $b = intval(strtotime($this->value['max']));
      $operator = strtoupper($this->operator);
      $this->query->addWhereExpression($this->options['group'], "$field $operator $a AND $b");
    }
    else {
      parent::opBetween($field);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {
    if ($this->value && isset($this->value['type']) && $this->value['type'] === 'strtotime') {
      $value = intval(strtotime($this->value['value']));
      $this->query->addWhereExpression($this->options['group'], "$field $this->operator $value");
    }
    else {
      parent::opSimple($field);
    }
  }

}
