<?php

namespace Drupal\Tests\access_by_taxonomy\Kernel;

use Drupal\access_by_taxonomy\AccessByTaxonomyService;

/**
 * Class AllowedUserTest tests access to nodes for a specific user.
 *
 * @package Drupal\Tests\access_by_taxonomy\Kernel
 */
class AllowedUserTest extends AccessByTaxonomyKernelTestBase {

  /**
   * Authenticated User Published Restricted allowed (by user).
   */
  public function testAuthenticatedPublishedRestrictedUserAllowed() {
    // Create a term allowed for admins and one user.
    $user = $this->normalUser;
    $term = $this->createTestAccessTaxonomyTerm([self::ROLE_ADMINISTRATOR], 'en', [$user]);
    $node = $this->createRestrictedNode($this->editorUser, 'en', $term, TRUE);
    // The allowed user should have access to the node.
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $user);
    // Another user with the same role cannot view the node.
    $user2 = $this->createUser();
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $user2);

    $nid = $node->id();
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::ROLE_REALM, $node->language()->getId(), 1);
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::USER_REALM, $node->language()->getId(), 1, $user->id());
    $this->assertAccessRowsFromDatabase($nid, 'all', $node->language()->getId(), 0);
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::OWNER_REALM, $node->language()->getId(), 1, $node->getOwnerId());
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::VIEW_ANY_REALM . $node->getType(), $node->language()->getId(), 1, 1);
  }

  /**
   * Authenticated User Unpublished Restricted allowed (by user).
   */
  public function testAuthenticatedUnpublishedRestrictedUserAllowed() {
    // Create a term allowed for admins and one user.
    $user = $this->normalUser;
    $term = $this->createTestAccessTaxonomyTerm([self::ROLE_ADMINISTRATOR], 'en', [$user]);
    $node = $this->createRestrictedNode($this->editorUser, 'en', $term, FALSE);
    // The allowed user should not have access to the node.
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $user);
    // Another user with the same role cannot view the node.
    $user2 = $this->createUser();
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $user2);

    $nid = $node->id();
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::ROLE_REALM, $node->language()->getId(), 0);
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::USER_REALM, $node->language()->getId(), 0, $user->id());
    $this->assertAccessRowsFromDatabase($nid, 'all', $node->language()->getId(), 0);
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::OWNER_REALM, $node->language()->getId(), 0);
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::OWN_UNPUBLISHED_REALM, $node->language()->getId(), 1, $node->getOwnerId());
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::VIEW_ANY_REALM . $node->getType(), $node->language()->getId(), 1, 1);
  }

}
