<?php

namespace Drupal\soapbox_flexible_publishing\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\soapbox_flexible_publishing\Form\FlexiblePublishingAddContentForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Flexible publishing add content block.
 *
 * @Block(
 *  id = "flexible_publishing_add_content_block",
 *  admin_label = @Translation("Flexible publishing add content block"),
 * )
 */
class FlexiblePublishingAddContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'message' => 'I want to add an [type] item using the [template] template.',
      'button' => 'Go',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message to show'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Your message must contain [type] and [template] which will be automatically replaced with the relevant dropdown select inputs.'),
    ];
    if (isset($this->configuration['message']) && !empty($this->configuration['message'])) {
      $form['message']['#default_value'] = $this->configuration['message'];
    }

    $form['button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button label'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];
    if (isset($this->configuration['button']) && !empty($this->configuration['button'])) {
      $form['button']['#default_value'] = $this->configuration['button'];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $message = $form_state->getValue('message');
    if (strpos($message, '[type]') === FALSE) {
      $form_state->setError($form['message'], $this->t('Your message must contain "[type]".'));
    }
    if (strpos($message, '[template]') === FALSE) {
      $form_state->setError($form['message'], $this->t('Your message must contain "[template]".'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['message'] = $form_state->getValue('message');
    $this->configuration['button'] = $form_state->getValue('button');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm(FlexiblePublishingAddContentForm::class, $this->configuration);
  }

}
