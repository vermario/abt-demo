<?php

namespace Drupal\Tests\access_by_taxonomy\Kernel;

use Drupal\access_by_taxonomy\AccessByTaxonomyService;
use Drupal\field\Entity\FieldConfig;
use Drupal\user\Entity\User;

/**
 * Class RemoveFieldsTest checks that removing ABT fields is possible.
 *
 * @package Drupal\Tests\access_by_taxonomy\Kernel
 */
class RemovedFieldsTest extends AccessByTaxonomyKernelTestBase {

  /**
   * Removing roles field.
   */
  public function testRemoveRolesField() {
    $this->deleteTaxonomyField('test', AccessByTaxonomyService::ALLOWED_ROLES_FIELD);
    $anonymous = User::getAnonymousUser();
    // Create a term allowed for one user.
    $user = $this->normalUser;
    $term = $this->createTestAccessTaxonomyTerm([], 'en', [$user]);
    $node = $this->createRestrictedNode($this->editorUser, 'en', $term, TRUE);
    // The allowed user should have access to the node.
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $user);
    // Another user with the same role cannot view the node.
    $user2 = $this->createUser();
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $user2);
    // The anonymous user cannot view the node.
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $anonymous);

    $nid = $node->id();
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::ROLE_REALM, $node->language()->getId(), 0);
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::USER_REALM, $node->language()->getId(), 1, $user->id());
    $this->assertAccessRowsFromDatabase($nid, 'all', $node->language()->getId(), 0);
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::OWNER_REALM, $node->language()->getId(), 1, $node->getOwnerId());
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::VIEW_ANY_REALM . $node->getType(), $node->language()->getId(), 1, 1);
  }

  /**
   * Removing users field.
   */
  public function testRemoveUsersField() {
    $this->deleteTaxonomyField('test', AccessByTaxonomyService::ALLOWED_USERS_FIELD);
    $anonymous = User::getAnonymousUser();
    // Create a term allowed for one role.
    $user = $this->normalUser;
    $term = $this->createTestAccessTaxonomyTerm([self::ROLE_EDITOR], 'en', []);
    $node = $this->createRestrictedNode($this->editorUser, 'en', $term, TRUE);
    // A normal user should NOT have access to the node.
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $user);
    // The owner user should have access to the node.
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $this->editorUser);
    // An editor user should have access to the node.
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $this->editorUserNotOwner);
    // The anonymous user cannot view the node.
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $anonymous);

    $nid = $node->id();
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::ROLE_REALM, $node->language()->getId(), 1);
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::USER_REALM, $node->language()->getId(), 0);
    $this->assertAccessRowsFromDatabase($nid, 'all', $node->language()->getId(), 0);
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::OWNER_REALM, $node->language()->getId(), 1, $node->getOwnerId());
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::VIEW_ANY_REALM . $node->getType(), $node->language()->getId(), 1, 1);
  }

  /**
   * Removing a field from a tax term.
   */
  private function deleteTaxonomyField($vid, $field_name) {
    // Deleting field.
    FieldConfig::loadByName('taxonomy_term', $vid, $field_name)->delete();
  }

}
