<?php

namespace Drupal\bugherd\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The cache tags invalidator service.
   *
   * @var Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    parent::__construct($config_factory);
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bugherd.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bugherd.settings');
    $form['bugherd_project_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BugHerd Project key'),
      '#description' => $this->t('To obtain your project key login or sign up for BugHerd at @link.', [
        '@link' => Link::fromTextAndUrl('link', Url::fromUri('https://www.bugherd.com'))->toString(),
      ]),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('bugherd_project_key'),
    ];

    $form['bugherd_disable_on_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable on admin pages'),
      '#description' => $this->t('Ticking the checkbox will prevent the BugHerd button being available on admin pages'),
      '#default_value' => $config->get('bugherd_disable_on_admin'),
    ];

    $form['reporter'] = [
      '#type' => 'details',
      '#title' => $this->t('Reporter features'),
      '#open' => TRUE,
    ];

    $form['reporter']['reporter_email_autofill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autofill email'),
      '#description' => $this->t('Will autofill the email if the user is logged in into Drupal.'),
      '#default_value' => $config->get('reporter_email_autofill'),
    ];

    $form['reporter']['email_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Email required'),
      '#description' => $this->t('If checked, users will have to fill their email to submit the feedback form.'),
      '#default_value' => $config->get('email_required'),
    ];

    $form['public_feedback'] = [
      '#type' => 'details',
      '#title' => $this->t('Public feedback features'),
      '#open' => TRUE,
    ];

    $form['public_feedback']['public_feedback_message'] = [
      '#markup' => $this->t(
        'Those features work only for the public feedback. In order to enable the feedback for every visitor, follow the BugHerd documentation: @link',
        ['@link' => Link::fromTextAndUrl('Setting up the public feedback', Url::fromUri('https://support.bugherd.com/hc/en-us/articles/207581263-Setting-Up-The-Public-Feedback-Tab'))->toString()]
      ),
    ];

    $form['public_feedback']['bugherd_widget_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#description' => $this->t('Choose the default location of the BugHerd widget'),
      '#default_value' => $config->get('bugherd_widget_position'),
      '#options' => [
        'bottom-right' => 'Bottom right',
        'bottom-left' => 'Bottom left',
      ],
    ];

    $form['public_feedback']['public_feedback_labels'] = [
      '#type' => 'details',
      '#title' => $this->t('Public feedback labels'),
      '#description' => $this->t('Override the default labels of the public feedback widget.'),
      '#open' => FALSE,
    ];

    $labels_params = self::getBugherdFeebacksLabelParams();

    foreach ($labels_params as $labels_param) {
      $form['public_feedback']['public_feedback_labels'][$labels_param['key']] = [
        '#type' => 'textfield',
        '#title' => $this->t($labels_param['label']),
        '#default_value' => $config->get($labels_param['key']),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('bugherd.settings')
      ->set('bugherd_project_key', $form_state->getValue('bugherd_project_key'))
      ->set('bugherd_disable_on_admin', $form_state->getValue('bugherd_disable_on_admin'))
      ->set('reporter_email_autofill', $form_state->getValue('reporter_email_autofill'))
      ->set('email_required', $form_state->getValue('email_required'))
      ->set('bugherd_widget_position', $form_state->getValue('bugherd_widget_position'));

    $labels_params = self::getBugherdFeebacksLabelParams();
    foreach ($labels_params as $labels_param) {
      $config->set($labels_param['key'], $form_state->getValue($labels_param['key']));
    }
    $config->save();

    // Clear all pages tagged with BugHerd cache tag.
    $this->cacheTagsInvalidator->invalidateTags([
      'bugherd',
    ]);
  }

  /**
   * Get the parameters that allow to override default labels.
   *
   * @return array
   *   An array of the different parameters.
   */
  public static function getBugherdFeebacksLabelParams() {
    return [
      [
        'key' => 'tab_text',
        'label' => 'Tab key',
      ],
      [
        'key' => 'option_title_text',
        'label' => 'Option title text'
      ],
      [
        'key' => 'option_pin_text',
        'label' => 'Option Pin Text'
      ],
      [
        'key' => 'option_site_text',
        'label' => 'Option site text'
      ],
      [
        'key' => 'feedback_entry_placeholder',
        'label' => 'Feedback entry placeholder'
      ],
      [
        'key' => 'feedback_email_placeholder',
        'label' => 'Feedback Email Placeholder'
      ],
      [
        'key' => 'feedback_submit_text',
        'label' => 'Feedback Submit Text'
      ],
      [
        'key' => 'confirm_success_text',
        'label' => 'Confirm success text'
      ],
      [
        'key' => 'confirm_loading_text',
        'label' => 'Confirm loading text'
      ],
      [
        'key' => 'confirm_close_text',
        'label' => 'Confirm close text'
      ],
      [
        'key' => 'confirm_error_text',
        'label' => 'Confirm error text'
      ],
      [
        'key' => 'confirm_retry_text',
        'label' => 'Confirm retry text'
      ],
      [
        'key' => 'confirm_extension_text',
        'label' => 'Confirm extension text'
      ],
      [
        'key' => 'confirm_extension_link_text',
        'label' => 'Confirm extension link text'
      ],
    ];
  }

}
