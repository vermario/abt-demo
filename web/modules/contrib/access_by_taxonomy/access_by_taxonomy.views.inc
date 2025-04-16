<?php

/**
 * @file
 * Provide views data for access_by_taxonomy.module.
 */

declare(strict_types=1);

use Drupal\access_by_taxonomy\Plugin\views\filter\NodeStatus;

/**
 * Implements hook_views_plugins_filter_alter().
 */
function access_by_taxonomy_views_plugins_filter_alter(array &$plugins): void {
  // Change the 'status' handler class.
  $plugins['node_status']['class'] = NodeStatus::class;
}
