<?php

namespace Drupal\Tests\access_by_taxonomy\Kernel;

use Drupal\user\RoleInterface;

/**
 * Class AuthenticatedNodeAccessTest tests access for a normal user.
 *
 * @package Drupal\Tests\access_by_taxonomy\Kernel
 */
class AuthenticatedNodeAccessTest extends AccessByTaxonomyKernelTestBase {

  /**
   * Authenticated Unpublished Unrestricted.
   */
  public function testAuthenticatedUnpublishedUnrestricted() {
    $user = $this->normalUser;
    $node = $this->createUnrestrictedNode($this->editorUser);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, FALSE);
  }

  /**
   * Authenticated Unpublished Restricted.
   */
  public function testAuthenticatedUnpublishedRestricted() {
    $user = $this->normalUser;
    $node = $this->createRestrictedNode($this->editorUser);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);
  }

  /**
   * Authenticated Unpublished Restricted Allowed.
   */
  public function testAuthenticatedUnpublishedRestrictedAllowed() {
    $user = $this->normalUser;
    // Create term allowed for authenticated.
    $term = $this->createTestAccessTaxonomyTerm([RoleInterface::AUTHENTICATED_ID]);

    $node = $this->createRestrictedNode($this->editorUser, 'en', $term);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);
  }

  /**
   * Authenticated Published Unrestricted.
   */
  public function testAuthenticatedPublishedUnrestricted() {
    $user = $this->normalUser;
    $node = $this->createUnrestrictedNode($this->editorUser, 'en', TRUE);

    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, FALSE);
  }

  /**
   * Authenticated Published Restricted.
   */
  public function testAuthenticatedPublishedRestricted() {
    $user = $this->normalUser;
    $node = $this->createRestrictedNode($this->editorUser, 'en', NULL, TRUE);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);

  }

  /**
   * Authenticated Published Restricted Allowed.
   */
  public function testAuthenticatedPublishedRestrictedAllowed() {
    $user = $this->normalUser;

    // Create term allowed for authenticated.
    $term = $this->createTestAccessTaxonomyTerm([RoleInterface::AUTHENTICATED_ID]);

    $node = $this->createRestrictedNode($this->editorUser, 'en', $term, TRUE);

    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);
  }

}
