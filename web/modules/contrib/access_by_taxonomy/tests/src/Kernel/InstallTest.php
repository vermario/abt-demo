<?php

namespace Drupal\Tests\access_by_taxonomy\Kernel;

use Drupal\access_by_taxonomy\AccessByTaxonomyService;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Class InstallTest tests that installing the module works fine.
 *
 * @package Drupal\Tests\access_by_taxonomy\Kernel
 */
class InstallTest extends AccessByTaxonomyKernelTestBase {

  /**
   * Test that upon install, roles are populated.
   */
  public function testRolesPopulated() {
    $roles = [
      RoleInterface::ANONYMOUS_ID,
      RoleInterface::AUTHENTICATED_ID,
      self::ROLE_EDITOR,
      self::ROLE_EDITOR_ALL_PAGES,
      self::ROLE_ADMINISTRATOR,
    ];

    $results = \Drupal::service('access_by_taxonomy.service')->getRoleGrant();
    $this->assertCount(count($roles), $results);
  }

  /**
   * Tests that fields are added to existing taxonomies.
   */
  public function testFieldsArePresent() {
    $fields = $this->container->get('entity_field.manager')->getFieldDefinitions('taxonomy_term', 'test');
    $this->assertNotEmpty($fields[AccessByTaxonomyService::ALLOWED_USERS_FIELD]);
    $this->assertNotEmpty($fields[AccessByTaxonomyService::ALLOWED_ROLES_FIELD]);

    $fields = $this->container->get('entity_field.manager')->getFieldDefinitions('taxonomy_term', 'test2');
    $this->assertNotEmpty($fields[AccessByTaxonomyService::ALLOWED_USERS_FIELD]);
    $this->assertNotEmpty($fields[AccessByTaxonomyService::ALLOWED_ROLES_FIELD]);
  }

  /**
   * Adds and removes a role, tests grants are updated accordingly.
   */
  public function testRoleAddAndRemove() {
    $current_gids = \Drupal::service('access_by_taxonomy.service')->getRoleGrant();
    $new_role = $this->drupalCreateRole([], 'new_role');
    $has_been_added = \Drupal::service('access_by_taxonomy.service')->getRoleGrant();
    $this->assertTrue(count($current_gids) + 1 === count($has_been_added));
    $role = Role::load($new_role);
    $role->delete();
    $has_been_removed = \Drupal::service('access_by_taxonomy.service')->getRoleGrant();
    $this->assertTrue(count($current_gids) === count($has_been_removed));
  }

  /**
   * Tests that permissions are present for all content types.
   */
  public function testPermissionsArePresent() {
    $content_types = $this->container->get('entity_type.manager')->getStorage('node_type')->loadMultiple();
    /** @var \Drupal\node\NodeTypeInterface $type. */
    foreach ($content_types as $type) {
      $type_id = $type->id();
      $this->config('user.role.' . RoleInterface::AUTHENTICATED_ID)
        ->set('permissions', ["access by taxonomy view any " . $type_id . " content"])
        ->save();
    }
  }

}
