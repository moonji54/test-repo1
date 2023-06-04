<?php

/**
 * @file
 */
?>
# Soapbox Featured Cards.

This module handles both manual and automated featured contents and populate teh contents in the preferred view modes.

In your own module, preprocess the node.

```
<?php

/**
 * Implements hook_preprocess_HOOK().
 */
function MY_MODULE_preprocess_paragraph(&$variables) {

  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];
  $node = $paragraph->getParentEntity();

  // Adding node information as a render var to paragraphs.
  $variables['node'] = $node;

  // Add details for specific paragraph types.
  switch ($paragraph->getType()) {

    case 'featured_boxes':
      $featured_cards_service = \Drupal::service('soapbox_featured_cards.featured_cards');

      // Sets image toggle field.
      $featured_cards_service->setImageToggleField($field = 'field_show_image');

      // Sets the page builder field.
      $featured_cards_service->setPageBuilderField('field_page_builder');

      // Sets the allowed view modes.
      $featured_cards_service->setViewModes([
        'card' => 'featured_card',
        'card_with_image' => 'featured_card_with_image',
      ]);

      // Sets the manual selection field.
      // The first argument syas whether the manaul selection is added via a 'paragrpah' reference field or a 'node' reference field.
      // The second argument is to give the name of the manual selection field.
      // The third argument is to give the name of the node selection field form the manaul paragrpah if the first argumnet here is 'paragraph'.
      $featured_cards_service->setManualSelectionFields('paragraph', 'field_contents', 'field_content_from_paragraph');

      // Sets automated settings - allowed node types, taxonomy filters, field limit, sort fields.
      $featured_cards_service->setAutomatedSettings('field_node_bundles', [
        'field_themes',
        'field_regions',
      ],
      'field_limit', [
        'created',
        'another_sort_field',
      ], '');

      // Set allowed types alternatively
      // to pass defualt node types
      // in case if a paragrpah doesn't have
      // node types configured.
      $featured_cards_service->setParagraph($paragraph);
      $featured_cards_service->setAllowedTypes($allowed_types);

      // Preprocess featured cards.
      $featured_cards_service->preprocessFeaturedCards($variables);

      // In order to set any additional filters for date.
      // Ex: show upcoming events only
      // Date filter.
      $date_filter = '';
      $date_fields = [];
      if ($paragraph->hasField('field_date_filter') &&
          in_array($paragraph->get('field_date_filter')->value,
            [
              'upcoming',
              'past',
            ])) {
        $date_filter = $paragraph->get('field_date_filter')->value;
        $date_fields = ['event' => 'field_start_date_time'];
      }
      $featured_cards_service->setAutomatedSettings('field_node_bundles', [
        'field_themes',
        'field_regions',
        'field_campaigns',
        'field_forums',
        'field_keywords',
        'field_networks',
        'field_leadership_supports',
      ], 'field_limit', 'unified_date', $date_filter, $date_fields, '');

  }
}
```


