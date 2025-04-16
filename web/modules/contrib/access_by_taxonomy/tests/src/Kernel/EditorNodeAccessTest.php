<?php

namespace Drupal\Tests\access_by_taxonomy\Kernel;

/**
 * Class EditorNodeAccessTest tests access to nodes for the editor role.
 *
 * @package Drupal\Tests\access_by_taxonomy\Kernel
 */
class EditorNodeAccessTest extends AccessByTaxonomyKernelTestBase {

  /**
   * Editor Owner Unpublished Unrestricted.
   */
  public function testEditorOwnerUnpublishedUnrestricted() {
    $user = $this->editorUser;
    $node = $this->createUnrestrictedNode($user);

    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, FALSE);
  }

  /**
   * Editor Owner Unpublished Restricted.
   */
  public function testEditorOwnerUnpublishedRestricted() {
    $user = $this->editorUser;
    $term = $this->createTestAccessTaxonomyTerm([self::ROLE_ADMINISTRATOR]);

    $node = $this->createRestrictedNode($user, 'en', $term, FALSE);

    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);
  }

  /**
   * Editor Owner Unpublished Restricted Allowed.
   */
  public function testEditorOwnerUnpublishedRestrictedAllowed() {
    $user = $this->editorUser;
    // Create term allowed for editor.
    $term = $this->createTestAccessTaxonomyTerm([self::ROLE_EDITOR]);

    $node = $this->createRestrictedNode($user, 'en', $term, FALSE);

    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);
  }

  /**
   * Editor Owner Published Unrestricted.
   */
  public function testEditorOwnerPublishedUnrestricted() {
    $user = $this->editorUser;
    $node = $this->createUnrestrictedNode($user, 'en', TRUE);

    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, FALSE);
  }

  /**
   * Editor Owner Published Restricted.
   */
  public function testEditorOwnerPublishedRestricted() {
    $user = $this->editorUser;
    $term = $this->createTestAccessTaxonomyTerm([self::ROLE_ADMINISTRATOR]);

    $node = $this->createRestrictedNode($user, 'en', $term, TRUE);
    // This checks the ACCESS_BY_TAXONOMY_OWNER_REALM:
    // testEditorNotOwnerPublishedRestrictedAllowed makes sure that other users
    // with the same role cannot access the node.
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $user);

    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);
  }

  /**
   * Editor Owner Published Restricted allowed.
   */
  public function testEditorOwnerPublishedRestrictedAllowed() {
    $user = $this->editorUser;
    $term = $this->createTestAccessTaxonomyTerm([self::ROLE_EDITOR]);

    $node = $this->createRestrictedNode($user, 'en', $term, TRUE);

    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $user);
    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);
  }

  /**
   * Editor NotOwner Unpublished Unrestricted.
   */
  public function testEditorNotOwnerUnpublishedUnrestricted() {
    $author_editor = $this->editorUser;
    $not_owner_editor = $this->editorUserNotOwner;
    $not_owner_editor_all_pages = $this->editorAllPagesUser;
    $node = $this->createUnrestrictedNode($author_editor);

    // Because the non-owner editor user does not have the
    // 'access by taxonomy view any page content'
    // permission, they should not have access to the node.
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $not_owner_editor);
    // However, a non-owner editor with the
    // 'access by taxonomy view any page content'
    // permission should have access to the node.
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $not_owner_editor_all_pages);

    $this->assertAccessRowsFromDatabaseForNode($node, FALSE);
  }

  /**
   * Editor NotOwner Unpublished Restricted.
   */
  public function testEditorNotOwnerUnpublishedRestricted() {
    $author_editor = $this->editorUser;
    $not_owner_editor = $this->editorUserNotOwner;
    $not_owner_editor_all_pages = $this->editorAllPagesUser;
    $term = $this->createTestAccessTaxonomyTerm([self::ROLE_ADMINISTRATOR], 'en');

    $node = $this->createRestrictedNode($author_editor, 'en', $term, FALSE);

    // Because the non-owner editor user does not have the
    // 'access by taxonomy view any page content'
    // permission, they should not have access to the node.
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $not_owner_editor);
    // However, a non-owner editor with the
    // 'access by taxonomy view any page content'
    // permission should have access to the node.
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $not_owner_editor_all_pages);

    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);
  }

  /**
   * Editor NotOwner Published Restricted.
   */
  public function testEditorNotOwnerPublishedRestricted() {
    $author_editor = $this->editorUser;
    $not_owner_editor = $this->editorUserNotOwner;
    $not_owner_editor_all_pages = $this->editorAllPagesUser;
    $term = $this->createTestAccessTaxonomyTerm([self::ROLE_ADMINISTRATOR], 'en');

    $node = $this->createRestrictedNode($author_editor, 'en', $term, TRUE);

    // Because the non-owner editor user does not have the
    // 'access by taxonomy view any page content'
    // permission, they should not have access to the node.
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $not_owner_editor);

    // However, a non-owner editor with the
    // 'access by taxonomy view any page content'
    // permission should have access to the node.
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $not_owner_editor_all_pages);

    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);
  }

  /**
   * Editor NotOwner Published Unrestricted.
   */
  public function testEditorNotOwnerPublishedUnrestricted() {
    $author_editor = $this->editorUser;
    $not_owner_editor = $this->editorUserNotOwner;
    $not_owner_editor_all_pages = $this->editorAllPagesUser;

    $node = $this->createUnrestrictedNode($author_editor, 'en', TRUE);

    // The node is not restricted.
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $not_owner_editor);
    // A non-owner editor with the
    // 'access by taxonomy view any page content'
    // permission should have access to the node.
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $not_owner_editor_all_pages);

    $this->assertAccessRowsFromDatabaseForNode($node, FALSE);
  }

  /**
   * Editor NotOwner Published Restricted Allowed.
   */
  public function testEditorNotOwnerPublishedRestrictedAllowed() {
    $author_editor = $this->editorUser;
    $not_owner_editor = $this->editorUserNotOwner;
    $not_owner_editor_all_pages = $this->editorAllPagesUser;
    $term = $this->createTestAccessTaxonomyTerm([self::ROLE_EDITOR], 'en');
    $node = $this->createRestrictedNode($author_editor, 'en', $term, TRUE);

    // The node is restricted and allowed.
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $not_owner_editor);
    // The owner can also update and delete.
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $author_editor);
    // A non-owner editor with the
    // 'access by taxonomy view any page content'
    // permission should have access to the node.
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $not_owner_editor_all_pages);

    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);
  }

  /**
   * Editor NotOwner Unpublished Restricted Allowed.
   */
  public function testEditorNotOwnerUnpublishedRestrictedAllowed() {
    $author_editor = $this->editorUser;
    $not_owner_editor = $this->editorUserNotOwner;
    $not_owner_editor_all_pages = $this->editorAllPagesUser;

    $term = $this->createTestAccessTaxonomyTerm([self::ROLE_EDITOR], 'en');
    $node = $this->createRestrictedNode($author_editor, 'en', $term, FALSE);

    // The node is restricted and allowed, but not published.
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $not_owner_editor);
    // The owner can update and delete.
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $author_editor);
    // A non-owner editor with the
    // 'access by taxonomy view any page content'
    // permission should have access to the node.
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $not_owner_editor_all_pages);

    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);
  }

}
