<?php

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Utility\Error as ErrorHelper;
use Drupal\views\Entity\View;

/**
 * Update views configuration to migrate from unified_datetime filter to field default.
 */
function unified_date_post_update_convert_views_filters(&$sandbox) {
  /** @var View $view */
  foreach (View::loadMultiple() as $view) {
    foreach (array_keys($view->get('display')) as $display_id) {
      $display_configuration = &$view->getDisplay($display_id);
      if (!empty($display_configuration['display_options']['filters'])) {
        foreach ($display_configuration['display_options']['filters'] as &$filter_configuration) {
          if (!empty($filter_configuration['plugin_id'])
            && $filter_configuration['plugin_id'] === 'unified_datetime') {
            $filter_configuration['plugin_id'] = 'date';
            $filter_configuration['entity_field'] = 'unified_date';
            $filter_configuration['field'] = 'unified_date';
          }
        }
      }
    }
    try {
      $view->save();
    }
    catch (Throwable $exception) {
      // Catch-case for exception from \Drupal\search_api\Plugin\views\cache\SearchApiTagCache::alterCacheMetadata.
      // There is a bug in search api, which not allow us to save view properly,
      // as trying to build query which not exists in view.
      \Drupal::logger('unified_date')
        ->log(
          RfcLogLevel::ERROR,
          '%type: @message in %function (line %line of %file).',
          ErrorHelper::decodeException($exception)
        );
    }
  }
}
