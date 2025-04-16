<?php

namespace Drupal\Tests\access_by_taxonomy\Kernel;

use Drupal\access_by_taxonomy\AccessByTaxonomyService;
use Drupal\access_by_taxonomy\NodeAccessService;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Class PermissionsCheckTest tests access for public and restricted nodes.
 *
 * @package Drupal\Tests\access_by_taxonomy\Kernel
 */
class PermissionsCheckTest extends AccessByTaxonomyKernelTestBase {

  /**
   * Tests that when the access changes for a term, all nodes are updated.
   */
  public function testTermAccessChange() {
    // Create a term initially with no allowed roles.
    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = $this->createTestAccessTaxonomyTerm([]);

    $node = Node::create([
      'type' => 'page',
      'title' => 'test_title_restricted',
      'field_tags' => [
        [
          'target_id' => $term->id(),
        ],
      ],
    ]);
    $node->save();
    $node2 = Node::create([
      'type' => 'page_2',
      'title' => 'test_title_2',
      'field_tags' => [
        [
          'target_id' => $term->id(),
        ],
      ],
    ]);
    $node2->save();
    $access = $this->getAccessFromDatabase((int) $node->id(), AccessByTaxonomyService::PUBLIC_REALM, $node->language()->getId());
    $this->assertCount(1, $access);
    $access2 = $this->getAccessFromDatabase((int) $node2->id(), AccessByTaxonomyService::PUBLIC_REALM, $node2->language()->getId());
    $this->assertCount(1, $access2);

    // Now add a restriction to the term:
    $term->set(AccessByTaxonomyService::ALLOWED_ROLES_FIELD, ['target_id' => 'editor']);
    $term->save();

    NodeAccessService::rebuildNodeAccess((int) $node->id());
    NodeAccessService::rebuildNodeAccess((int) $node2->id());
    $access3 = $this->getAccessFromDatabase((int) $node->id(), AccessByTaxonomyService::ROLE_REALM, $node->language()->getId());
    $access4 = $this->getAccessFromDatabase((int) $node2->id(), AccessByTaxonomyService::ROLE_REALM, $node2->language()->getId());

    $this->assertCount(1, $access3);
    $this->assertCount(1, $access4);
  }

  /**
   * Test that a node with multiple terms works.
   */
  public function testMultipleTerms() {
    // Create two terms allowing the same role.
    $term1 = $this->createTestAccessTaxonomyTerm(['editor']);
    $term2 = $this->createTestAccessTaxonomyTerm(['editor']);

    // Put the terms on a node.
    $node = $this->createRestrictedNode($this->editorUser, 'en', $term1, TRUE);
    $node->set(
      'field_tags',
      [
        ['target_id' => $term1->id()],
        ['target_id' => $term2->id()],
      ]
    );
    $node->save();

    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $this->normalUser);
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, User::getAnonymousUser());
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $this->editorUserNotOwner);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $this->editorUser);
  }

}
