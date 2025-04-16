<?php

namespace Drupal\Tests\access_by_taxonomy\Kernel;

use Drupal\access_by_taxonomy\AccessByTaxonomyService;
use Drupal\user\Entity\Role;

/**
 * Class DeleteRoleTest tests operations after a role is deleted.
 *
 * @package Drupal\Tests\access_by_taxonomy\Kernel
 */
class DeleteRoleTest extends AccessByTaxonomyKernelTestBase {

  /**
   * Allowed user delete test.
   */
  public function testRoleDelete() {

    // Set up a new role:
    $new_role = $this->drupalCreateRole(['access content'], 'test_only_role');

    // Create a term allowed for the new role:
    $term = $this->createTestAccessTaxonomyTerm([$new_role]);
    $node = $this->createRestrictedNode($this->editorUser, 'en', $term, TRUE);
    $nid = $node->id();

    // Check that the database has been updated accordingly:
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::ROLE_REALM, $node->language()->getId(), 1);
    // Now delete the role:
    $role = Role::load($new_role);
    $role->delete();
    // Now check that there are no more rows for the role for the node.
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::ROLE_REALM, $node->language()->getId(), 0);
  }

}
