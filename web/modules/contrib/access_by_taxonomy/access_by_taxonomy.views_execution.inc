<?php

/**
 * @file
 * Provide views runtime hooks for access_by_taxonomy.module.
 */

declare(strict_types=1);

use Drupal\node\Entity\NodeType;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_substitutions().
 */
function access_by_taxonomy_views_query_substitutions(ViewExecutable $view): array {
  $account = \Drupal::currentUser();
  $substitutions = [];
  foreach (NodeType::loadMultiple() as $type) {
    $type_id = $type->id();
    $substitutions["***ACCESS_BY_TAXONOMY_VIEW_ANY_TYPE_$type_id***"] = (int) $account->hasPermission('access by taxonomy view any ' . $type_id . ' content');
  }

  return $substitutions;
}
