<?php

/**
 * @file
 * Soapbox Nodes API documentation.
 */

/**
 * Alter the sub navigation to show sub or sibling pages.
 *
 * @param bool $show_sub_pages
 *   True/false for showing sub pages.
 * @param bool $show_sibling_pages
 *   True/false for showing sibling pages.
 */
function hook_soapbox_nodes_subpages_alter(&$show_sub_pages, &$show_sibling_pages) {
  $show_sub_pages = FALSE;
  $show_sibling_pages = TRUE;
}
