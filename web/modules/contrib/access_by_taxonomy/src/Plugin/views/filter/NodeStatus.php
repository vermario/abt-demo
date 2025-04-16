<?php

declare(strict_types=1);

namespace Drupal\access_by_taxonomy\Plugin\views\filter;

use Drupal\node\Entity\NodeType;
use Drupal\node\Plugin\views\filter\Status;

/**
 * Adds support for the access by taxonomy view any $type_id content.
 *
 * Takes over the Published or Admin filter query.
 *
 * @ingroup views_filter_handlers
 *
 * @property \Drupal\views\Plugin\views\query\Sql $query
 */
class NodeStatus extends Status {

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    $table = $this->ensureMyTable();
    // This is the default that matches the core Status filter:
    $snippet = "$table.status = 1 OR ($table.uid = ***CURRENT_USER*** AND ***CURRENT_USER*** <> 0 AND ***VIEW_OWN_UNPUBLISHED_NODES*** = 1) OR ***BYPASS_NODE_ACCESS*** = 1";
    if ($this->moduleHandler->moduleExists('content_moderation')) {
      $snippet .= ' OR ***VIEW_ANY_UNPUBLISHED_NODES*** = 1';
    }

    $where_per_type = [];
    foreach (NodeType::loadMultiple() as $type) {
      $type_id = $type->id();
      $where_per_type[] = "($table.type = '$type_id' AND ***ACCESS_BY_TAXONOMY_VIEW_ANY_TYPE_$type_id*** = 1)";
    }
    if ($where_per_type !== []) {
      $where_per_type = implode(' OR ', $where_per_type);
      $snippet .= " OR $where_per_type";
    }

    $this->query->addWhereExpression($this->options['group'], $snippet);
  }

}
