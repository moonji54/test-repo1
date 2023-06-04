<?php

namespace Drupal\colorbox\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\colorbox\ElementAttachmentInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

// Use Drupal\Core\Utility\LinkGeneratorInterface;.

/**
 * Plugin for responsive image formatter.
 *
 * @FieldFormatter(
 *   id = "colorbox_responsive",
 *   module = "colorbox",
 *   label = @Translation("Colorbox Responsive"),
 *   field_types = {
 *     "image",
 *   },
 * )
 */
class ColorboxResponsiveFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var EntityStorageInterface
   */
  protected $responsiveImageStyleStorage;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * Constructs a ColorboxResponsiveFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $responsive_image_style_storage
   *   The responsive image style storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\colorbox\ElementAttachmentInterface $attachment
   *   Allow the library to be attached to the page.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler services.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $responsive_image_style_storage, EntityStorageInterface $image_style_storage, ElementAttachmentInterface $attachment, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->responsiveImageStyleStorage = $responsive_image_style_storage;
    $this->imageStyleStorage = $image_style_storage;
    $this->attachment = $attachment;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('responsive_image_style'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('colorbox.attachment'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'colorbox_responsive_node_style' => '',
        'colorbox_image_style' => '',
        'colorbox_responsive_image_style' => '',
        'colorbox_gallery' => 'post',
        'colorbox_gallery_custom' => '',
        'colorbox_caption' => 'auto',
        'colorbox_caption_custom' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure Image Styles'),
      Url::fromRoute('entity.image_style.collection')
    );
    $responsive_image_options = [];
    $responsive_image_styles = $this->responsiveImageStyleStorage->loadMultiple();
    if ($responsive_image_styles && !empty($responsive_image_styles)) {
      foreach ($responsive_image_styles as $machine_name => $responsive_image_style) {
        if ($responsive_image_style->hasImageStyleMappings()) {
          $responsive_image_options[$machine_name] = $responsive_image_style->label();
        }
      }
    }

    $element['colorbox_responsive_node_style'] = [
      '#title' => $this->t('Responsive image style for content'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('colorbox_responsive_node_style'),
      '#required' => TRUE,
      '#options' => $responsive_image_options,
      '#description' => [
        // '#markup' => $this->linkGenerator->generate($this->t('Configure Responsive Image Styles'), new Url('entity.responsive_image_style.collection')),
        '#access' => $this->currentUser->hasPermission('administer responsive image styles'),
      ],
    ];
    $element['colorbox_image_style'] = [
      '#title' => $this->t('Image style for Colorbox'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('colorbox_image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
          '#access' => $this->currentUser->hasPermission('administer image styles'),
        ],
    ];
    $element['colorbox_responsive_image_style'] = [
      '#title' => $this->t('Responsive image style for Colorbox'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('colorbox_responsive_image_style'),
      '#required' => TRUE,
      '#options' => $responsive_image_options,
      '#description' => [
        // '#markup' => $this->linkGenerator->generate($this->t('Configure Responsive Image Styles'), new Url('entity.responsive_image_style.collection')),
        '#access' => $this->currentUser->hasPermission('administer responsive image styles'),
      ],
    ];

    $gallery = [
      'post' => $this->t('Per post gallery'),
      'page' => $this->t('Per page gallery'),
      'field_post' => $this->t('Per field in post gallery'),
      'field_page' => $this->t('Per field in page gallery'),
      'custom' => $this->t('Custom (with tokens)'),
      'none' => $this->t('No gallery'),
    ];
    $element['colorbox_gallery'] = [
      '#title' => $this->t('Gallery (image grouping)'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('colorbox_gallery'),
      '#options' => $gallery,
      '#description' => $this->t('How Colorbox should group the image galleries.'),
    ];
    $element['colorbox_gallery_custom'] = [
      '#title' => $this->t('Custom gallery'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('colorbox_gallery_custom'),
      '#description' => $this->t('All images on a page with the same gallery value (rel attribute) will be grouped together. It must only contain lowercase letters, numbers, and underscores.'),
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name$="[settings_edit_form][settings][colorbox_gallery]"]' => ['value' => 'custom'],
        ],
      ],
    ];
    if ($this->moduleHandler->moduleExists('token')) {

      $entity_type = '';

      if (isset($form['#entity_type']) && !empty($form['#entity_type'])) {
        $entity_type = $form['#entity_type'];
      }

      $element['colorbox_token_gallery'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Replacement patterns'),
        '#theme' => 'token_tree_link',
        '#token_types' => [$entity_type, 'file'],
        '#states' => [
          'visible' => [
            ':input[name$="[settings_edit_form][settings][colorbox_gallery]"]' => ['value' => 'custom'],
          ],
        ],
      ];
    }
    else {
      $element['colorbox_token_gallery'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Replacement patterns'),
        '#description' => '<strong class="error">' . $this->t('For token support the <a href="@token_url">token module</a> must be installed.', ['@token_url' => 'http://drupal.org/project/token']) . '</strong>',
        '#states' => [
          'visible' => [
            ':input[name$="[settings_edit_form][settings][colorbox_gallery]"]' => ['value' => 'custom'],
          ],
        ],
      ];
    }

    $caption = [
      'auto' => $this->t('Automatic'),
      'title' => $this->t('Title text'),
      'alt' => $this->t('Alt text'),
      'entity_title' => $this->t('Content title'),
      'custom' => $this->t('Custom (with tokens)'),
      'none' => $this->t('None'),
    ];
    $element['colorbox_caption'] = [
      '#title' => $this->t('Caption'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('colorbox_caption'),
      '#options' => $caption,
      '#description' => $this->t('Automatic will use the first non-empty value out of the title, the alt text and the content title.'),
    ];
    $element['colorbox_caption_custom'] = [
      '#title' => $this->t('Custom caption'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('colorbox_caption_custom'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings_edit_form][settings][colorbox_caption]"]' => ['value' => 'custom'],
        ],
      ],
    ];
    if ($this->moduleHandler->moduleExists('token')) {

      $entity_type = '';

      if (isset($form['#entity_type']) && !empty($form['#entity_type'])) {
        $entity_type = $form['#entity_type'];
      }

      $element['colorbox_token_caption'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Replacement patterns'),
        '#theme' => 'token_tree_link',
        '#token_types' => [$entity_type, 'file'],
        '#states' => [
          'visible' => [
            ':input[name$="[settings_edit_form][settings][colorbox_caption]"]' => ['value' => 'custom'],
          ],
        ],
      ];
    }
    else {
      $element['colorbox_token_caption'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Replacement patterns'),
        '#description' => '<strong class="error">' . $this->t('For token support the <a href="@token_url">token module</a> must be installed.', ['@token_url' => 'http://drupal.org/project/token']) . '</strong>',
        '#states' => [
          'visible' => [
            ':input[name$="[settings_edit_form][settings][colorbox_caption]"]' => ['value' => 'custom'],
          ],
        ],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines.
    // their styles in code.

    $responsive_image_styles = $this->responsiveImageStyleStorage->loadMultiple();

    if (isset($responsive_image_styles[$this->getSetting('colorbox_responsive_node_style')])) {
      $summary[] = $this->t('Responsive content image style: @style', ['@style' => $responsive_image_styles[$this->getSetting('colorbox_responsive_node_style')]->label()]);
    }
    // elseif ($this->getSetting('colorbox_responsive_node_style') == 'hide') {
    //   $summary[] = $this->t('Responsive content image style: Hide');
    // }
    else {
      $summary[] = $this->t('Responsive content image style: Original image');
    }

    if (isset($image_styles[$this->getSetting('colorbox_image_style')])) {
      $summary[] = $this->t('Colorbox image style: @style', ['@style' => $image_styles[$this->getSetting('colorbox_image_style')]]);
    }

    if (isset($responsive_image_styles[$this->getSetting('colorbox_responsive_image_style')])) {
      $summary[] = $this->t('Colorbox responsive image style: @style', ['@style' => $responsive_image_styles[$this->getSetting('colorbox_responsive_image_style')]->label()]);
    }
    else {
      $summary[] = $this->t('Colorbox responsive image style: Original image');
    }

    $gallery = [
      'post' => $this->t('Per post gallery'),
      'page' => $this->t('Per page gallery'),
      'field_post' => $this->t('Per field in post gallery'),
      'field_page' => $this->t('Per field in page gallery'),
      'custom' => $this->t('Custom (with tokens)'),
      'none' => $this->t('No gallery'),
    ];
    if ($this->getSetting('colorbox_gallery')) {
      $summary[] = $this->t('Colorbox gallery type: @type', ['@type' => $gallery[$this->getSetting('colorbox_gallery')]]) . ($this->getSetting('colorbox_gallery') == 'custom' ? ' (' . $this->getSetting('colorbox_gallery_custom') . ')' : '');
    }

    $caption = [
      'auto' => $this->t('Automatic'),
      'title' => $this->t('Title text'),
      'alt' => $this->t('Alt text'),
      'entity_title' => $this->t('Content title'),
      'custom' => $this->t('Custom (with tokens)'),
      'none' => $this->t('None'),
    ];

    if ($this->getSetting('colorbox_caption')) {
      $summary[] = $this->t('Colorbox caption: @type', ['@type' => $caption[$this->getSetting('colorbox_caption')]]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    // Collect cache tags to be added for each item in the field.
    $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('colorbox_responsive_node_style'));
    $image_styles_to_load = [];
    $cache_tags = [];
    if ($responsive_image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $responsive_image_style->getCacheTags());
      $image_styles_to_load = $responsive_image_style->getImageStyleIds();
    }

    $image_styles = $this->imageStyleStorage->loadMultiple($image_styles_to_load);
    foreach ($image_styles as $image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
    }

    foreach ($files as $delta => $file) {
      $settings['style_name'] = $settings['colorbox_responsive_node_style'];
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $elements[$delta] = [
        '#theme' => 'colorbox_responsive_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#entity' => $items->getEntity(),
        '#settings' => $settings,
        '#cache' => [
          'tags' => $cache_tags,
        ],
      ];
    }

    // Attach the Colorbox JS and CSS.
    if ($this->attachment->isApplicable()) {
      $this->attachment->attach($elements);
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $style_ids = [];
    $style_ids[] = $this->getSetting('colorbox_responsive_node_style');
    $style_ids[] = $this->getSetting('colorbox_responsive_image_style');
    $style_ids[] = $this->getSetting('colorbox_image_style');
    /** @var \Drupal\image\ImageStyleInterface $style */
    foreach ($style_ids as $style_id) {
      if ($style_id && $style = ResponsiveImageStyle::load($style_id)) {
        // Add the responsive image style as dependency.
        $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
      }
      if ($style_id && $style = ImageStyle::load($style_id)) {
        // Add the responsive image style as dependency.
        $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
      }
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return \Drupal::moduleHandler()->moduleExists('responsive_image');
  }

}
