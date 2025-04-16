<?php

namespace Drupal\Tests\access_by_taxonomy\Kernel;

use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * Class PermissionsCheckTest tests access for public and restricted nodes.
 *
 * @package Drupal\Tests\access_by_taxonomy\Kernel
 */
class AnonymousNodeAccessTest extends AccessByTaxonomyKernelTestBase {

  /**
   * Anonymous Unpublished Unrestricted.
   */
  public function testAnonymousUnpublishedUnrestricted() {
    $user = User::getAnonymousUser();
    $node = $this->createUnrestrictedNode($this->editorUser);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, FALSE);

  }

  /**
   * Anonymous Unpublished Restricted.
   */
  public function testAnonymousUnpublishedRestricted() {
    $user = User::getAnonymousUser();
    $node = $this->createRestrictedNode($this->editorUser);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);
  }

  /**
   * Anonymous Unpublished Restricted Allowed.
   */
  public function testAnonymousUnpublishedRestrictedAllowed() {
    $user = User::getAnonymousUser();
    // Create term allowed for anonymous.
    $term = $this->createTestAccessTaxonomyTerm([RoleInterface::ANONYMOUS_ID]);

    $node = $this->createRestrictedNode($this->editorUser, 'en', $term);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);
  }

  /**
   * Anonymous Published Unrestricted.
   */
  public function testAnonymousPublishedUnrestricted() {
    $user = User::getAnonymousUser();
    $node = $this->createUnrestrictedNode($this->editorUser, 'en', TRUE);

    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, FALSE);
  }

  /**
   * Anonymous Published Restricted.
   */
  public function testAnonymousPublishedRestricted() {
    $user = User::getAnonymousUser();
    $node = $this->createRestrictedNode($this->editorUser, 'en', NULL, TRUE);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);
  }

  /**
   * Anonymous Published Restricted Allowed.
   */
  public function testAnonymousPublishedRestrictedAllowed() {
    $user = User::getAnonymousUser();

    // Create term allowed for anonymous.
    $term = $this->createTestAccessTaxonomyTerm([RoleInterface::ANONYMOUS_ID]);

    $node = $this->createRestrictedNode($this->editorUser, 'en', $term, TRUE);

    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);
  }

}
