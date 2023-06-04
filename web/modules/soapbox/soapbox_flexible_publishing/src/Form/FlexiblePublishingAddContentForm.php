<?php

namespace Drupal\soapbox_flexible_publishing\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Flexible publishing add content form.
 */
class FlexiblePublishingAddContentForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flexible_publishing_add_content';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $block_configuration = $form_state->getBuildInfo()['args'][0];

    // Get message and remove double spaces.
    $message = $block_configuration['message'];
    $message = preg_replace('/\s+/', ' ', $message);

    // Split the string on type and template.
    $message_parts = preg_split('/(\[type\]|\[template\])/', $message);
    $prefix = $suffix = $mid = '';

    // If type isn't at the start of the message, save prefix.
    if (strpos($message, '[type]') !== 0 && count($message_parts)) {
      $prefix = array_shift($message_parts);
    }

    // If type and template aren't adjacent to each other, save mid.
    if (strpos($message, '[type][template]') === FALSE && strpos($message, '[type] [template]') === FALSE && count($message_parts)) {
      $mid = array_shift($message_parts);
    }

    // If template is not at the end of the string, save suffix.
    if (str_ends_with($message, '[type]') === FALSE && count($message_parts)) {
      $suffix = array_shift($message_parts);
    }

    $types = NodeType::loadMultiple();
    /** @var \Drupal\Core\Entity\EntityDisplayRepository $entity_display */
    $entity_display = \Drupal::service('entity_display.repository');

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container-inline', 'c-add-content'],
      ],
    ];

    // Prefix text.
    $form['container']['prefix'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['c-add-content__text'],
      ],
    ];
    $form['container']['prefix']['text'] = [
      '#type' => 'html_tag',
      '#tag' => 'h4',
      '#value' => $prefix,
    ];

    // Content type selection.
    $options = [];
    foreach ($types as $type) {
      $options[$type->id()] = $type->label();
    }
    $form['container']['node_type'] = [
      '#type' => 'select',
      '#options' => $options,
    ];

    // Mid text.
    $form['container']['mid'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['c-add-content__text'],
      ],
    ];
    $form['container']['mid']['text'] = [
      '#type' => 'html_tag',
      '#tag' => 'h4',
      '#value' => $mid,
    ];

    // Template selection.
    foreach ($types as $type) {
      $settings = $type->getThirdPartySettings('soapbox_flexible_publishing');
      $options = $entity_display->getFormModeOptionsByBundle('node', $type->id());
      if (isset($settings['use_templates']) && $settings['use_templates']) {
        $options = array_intersect_key($options, $settings['form_modes_as_templates']);
      }
      else {
        $options = array_intersect_key($options, ['default' => 'default']);
        $options['default'] = $this->t('Default') . ' ' . $type->label();
      }
      $form['container'][$type->id() . '_template'] = [
        '#type' => 'select',
        '#options' => $options,
        '#states' => [
          'visible' => [
            ':input[name="node_type"]' => ['value' => $type->id()],
          ],
        ],
      ];
    }

    // Suffix text.
    $form['container']['suffix'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['c-add-content__text'],
      ],
    ];
    $form['container']['suffix']['text'] = [
      '#type' => 'html_tag',
      '#tag' => 'h4',
      '#value' => $suffix,
    ];
    $form['container']['button'] = [
      '#type' => 'submit',
      '#value' => $block_configuration['button'],
      '#attributes' => [
        'class' => ['button--primary'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $type = $form_state->getValue('node_type');
    $template = $form_state->getValue($type . '_template');
    $route_name = 'node.add';
    if ($template !== 'default') {
      $route_name .= '.' . $template;
    }
    $url = new Url($route_name, ['node_type' => $type]);
    $response = new RedirectResponse($url->toString(), 302);
    $response->send();
  }

}
