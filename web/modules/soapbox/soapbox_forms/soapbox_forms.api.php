<?php

/**
 * @file
 * Soapbox Forms API documentation.
 */

/**
 * Exclude forms from applying c-form and other custom wrapper/field classes.
 *
 * @param bool $is_excluded
 *   Set true/false for excluding the current form.
 * @param string $form_id
 *   From id of the current form.
 */
function hook_soapbox_forms_exclude_form_alter(&$is_excluded, &$form_id) {
  if (stripos($form_id, 'webform_') !== FALSE) {
    $is_excluded = TRUE;
  }
}
