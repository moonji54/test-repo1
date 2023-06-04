<?php

namespace Drupal\soapbox_themes\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extensions.
 */
class ThemeTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getTokenParsers() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeVisitors() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [];
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
    return [
      new TwigFunction('get_theme', [
        $this,
        'getThemeName',
      ]),
    ];
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
    return 'soapbox_themes.twig.extension';
  }

  /**
   * Get current activated theme.
   *
   * @return mixed|string
   *   Theme name.
   */
  public function getThemeName() {
    return \Drupal::theme()->getActiveTheme()->getName();
  }

}
