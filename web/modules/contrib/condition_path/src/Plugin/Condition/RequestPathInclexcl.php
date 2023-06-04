<?php

namespace Drupal\condition_path\Plugin\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Plugin\Condition\RequestPath;

/**
 * Provides a 'Request Path Include Exclude' condition.
 *
 * This condition can simultaneously in- and exclude paths.
 *
 * @Condition(
 *   id = "request_path_inclexcl",
 *   label = @Translation("Request Path Include Exclude"),
 * )
 */
class RequestPathInclexcl extends RequestPath {

  /**
   * Group name for included pages.
   */
  protected const INCLUDED_GROUP = 'included';

  /**
   * Group name for excluded pages.
   */
  protected const EXCLUDED_GROUP = 'excluded';

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['pages']['#description'] .= ' ' . $this->t("Use a leading '!' character to exclude a path. An example excluded path is %excluded-path for a news overview page. To include all news subpages, use %included-path. The more specific the page, the lower it should be listed.", [
      '%excluded-path' => '!/news',
      '%included-path' => '/news/*',
    ]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['pages'] = $form_state->getValue('pages');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary(): string {
    if (empty($this->configuration['pages'])) {
      return $this->t('No page is specified');
    }

    $summary = '';
    $countIncluded = $countExcluded = 0;
    $pages = $this->splitPages($this->configuration['pages']);

    foreach ($pages as $page) {
      strpos($page, '!') === 0 ? $countExcluded++ : $countIncluded++;
    }

    if (!empty($countIncluded)) {
      $summary .= $this->t('Pages included: @count', [
        '@count' => $countIncluded,
      ]);
    }

    if (!empty($countExcluded)) {
      $summary .= empty($summary) ? '' : '<br />';
      $summary .= $this->t('Pages excluded: @count', [
        '@count' => $countExcluded,
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    // Convert path to lowercase. This allows comparison of the same path
    // with different case. Ex: /Page, /page, /PAGE.
    $pages = mb_strtolower($this->configuration['pages']);
    if (!$pages) {
      return TRUE;
    }

    $request = $this->requestStack->getCurrentRequest();
    // Compare the lowercase path alias (if any) and internal path.
    $path = $this->currentPath->getPath($request);
    // Do not trim a trailing slash if that is the complete path.
    $path = $path === '/' ? $path : rtrim($path, '/');
    $pathAlias = mb_strtolower($this->aliasManager->getAliasByPath($path));

    $result = FALSE;
    $pages = $this->splitPages($pages);
    $pageGroups = $this->groupPages($pages);

    foreach ($pageGroups as $key => $group) {
      $group = implode(PHP_EOL, $group);
      $match = $this->pathMatcher->matchPath($pathAlias, $group) || (($path != $pathAlias) && $this->pathMatcher->matchPath($path, $group));

      // If we have a match, negate the result if the pages are to be excluded.
      if ($match) {
        $result = strpos($key, self::INCLUDED_GROUP) === 0;
      }
    }

    return $result;
  }

  /**
   * Group pages in include and exclude groups respecting their given order.
   *
   * By splitting up in groups, several paths can be validated at the same time
   * using the pathMatcher instead of checking them one by one.
   * The returned array will contain sub arrays with numbered keys. The keys
   * will be prefixed with 'included' or 'excluded' to mark them as such.
   *
   * @param array $pages
   *   The listed pages as an array.
   *
   * @return array
   *   The listed pages divided into in- and exclude groups, respecting the
   *   input order.
   */
  protected function groupPages(array $pages): array {
    $groups = [];
    $groupIndex = 0;
    $currentGroupType = '';

    foreach ($pages as $page) {
      if (strpos($page, '!') === 0) {
        // Check if we are starting a new 'excluded' group and mark it so.
        if ($currentGroupType !== self::EXCLUDED_GROUP) {
          $currentGroupType = self::EXCLUDED_GROUP;
          $groupKey = $currentGroupType . '_' . $groupIndex++;
        }
      }
      else {
        // Check if we are starting a new 'included' group and mark it so.
        if ($currentGroupType !== self::INCLUDED_GROUP) {
          $currentGroupType = self::INCLUDED_GROUP;
          $groupKey = $currentGroupType . '_' . $groupIndex++;
        }
      }

      // Add the page to its group.
      if (!empty($groupKey)) {
        $groups[$groupKey][] = ltrim($page, '!');
      }
    }

    return $groups;
  }

  /**
   * Split pages as a string to an array where each page is an element.
   *
   * @param string $pages
   *   The listed pages as a string.
   *
   * @return array
   *   The listed pages split into an array.
   */
  protected function splitPages(string $pages): array {
    return array_map('trim', preg_split('/[\r\n]+/', $pages));
  }

}
