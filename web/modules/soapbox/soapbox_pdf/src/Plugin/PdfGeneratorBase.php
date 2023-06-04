<?php

namespace Drupal\soapbox_pdf\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\soapbox_pdf\Form\NodeTypeFormPdfPartialForm;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for PDF generator plugins.
 */
abstract class PdfGeneratorBase extends PluginBase implements PdfGeneratorInterface, ContainerFactoryPluginInterface {

  const CLIENT_URL = 'https://chrome.browserless.io/pdf';

  const REQUEST_TYPES_GENERATE = 'generate';

  use LoggerChannelTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * A Guzzle client object.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The token replacement instance.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The site settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Constructs a BlockRegion plugin instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   A Guzzle client object.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Utility\Token $token
   *   The token replacement instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Site\Settings|null $settings
   *   The site settings.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandlerInterface $module_handler,
    ClientFactory $http_client_factory,
    FileSystemInterface $file_system,
    Token $token,
    ConfigFactoryInterface $config_factory,
    Settings $settings = NULL
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->httpClientFactory = $http_client_factory;
    $this->fileSystem = $file_system;
    $this->token = $token;
    $this->configFactory = $config_factory;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $module_handler = $container->get('module_handler');
    $http_client_factory = $container->get('http_client_factory');
    $file_system = $container->get('file_system');
    $token = $container->get('token');
    $config_factory = $container->get('config.factory');
    $settings = $container->get('settings');
    return new static($configuration, $plugin_id, $plugin_definition, $module_handler, $http_client_factory, $file_system, $token, $config_factory, $settings);
  }

  /**
   * Static method to run own plugin step method.
   *
   * If run any methods from this plugin type in the batch directly, you will
   * get the error about dependencies which cannot be serialized. This static
   * method helps to solver this problem. The two first arguments should be
   * plugin id and method name.
   *
   * @param mixed ...$args
   *   Method arguments.
   *
   * @todo Find another way to run method directly from plugin object instead of
   * generating service object for each step.
   */
  public static function runPluginStep(...$args) {
    [$plugin_id, $method] = $args;
    $manager = \Drupal::service('plugin.manager.pdf_generator');
    $pdf_provider = $manager->createInstance($plugin_id);
    call_user_func_array([$pdf_provider, $method], array_slice($args, 2));
  }

  /**
   * {@inheritdoc}
   */
  public function saveFinalPdf(NodeInterface $node, ?UserInterface $user = NULL) {
    // Move temporary file to permanent location.
    $temp_location = $this->getTemporaryFilePath($node);
    $permanent_location = $this->getFilePath($node);
    $this->fileSystem->prepareDirectory($permanent_location, FileSystemInterface::CREATE_DIRECTORY);

    // Create new file and media entities and save into node field.
    $field_name = $this->getSettings($node->getType())['field_save'];
    if (!$node->get($field_name)->isEmpty() &&
      $node->get($field_name)->entity &&
      $node->get($field_name)->entity instanceof MediaInterface) {
      $media = $node->get($field_name)->entity;
      $source_field = $media->getSource()->getConfiguration()['source_field'];

      // Remove old file from media object.
      if (!$media->get($source_field)->isEmpty()
        && $old_file = $media->get($source_field)->entity) {
        $old_file = $media->get($source_field)->entity;
        $old_path = substr($old_file->createFileUrl(), 1);
        $old_file->delete();
      }

      $file = $this->createFile($temp_location, $permanent_location . '/' . $this->getFileName($node));
      $file->save();

      // @todo Create an event type and move it there.
      if (isset($old_path) && $this->moduleHandler->moduleExists('redirect')) {
        $new_file_path = substr($file->createFileUrl(), 1);
        $redirect_manager = \Drupal::service('redirect.repository');
        foreach ($redirect_manager->findBySourcePath($old_path) as $redirect) {
          $redirect->setSource($new_file_path);
          $redirect->save();
        }
      }

      $media->set($source_field, ['target_id' => $file->id()]);
      $media->save();
    }
    else {
      $file = $this->createFile($temp_location, $permanent_location . '/' . $this->getFileName($node));
      $file->save();

      $media = $this->createMedia($node, $file);
      if (isset($user)) {
        $media->set('uid', $user->id());
      }
      $media->save();
      $node->set($field_name, ['target_id' => $media->id()]);
      $node->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFinalPdf(NodeInterface $node) {
    $field_name = $this->getSettings($node->getType())['field_save'];
    if (!$node->get($field_name)->isEmpty()) {
      $media = $node->get($field_name)->entity;
      if ($media && $media instanceof MediaInterface) {
        $source_field = $media->getSource()->getConfiguration()['source_field'];
        if (!$media->get($source_field)->isEmpty()) {
          return $media->get($source_field)->entity;
        }
      }
    }

    return NULL;
  }

  /**
   * Add link in batch context.
   */
  public function generateBatchPdfLink($node, &$context) {
    if ($file = $this->getFinalPdf($node)) {
      $context['results']['file_link'] = $file->createFileUrl();
    }
  }

  /**
   * Generate temporary file path base on node id.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object for generating unique temporary path.
   *
   * @return string
   *   Temporary file path.
   */
  protected function getTemporaryFilePath(NodeInterface $node) {
    return $this->fileSystem->getTempDirectory() . "/temp-file-{$node->id()}.pdf";
  }

  /**
   * Generate file name base on node title.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object for generating filename.
   *
   * @return string
   *   Generated filename.
   */
  protected function getFileName(NodeInterface $node) {
    $file_name = $this->token->replace('[node:title] ([node:nid])', ['node' => $node]);
    $file_name = str_replace(' ', '-', $file_name);
    $file_name = strtolower($file_name);
    return "{$file_name}.pdf";
  }

  /**
   * Generate file path base on field settings.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object for getting field.
   *
   * @return string
   *   Generated file path.
   */
  protected function getFilePath(NodeInterface $node) {
    $media = $this->createMedia($node);

    $source_field = $media->getSource()->getConfiguration()['source_field'];
    $settings = $media->get($source_field)->getSettings();

    $destination = trim($settings['file_directory'], '/');

    // Replace tokens. As the tokens might contain HTML we convert it to plain
    // text.
    $destination = PlainTextOutput::renderFromHtml($this->token->replace($destination));
    return $settings['uri_scheme'] . '://' . $destination;
  }

  /**
   * Create media object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node object to get settings.
   * @param \Drupal\file\FileInterface|null $file
   *   PDF file object to save into new media.
   *
   * @return \Drupal\media\Entity\MediaInterface
   *   Media object.
   */
  protected function createMedia(NodeInterface $node, ?FileInterface $file = NULL) {
    $field_name = $this->getSettings($node->getType())['field_save'];
    $handler_settings = $node->get($field_name)->getSetting('handler_settings');
    $media_pdf_bundles = NodeTypeFormPdfPartialForm::getAvailableMediaBundles();

    // Get first PDF media bundle.
    foreach ($handler_settings['target_bundles'] as $bundle) {
      if (in_array($bundle, $media_pdf_bundles)) {
        break;
      }
      $bundle = NULL;
    }
    if (!isset($bundle)) {
      throw new \Exception("The media field doesn't have any media PDF type.");
    }

    $media = Media::create(['bundle' => $bundle]);
    if (isset($file)) {
      $source_field = $media->getSource()->getConfiguration()['source_field'];
      $media->set($source_field, ['target_id' => $file->id()]);
    }

    return $media;
  }

  /**
   * Move temporary file to permanent folder and create file object.
   *
   * @param string $temp_location
   *   Temporary location.
   * @param string $dest
   *   Destination path.
   *
   * @return \Drupal\file\FileInterface
   *   Created file object.
   */
  protected function createFile($temp_location, $dest) {
    $uri = $this->fileSystem->move($temp_location, $dest, FileSystemInterface::EXISTS_RENAME);
    $file = File::create([
      'filename' => basename($dest),
      'status' => FILE_STATUS_PERMANENT,
      'uri' => $uri,
    ]);
    return $file;
  }

  /**
   * Get PDF settings for node type.
   */
  protected function getSettings($node_type) {
    $content_types = $this->configFactory->get('soapbox_pdf.pdf_settings')
      ->get('content_types') ?? [];
    return $content_types[$node_type] ?? [];
  }

  /**
   * Generate link to node PDF view mode for generating PDF.
   */
  protected function getNodePdfPage($node) {
    $node_url = Url::fromRoute('soapbox_pdf.node_pdf_page_controller', [
      'node' => $node->id(),
    ])
      ->setOption('language', $node->language())
      ->setAbsolute()
      ->toString();

    return $node_url;
  }

  /**
   * Do request to API.
   *
   * @param string $url
   *   The request URL.
   * @param array $options
   *   Array with options.
   * @param string $type
   *   Use to identify request type in alter function.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   Response object.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function doRequest($url, array $options, $type) {
    $this->moduleHandler->alter('soapbox_pdf_request', $url, $options, $type);

    try {
      $client = $this->httpClientFactory->fromOptions([
        'headers' => [
          'authorization' => 'Basic ' . base64_encode($this->settings->get('browserless_api_key')),
        ],
      ]);
      $response = $client->request('POST', $url, $options);
      if ($response->getStatusCode() !== 200) {
        $message = 'API call failed with message: ' . $response->getReasonPhrase();
        $this->getLogger('soapbox_pdf')->error($message);
      }
      return $response;
    }
    catch (\Exception $e) {
      $this->getLogger('soapbox_pdf')
        ->error('Caught exception: ' . $e->getMessage());
    }
  }

}
