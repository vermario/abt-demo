<?php

namespace Drupal\Tests\access_by_taxonomy\Kernel;

use Drupal\access_by_taxonomy\AccessByTaxonomyService;

/**
 * Class DeleteUserTest tests that after a user is deleted, access is removed.
 *
 * @package Drupal\Tests\access_by_taxonomy\Kernel
 */
class DeleteUserTest extends AccessByTaxonomyKernelTestBase {

  /**
   * Tests that after a user is deleted, access is removed.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
  }

  /**
   * User Delete test.
   */
  public function testOwnerUserDelete() {
    // Create a published node.
    $node = $this->createRestrictedNode($this->editorUser, 'en', NULL, TRUE);
    $node2 = $this->createRestrictedNode($this->editorUser, 'en', NULL, FALSE);
    $nid = $node->id();
    $nid2 = $node2->id();
    $uid = $this->editorUser->id();
    // Assert that we have the owner row.
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::OWNER_REALM, $node->language()->getId(), 1, $uid);
    $this->assertAccessRowsFromDatabase($nid2, AccessByTaxonomyService::OWN_UNPUBLISHED_REALM, $node->language()->getId(), 1, $uid);
    $this->editorUser->delete();
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::OWNER_REALM, $node->language()->getId(), 0, $uid);
    $this->assertAccessRowsFromDatabase($nid2, AccessByTaxonomyService::OWN_UNPUBLISHED_REALM, $node->language()->getId(), 0, $uid);
  }

  /**
   * Allowed user delete test.
   */
  public function testAllowedUserDelete() {
    // Create a term allowed for admins and one user.
    $user = $this->normalUser;
    $term = $this->createTestAccessTaxonomyTerm([self::ROLE_ADMINISTRATOR], 'en', [$user]);
    $node = $this->createRestrictedNode($this->editorUser, 'en', $term, TRUE);
    $nid = $node->id();
    $uid = $user->id();
    // The user should have access to the node.
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::USER_REALM, $node->language()->getId(), 1, $uid);
    $user->delete();
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::USER_REALM, $node->language()->getId(), 0, $uid);
  }

}
