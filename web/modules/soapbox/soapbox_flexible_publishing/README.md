# Installation instructions

## Initial setup

1. Install `form_mode_manager` module
1. Enable this module

## Managing the form modes to serve as templates

1. Visit `/admin/structure/display-modes/form` and add Content Form modes, eg
'Chapterised item', 'Download item'
2. Enable the form modes under the 'Manage form display' tab of your content 
type in 'Custom display settings', eg `/admin/structure/types/manage/article/form-display`
3. Visit `/admin/content` and `/node/add` to see your new form modes.

## Removing the 'Edit as (form mode)' from the edit node page

1. Go to Structure > Blocks > (Admin Theme) > Secondary Tasks
2. Add '/node/*/edit/*' to 'Request path exclusion' to remove from all (or use 
more specific criteria to remove from only some).

## Adding the 'I want to add an [type] item using the [template] template' block

1. Go to Structure > Blocks > (Admin Theme) > Content
2. Place the 'Flexible Publishing Add Content' block.
3. Restrict to certain 'Pages':

```
/node/add
/admin/content
```
