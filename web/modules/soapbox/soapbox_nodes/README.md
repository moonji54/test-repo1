# Soapbox Nodes Twig Extensions/Helper services.

This module provides a bunch of Twig extensions with useful Twig filters and functions to alter the meta information
shown on the cards, listing items and node templates.

This module also provides services such as SubPageHelper and LanguageHelper. 

## Excerpt Twig Extension

This provides `get_excerpt` Twig filter to populate the excerpt text from predefined node fields.

### Allowed parameters

1. Node fields to be used for getting excerpt text from.
2. Paragraph fields such as Wysiwyg page builder field to be used to trim and get summary text from. For each paragraph
   field, the field name can be given using `field` key, the paragraph type can be given using `type` key and the body
   field to extract the text from the paragraph can be given using `wysiwyg_body`.
   Ex: ```['field' => 'field_components',
   'type' => 'wysiwyg',
   'wysiwyg_body' => 'field_body']```
3. The char length to truncate the text.

### Usage

In Twig as a filter directly:

```
node|get_excerpt([
'field_description',
'field_abstract'],
[{field: 'field_components',
 type: 'wysiwyg',
 wysiwyg_body: 'field_body'}]
 ,160)
```

As a service from inside node preprocess hooks and then directly use the custom preprocessed variable. Easy enough to
control the function arguments such as allowed node fields, etc in one place without having to repeat the long twig filter across all twig templates.

```
function MY_MODULE_preprocess_node(&$variables) {
    $node = $variables['node'];

    // Get excerpt from twig extension.
    $excerpt_twig_service = \Drupal::service('soapbox_nodes.excerpt.twig.extension');
    $variables['excerpt'] = $excerpt_twig_service->getNodeExcerpt($node,
    ['field_description', 'field_abstract'], [
    [
    'field' => 'field_components',
    'type' => 'wysiwyg',
    'wysiwyg_body' => 'field_body',
    ],
    ], 160);

}
```

## Type label Twig Extension

This provides `type_label` Twig filter to get the type meta to show on cards. If you want to show the subtype taxonomy
term for a node instead of showing the node bundle in a card, then this filter will be useful.

### Allowed parameters

1. Taxonomy fields to be used for getting the type label from.

### Usage

In Twig as a filter directly:

```
node|type_label(
{'document': 'field_document_type',
 'event': 'field_event_type',
  'news': 'field_news_type'})
```

As a service from inside node preprocess hooks and then directly use the custom preprocessed variable. Easy enough to
control the function arguments such as allowed node fields, etc in one place without having to repeat the long twig filter across all twig templates.

```
function MY_MODULE_preprocess_node(&$variables) {
    $node = $variables['node'];

    // Get type label from twig extension.
    $type_twig_service = \Drupal::service('soapbox_nodes.type_label.twig.extension');
    $variables['type_label'] = $type_twig_service->getSubtype($node, [
      'document' => 'field_document_type',
      'article' => 'field_article_type',
      'news' => 'field_news_type',
      'event' => 'field_event_type',
    ]);

}
```

## Dateline Twig Extension

This provides `dateline` Twig filter to populate the dateline in cards. This becomes useful to show the different dates
or date ranges for different node bundles.

### Allowed parameters

1. The date fields to get the date from. The default field is `unified_date`. For showing ranges with start/end dates,
   set the range key to true and provide the start/end date fields to be used. Ex: ```[
   'node_bundle' => 'event',
   'range' => TRUE,
   'range_start' => 'field_start_date_time',
   'range_end' => 'field_end_date_time',
   'date_format' => 'your_site_date_format',
   ]```
2. A static timezone field can be set with the key as `suffix` to append to the edn of dateline.
3. The general Drupal date format that you have set for the project in the regional settings.

### Usage

In Twig as a filter directly:

```
node|dateline([
{node_bundle: 'event',
 'range': true, range_start: 'field_start_date',
  range_end: 'field_end_date',
  date_format: your_speficic_date_format_for_this_bundle'}],
  'your_site_date_format')
```

As a service from inside node preprocess hooks and then directly use the custom preprocessed variable. Easy enough to
control the function arguments such as allowed node fields, etc in one place without having to repeat the long twig filter across all twig templates.

```
function MY_MODULE_preprocess_node(&$variables) {
    $node = $variables['node'];

    // Get dateline.
    $date_twig_service = \Drupal::service('soapbox_nodes.date.twig.extension');
    $variables['dateline'] = $date_twig_service->getDateLine($node, [
      [
        'node_bundle' => 'event',
        'range' => TRUE,
        'range_start' => 'field_start_date_time',
        'range_end' => 'field_end_date_time',
        'date_format' => 'your_speficic_date_format_for_this_bundle',
        'suffix_field' => 'field_timezone',
      ],
    ], 'your_site_date_format');

}
```

## SubPages Helper

This helper class provides a service to dynamically populate a list of subpages or sibling pages for the current page to form a sub navigation. The sub/sibling pages are populated on the basis of how menu items are added via a specific menu.

### Usage

```
function MY_MODULE_preprocess_node(&$variables) {
   $node = $variables['node'];
   $sub_pages_helper = Drupal::service('soapbox_nodes.sub_pages');
   $sub_page_ids = $sub_pages_helper->getSubPages($node);

   if (!empty($sub_page_ids)) {
      // The below will generate the node objects from node ids.
      // You can directly assign it to $variables if it can be processed form the twig file.
      $sub_pages = Node::loadMultiple($sub_page_ids);
      
      // Alternatively, generate an array of URL strings and labels.
      if ($sub_pages) {
         $sub_nav_items = [];
         foreach ($sub_pages as $sub_page) {
            $sub_nav_items[] = [
              'url' => $sub_page->toUrl()->toString(),
              'title' => $sub_page->label(),
            ];
         }
      }
   } 
}        
```


## Languages helper

This helper class provides a service to get a list of translations available for the current node in a pretty formatted array with options for language_id, language object, translated content, translation_url, current language, etc.

### Usage

```
function MY_MODULE_preprocess_node(&$variables) {
   $node = $variables['node'];
   $language_helper = Drupal::service('soapbox_nodes.languages');
   $variables['translations'] = $language_helper->getLanguages($node);
}   
```

## Subpage title

This gives the capability to use either Node or Menu as Subpage title.

## Usage
You can add following in your settings file to enable Menu as title, default will be Node as title.
```
$config['soapbox_nodes.subpages']['menu_title'] = TRUE;
```
