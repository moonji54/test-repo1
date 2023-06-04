# Soapbox Related Content.

This module handles finding matching related contents, ordering by most matches to least matches. An arbitrary limit (25) has been set per field to avoid loading too many nodes into memory if site size grows large.

In your own module, preprocess the node. 

```
<?php

/**
 * @file
 * Provides additional variables for the node templates.
 */

/**
 * Implements template_preprocess_node().
 */
function MYMODULE_preprocess_node(&$variables) {
  $related_content_helper = Drupal::service('soapbox_related_content.related_content');
  
  // Which node types are allowed as related content results?
  $related_content_helper->setAllowedTargetNodeTypes([
    'news',
    'publication',
  ]);
  
  // Which fields should be compared?
  $related_content_helper->setFieldsToCheck([
    'field_themes',
    'field_countries',
  ]);

  // Has the client been given control over which entity
  // IDs to restrict to rather than just matching ones,
  // like the setFieldToCheck() above.
  $target_ids = [];
  foreach ($node->get('field_allowed_themes') as $theme_reference) {
    $target_ids[] = $theme_reference->target_id;
  }
  if ($target_ids) {
    $related_content_helper->setSpecificEntityIdsToMatchByField('field_theme', $target_ids);
  }
  
  // Which view mode should be used to render the contents?
  $related_content_helper->setViewMode('featured_box_small');
  
  // Set the sort order. Unified date descending is default,
  // so this can be skipped unless you need something specific.
  $related_content_helper->setSortOrders([
    [
      'field' => 'field_my_field',
      'direction' => 'DESC',
    ],
    [
      'field' => 'unified_date',
      'direction' => 'DESC',
    ],
  ]);

  // How many related contents should we find?
  $this->setReturnLimit(4);
  
  // Run the preprocess.
  $related_content_helper->preprocessNode($variables);
}
```

Then in your twig template, something like:

```
{% if related_nodes is not empty %}
  <div class="c-featured-boxes">
    {% for related_node in related_nodes %}
      {# The output of node--your-view-mode.html.twig #}
      {{ related_node }}
    {% endfor %}
  </div>
{% endif %}
```
