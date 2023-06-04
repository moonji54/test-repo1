<?php

namespace Drupal\pdf_generator;

use Dompdf\Dompdf;
use Dompdf\Options;
use Dompdf\Adapter\CPDF;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\Renderer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Render\Markup;

/**
 * Defines the DomPdfGenerator service to be used by another services.
 */
class DomPdfGenerator {

  use StringTranslationTrait;

  /**
   * Options for dompdf.
   *
   * @var \Dompdf\Options
   */
  protected $options;

  /**
   * Dompdf library.
   *
   * @var \Dompdf\Dompdf
   */
  protected $dompdf;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The object renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Protected requestStack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;


  /**
   * Constructs a new object.
   *
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The object renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   */
  public function __construct(
    Renderer $renderer,
    RequestStack $request_stack,
    ModuleHandlerInterface $module_handler
  ) {
    $this->renderer = $renderer;
    $this->requestStack = $request_stack;
    $this->moduleHandler = $module_handler;
    $this->options = new Options();
    $this->options->set('enable_css_float', TRUE);
    $this->options->set('enable_html5_parser', TRUE);
    $this->options->set('enable_remote', TRUE);
    $this->options->set('defaultFont', 'Times');
    $this->options->set('chroot', DRUPAL_ROOT);
  }

  /**
   * Render usin dompdf with given options.
   *
   * @param string $title
   *   The title of the pdf.
   * @param array $content
   *   The build array to be rendered.
   * @param bool $preview
   *   If enabled the html will be printed without render the pdf.
   * @param array $options
   *   The options array to perform pdf.
   * @param string $pageSize
   *   The size of the page.
   * @param string $disposition
   *   The disposition of the page, portrait or landscape.
   * @param string $cssText
   *   Text to load additional css.
   * @param string $cssPath
   *   Path to load additional css.
   * @param bool $forceDownload
   *   If force download by Content-Disposition header is required.
   */
  public function getResponse($title, array $content, $preview = FALSE, array $options = [], $pageSize = 'A4', $showPagination = 0, $paginationX = 0, $paginationY = 0, $disposition = 'portrait', $cssText = NULL, $cssPath = NULL, $forceDownload = TRUE) {
    $request = $this->requestStack->getCurrentRequest();
    $base_url = $request->getSchemeAndHttpHost();

    // By default we load some options.
    // The user can override these options.
    foreach ($options as $key => $option) {
      $this->options->set($key, $option);
    }

    // Dompdf needs to be initialized with custom options if they are supplied.
    $this->dompdf = new Dompdf($this->options);
    $path = $this->moduleHandler->getModule('pdf_generator')->getPath();
    $css = file_get_contents($path . '/css/pdf.css');
    // Add inline css from text.
    if (!empty($cssText)) {
      $css .= "\r\n";
      $css .= $cssText;
    }
    // Add inline css from file.
    if (!empty($cssPath) && file_exists($cssPath)) {
      $css .= "\r\n";
      $css .= file_get_contents($cssPath);
    }

    $css = str_replace("url('/", "url('" . $base_url . "/", $css);
    $build = [
      '#theme' => 'pdf_generator_print',
      '#css' => Markup::create($css),
      '#content' => $content,
      '#title' => $title,
    ];

    if ($preview) {
      return $build;
    }
    $html = (string) $this->renderer->renderRoot($build);

    $html = urldecode($html);
    $html = str_replace('src="' . $base_url . '/', 'src="/', $html);
    $html = str_replace('href="/', 'href="' . $base_url . '/', $html);
    $html = str_replace('src="/', 'src="' . DRUPAL_ROOT . '/', $html);

    $this->dompdf->setOptions($this->options);
    $this->dompdf->loadHtml($html);
    $this->dompdf->setPaper($pageSize, $disposition);

    $this->moduleHandler->alter('pdf_generator_pre_render', $this->dompdf);

    $this->dompdf->render();

    switch ($showPagination) {
      case 1:
        $canvas = $this->dompdf->getCanvas();
        $canvas->page_text($paginationX, $paginationY, "{PAGE_NUM}", NULL, 12);
        $this->dompdf->setCanvas($canvas);
        break;

      case 2:
        $canvas = $this->dompdf->getCanvas();
        $canvas->page_text($paginationX, $paginationY, "{PAGE_NUM}/{PAGE_COUNT}", NULL, 12);
        $this->dompdf->setCanvas($canvas);
        break;

    }

    $this->moduleHandler->alter('pdf_generator_post_render', $this->dompdf);

    $response = new Response();
    $response->setContent($this->dompdf->output());
    $response->headers->set('Content-Type', 'application/pdf');
    if (is_array($title)) {
      $title = $this->renderer->render($title);
    }
    $filename = strtolower(trim(preg_replace('#\W+#', '_', $title), '_'));
    if ($forceDownload) {
      $response->headers->set('Content-Disposition', "attachment; filename={$filename}.pdf");
    }
    return $response;
  }

  /**
   * Return a list of page sizes.
   */
  public function pageSizes() {
    $sizes = array_keys(CPDF::$PAPER_SIZES);
    return array_combine($sizes, array_map('ucfirst', $sizes));
  }

  /**
   * Return a list disposition.
   */
  public function availableDisposition() {
    return [
      'portrait' => $this->t('Portrait'),
      'landscape' => $this->t('Landscape'),
    ];
  }

}
