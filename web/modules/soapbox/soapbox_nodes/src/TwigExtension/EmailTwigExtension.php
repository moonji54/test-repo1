<?php

namespace Drupal\soapbox_nodes\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class to protect an email address (obfuscate).
 */
class EmailTwigExtension extends AbstractExtension {


  /**
   * Twig\Extension\ExtensionInterface definition.
   *
   * @var \Twig\Extension\ExtensionInterface
   */

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
    return [
      new TwigFilter('protect_email', [
        $this,
        'protectEmail',
      ]),
    ];
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
    return [];
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
    return 'soapbox_nodes.email.twig.extension';
  }

  /**
   * Escapes the email.
   *
   * @param string $email
   *   Email to filter.
   *
   * @return string|string[]
   *   Escaped email.
   */
  public function protectEmail($email) {

    $new_mail = '';

    if (!empty($email)) {

      $p = str_split(trim($email));

      foreach ($p as $val) {
        $new_mail .= '&#' . ord($val) . ';';
      }

      $new_mail = [
        '#markup' => $new_mail,
      ];
    }

    return $new_mail;
  }

}
