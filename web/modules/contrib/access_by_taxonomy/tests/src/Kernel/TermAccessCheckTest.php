<?php

namespace Drupal\Tests\access_by_taxonomy\Kernel;

use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * Class TermAccessCheckTest tests access for terms.
 *
 * @package Drupal\Tests\access_by_taxonomy\Kernel
 */
class TermAccessCheckTest extends AccessByTaxonomyKernelTestBase {

  /**
   * Tests that when the access is not set on a term, everyone can view it.
   */
  public function testTermNotRestricted() {
    // Create a term with no allowed roles.
    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = $this->createTestAccessTaxonomyTerm([]);
    $test = $term->access('view', $this->editorUser);
    $this->assertTrue($test);
    $test = $term->access('view', $this->editorUserNotOwner);
    $this->assertTrue($test);
    $user = User::getAnonymousUser();
    $test = $term->access('view', $user);
    $this->assertTrue($test);
  }

  /**
   * Tests that only the allowed roles can view a term.
   */
  public function testTermRestricted() {
    // Create a term with authenticated as allowed role.
    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = $this->createTestAccessTaxonomyTerm([RoleInterface::AUTHENTICATED_ID]);
    $test = $term->access('view', $this->editorUser);
    $this->assertTrue($test);
    $test = $term->access('view', $this->editorUserNotOwner);
    $this->assertTrue($test);
    $test = $term->access('view', $this->normalUser);
    $this->assertTrue($test);
    $user = User::getAnonymousUser();
    $test = $term->access('view', $user);
    $this->assertFalse($test);
  }

  /**
   * Tests that only the allowed user can view a term.
   */
  public function testTermRestrictedByUser() {
    // Create a term with only a specific user allowed.
    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = $this->createTestAccessTaxonomyTerm([], 'en', [$this->normalUser]);
    $test = $term->access('view', $this->editorUser);
    $this->assertFalse($test);
    $test = $term->access('view', $this->normalUser);
    $this->assertTrue($test);
    $user = User::getAnonymousUser();
    $test = $term->access('view', $user);
    $this->assertFalse($test);
  }

}
