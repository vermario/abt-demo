<?php

namespace Drupal\access_by_taxonomy;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeGrantDatabaseStorage;

/**
 * Class NodeAccess.
 *
 * This class is responsible for rebuilding node access for terms and nodes.
 */
class NodeAccessService {

  use StringTranslationTrait;

  const BATCH_CHUNK_SIZE = 50;

  /**
   * Constructs a new \Drupal\access_by_taxonomy\NodeAccessService object.
   */
  public function __construct(
    private readonly AccessByTaxonomyService $accessByTaxonomyService,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly Connection $database,
    private readonly NodeGrantDatabaseStorage $grantStorage,
    private readonly LoggerChannel $loggerChannel,
    private readonly CacheTagsInvalidatorInterface $cacheTagsInvalidator,
  ) {}

  /**
   * Rebuild access for a node.
   *
   * @param int $nid
   *   Node id value.
   *
   * @return string
   *   Message.
   */
  public static function rebuildNodeAccess(int $nid): string {
    $service = \Drupal::service('access_by_taxonomy.node_access');
    // Delete existing grants for this node.
    $service->database
      ->delete('node_access')
      ->condition('nid', $nid)
      ->execute();
    $service->entityTypeManager->getStorage('node')->resetCache([$nid]);
    $node = $service->entityTypeManager->getStorage('node')->load($nid);
    // To preserve database integrity, only write grants if the node
    // loads successfully.
    if (!empty($node)) {
      $grantHandler = $service->entityTypeManager
        ->getAccessControlHandler('node');
      /** @var \Drupal\node\NodeAccessControlHandler $grantHandler */
      $grants = $grantHandler->acquireGrants($node);
      $service->grantStorage->write($node, $grants);
      // Invalidate cache tags for this node:
      $service->cacheTagsInvalidator->invalidateTags($node->getCacheTags());
    }

    return 'Processed node ' . $nid;
  }

  /**
   * Function to process a batch of nodes.
   *
   * @param int $batch_id
   *   The id of the current batch.
   * @param array $nids
   *   An array containing an array with the nids to process during this round.
   * @param array $context
   *   The batch context.
   *
   * @return void
   *   Rebuilds access for provide node ids.
   */
  public static function processNodeAccessBatch(int $batch_id, array $nids, array &$context): void {
    if (!isset($context['results']['updated'])) {
      $context['results']['updated'] = 0;
    }

    // Rebuild access for the provided nids:
    foreach ($nids as $nid) {
      NodeAccessService::rebuildNodeAccess($nid);
      // Keep track of progress in results:
      $context['results']['updated']++;
    }
  }

  /**
   * Rebuild access for nodes using a term.
   *
   * @param int $termId
   *   Taxonomy term id value.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function rebuildTermAccess(int $termId): void {
    $nids = $this->getNidsUsingTerm($termId);
    $this->loggerChannel->notice('Rebuilding access for term @termId with @count nodes: @nids.', [
      '@termId' => $termId,
      '@count' => count($nids),
      '@nids' => implode(',', $nids),
    ]);

    // Create batches:
    $chunks = array_chunk($nids, self::BATCH_CHUNK_SIZE);
    $num_chunks = count($chunks);
    $operations = [];
    // Create operations:
    for ($batch_id = 0; $batch_id < $num_chunks; $batch_id++) {
      $operations[] = [
        '\Drupal\access_by_taxonomy\NodeAccessService::processNodeAccessBatch',
        [
          $batch_id + 1,
          $chunks[$batch_id],
        ],
      ];
    }

    $batch = [
      'title' => $this->t('Updating content access permissions'),
      'operations' => $operations,
      'finished' => 'Drupal\access_by_taxonomy\NodeAccessService::rebuildComplete',
    ];
    batch_set($batch);

  }

  /**
   * Get node ids using a term.
   *
   * @param int $termId
   *   Taxonomy term id value.
   *
   * @return array
   *   Array of node ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getNidsUsingTerm(int $termId): array {
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $fields = [];
    foreach ($node_types as $node_type) {
      $fieldsCt = $this->accessByTaxonomyService->getNodeTypeTaxonomyTermFieldDefinitions((string) $node_type->id());
      $fields = array_merge($fields, $fieldsCt);
    }
    $fields = array_unique($fields);
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $or = $query->orConditionGroup();
    foreach ($fields as $field) {
      $or->condition($field, $termId);
    }
    $query->condition($or);
    $query->accessCheck(FALSE);
    return $query->execute();
  }

  /**
   * Rebuild is finished.
   */
  public static function rebuildComplete($success, $results): void {
    if ($success && isset($results['updated']) && $results['updated'] > 0) {
      // If we are successful, and we have updated nodes,
      // invalidate the node list cache for views:
      \Drupal::service('cache_tags.invalidator')->invalidateTags(['node_list']);
      // Show a success message to the users:
      \Drupal::messenger()->addMessage(t('Access rebuild complete. @count nodes updated.', ['@count' => $results['updated']]));
    }
  }

}
