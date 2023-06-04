<?php

namespace Drupal\Tests\footnotes\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\block_content\Entity\BlockContent;

/**
 * Contains Footnotes Filter plugin functionality tests.
 *
 * @group footnotes
 */
class FootnotesFilterPluginTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'fakeobjects',
    'footnotes',
    'node',
    'block',
    'block_content',
  ];

  /**
   * An user with permissions to proper permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Text format name.
   *
   * @var string
   */
  protected $formatName;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a filter admin user.
    $permissions = [
      'administer filters',
      'administer nodes',
      'access administration pages',
      'administer site configuration',
      'administer blocks',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->formatName = strtolower($this->randomMachineName());

    $this->drupalLogin($this->adminUser);
    $this->createTextFormat();
    $this->drupalCreateContentType(['type' => 'page']);
  }

  /**
   * Tests CKEditor Filter plugin functionality.
   */
  public function testDefaultFunctionality() {
    // Verify a title with HTML entities is properly escaped.
    $text1 = 'This is the note one.';
    $note1 = '[fn]' . $text1 . '[/fn]';
    $text2 = 'And this is the note two.';
    $note2 = "<fn>$text2</fn>";

    $body = '<p>' . $this->randomMachineName(100) . $note1 . '</p><p>' .
      $this->randomMachineName(100) . $note2 . '</p>';

    // Create a node.
    $node = $this->drupalCreateNode([
      'title' => $this->randomString(),
      'body' => [
        0 => [
          'value' => $body,
          'format' => $this->formatName,
        ],
      ],
    ]);

    $this->drupalGet('node/' . $node->id());

    // Footnote with [fn].
    $this->assertNoRaw($note1);
    $this->assertText($text1);

    // Footnote with <fn>.
    $this->assertNoRaw($note2);
    $this->assertText($text2);

    // Css file:
    $this->assertRaw('/assets/css/footnotes.css');
    // @todo currently additional settings doesn't work as expected.
    // So we don't check additional settings for now.
    // $this->createTextFormat(TRUE);
    $text1 = 'This is the note one.';
    $note1 = "[fn value='1']{$text1}[/fn]";
    $text2 = 'And this is the note two.';
    $note2 = "<fn value='1'>{$text2}</fn>";

    $body = '<p>' . $this->randomMachineName(100) . $note1 . '</p><p>' .
      $this->randomMachineName(100) . $note2 . '</p>';

    // Create a node.
    $node = $this->drupalCreateNode([
      'title' => $this->randomString(),
      'body' => [
        0 => [
          'value' => $body,
          'format' => $this->formatName,
        ],
      ],
    ]);

    $this->drupalGet('node/' . $node->id());

    // Footnote with [fn].
    $this->assertNoRaw($note1);
    $this->assertText($text1);

    // Elements with the same value should be collapsed.
    // @todo This should work only if footnotes_collapse setting is enabled.
    $this->assertNoRaw($note2);
    $this->assertNoText($text2);
  }

  /**
   * Tests footnotes footer disable functionality.
   */
  public function testFootnotesFooterDisable() {
    // Set up the footnote texts.
    $text1 = 'This is the note one.';
    $note1 = "[fn value='1']{$text1}[/fn]";
    $text2 = 'And this is the note two.';
    $note2 = "<fn value='1'>{$text2}</fn>";

    $body = '<p>' . $this->randomMachineName(100) . $note1 . '</p><p>' .
      $this->randomMachineName(100) . $note2 . '</p>';

    // Update the filter to disable to paragraph footers.
    $this->drupalGet("admin/config/content/formats/manage/" . $this->formatName);
    $edit['filters[filter_footnotes][settings][footnotes_footer_disable]'] = 1;
    $this->submitForm($edit, 'Save configuration');

    // Create a custom block type with just a body field.
    $this->drupalGet('/admin/structure/block/block-content/types/add');
    $custom_block_type = strtolower($this->randomMachineName(8));
    $edit = [
      'label' => $custom_block_type,
      'id' => $custom_block_type,
    ];
    $this->submitForm($edit, 'Save');

    // Create custom block of our custom type.
    $custom_block_name = strtolower($this->randomMachineName(8));
    $block = BlockContent::create([
      'info' => $custom_block_name,
      'type' => $custom_block_type,
      'body' => $body,
      'langcode' => 'en',
    ]);
    $block->save();

    // Set the format to the testing format.
    $block->body->format = $this->formatName;
    $block->save();

    // Add our custom block twice.
    $this->placeCustomBlock($block->uuid());
    $this->placeCustomBlock($block->uuid());

    // Add the footnotes group block.
    $this->drupalGet('admin/structure/block/add/footnotes_group');
    $edit = [
      'region' => 'footer',
      'id' => strtolower($this->randomMachineName(8)),
    ];
    $this->submitForm($edit, 'Save block');

    // Create a node.
    $node = $this->drupalCreateNode([
      'title' => $this->randomString(),
      'body' => [
        0 => [
          'value' => $body,
          'format' => $this->formatName,
        ],
      ],
    ]);

    $this->drupalGet('node/' . $node->id());

    // Footnote with [fn].
    $this->assertSession()->responseNotContains($note1);
    $this->assertSession()->pageTextContains($text1);
    $this->assertSession()->elementsCount('css', '.footnotes .footnote', 1);

    // Elements with the same value should be collapsed.
    // @todo This should work only if footnotes_collapse setting is enabled.
    $this->assertSession()->responseNotContains($note2);
    $this->assertSession()->pageTextNotContains($text2);
  }

  /**
   * Create a new text format.
   *
   * @param bool $additional_settings
   *   Indicates if filter settings should be enabled.
   */
  protected function createTextFormat($additional_settings = FALSE) {
    $button_groups = json_encode([
      [
        [
          'name' => 'Tools',
          'items' => ['Source', 'footnotes'],
        ],
      ],
    ]);

    $edit = [
      'format' => $this->formatName,
      'name' => $this->formatName,
      'roles[' . AccountInterface::AUTHENTICATED_ROLE . ']' => TRUE,
      'editor[editor]' => 'ckeditor',
      'filters[filter_footnotes][status]' => TRUE,
    ];
    $this->drupalGet("admin/config/content/formats/add");
    // Keep the "CKEditor" editor selected and click the "Configure" button.
    $this->drupalPostForm(NULL, $edit, 'editor_configure');
    $edit['editor[settings][toolbar][button_groups]'] = $button_groups;
    $edit['filters[filter_footnotes][settings][footnotes_collapse]'] = $button_groups;
    if ($additional_settings) {
      $edit['filters[filter_footnotes][settings][footnotes_collapse]'] = 1;
      $edit['filters[filter_footnotes][settings][footnotes_html]'] = 1;
    }
    $this->drupalPostForm(NULL, $edit, $this->t('Save configuration'));
    $this->assertText($this->t('Added text format @format.', ['@format' => $this->formatName]));
  }

  /**
   * Place our custom block.
   *
   * @param string $block_uuid
   *   The UUID of the block.
   */
  private function placeCustomBlock($block_uuid) {
    $this->drupalGet('admin/structure/block/add/block_content:' . $block_uuid);
    $edit = [
      'region' => 'content',
      'id' => strtolower($this->randomMachineName(8)),
    ];
    $this->submitForm($edit, 'Save block');
  }

}
