<?php

namespace Drupal\Tests\access_by_taxonomy\Kernel;

use Drupal\user\Entity\User;

/**
 * Class MultilanguageTest tests access for translations in different statuses.
 *
 * @package Drupal\Tests\access_by_taxonomy\Kernel
 */
class MultilanguageTest extends AccessByTaxonomyKernelTestBase {

  /**
   * Test access for a published node with an unpublished translation.
   */
  public function testPublishedOriginalUnpublishedTranslationRestricted() {
    // Create a node, set it to published.
    $node = $this->createRestrictedNode($this->editorUser, 'en', NULL, TRUE);
    // Add a translation for de, set the translation to unpublished.
    $node->addTranslation('de', $node->toArray());
    $node->save();
    $de = $node->getTranslation('de');
    $de->setUnpublished();
    $de->save();

    // Original node check:
    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, User::getAnonymousUser());
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $this->normalUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $this->editorUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $this->adminUser);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $this->editorUserNotOwner);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $this->editorAllPagesUser);

    // Unpublished translated node check:
    $this->assertAccessRowsFromDatabaseForNode($de, TRUE);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $de, User::getAnonymousUser());
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $de, $this->normalUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $de, $this->editorUser);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $de, $this->editorAllPagesUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $de, $this->adminUser);

  }

  /**
   * Test access for an unpublished node with a published translation.
   */
  public function testUnpublishedOriginalPublishedTranslationRestricted() {
    // Create a node, set it to unpublished.
    $node = $this->createRestrictedNode($this->editorUser, 'en', NULL, FALSE);
    // Add a translation for de, set the translation to published.
    $node->addTranslation('de', $node->toArray());
    $node->save();
    $de = $node->getTranslation('de');
    $de->setPublished();
    $de->save();

    // Original node check:
    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, User::getAnonymousUser());
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $this->normalUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $this->editorUser);
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $this->editorUserNotOwner);
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $this->normalUser);

    // Published translated node check:
    $this->assertAccessRowsFromDatabaseForNode($de, TRUE);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $de, User::getAnonymousUser());
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $de, $this->normalUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $de, $this->editorUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $de, $this->adminUser);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $de, $this->editorUserNotOwner);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $de, $this->editorAllPagesUser);

  }

  /**
   * Test access for an unpublished node with an unpublished translation.
   */
  public function testUnpublishedOriginalUnpublishedTranslationRestricted() {
    // Create a node, set it to unpublished.
    $node = $this->createRestrictedNode($this->editorUser, 'en', NULL, FALSE);
    // Add a translation for de:
    $node->addTranslation('de', $node->toArray());
    $node->save();
    $de = $node->getTranslation('de');

    // Original node check:
    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, User::getAnonymousUser());
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $this->normalUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $this->editorUser);
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $this->editorUserNotOwner);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $this->editorAllPagesUser);

    // Unpublished translated node check:
    $this->assertAccessRowsFromDatabaseForNode($de, TRUE);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $de, User::getAnonymousUser());
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $de, $this->normalUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $de, $this->editorUser);
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $de, $this->editorUserNotOwner);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $de, $this->adminUser);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $de, $this->editorAllPagesUser);

  }

  /**
   * Test access for a published node with a published translation.
   */
  public function testPublishedOriginalPublishedTranslationRestricted() {
    // Create a node, set it to published.
    $node = $this->createRestrictedNode($this->editorUser, 'en', NULL, TRUE);
    // Add a translation for de:
    $node->addTranslation('de', $node->toArray());
    $node->save();
    $de = $node->getTranslation('de');

    // Original node check:
    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, User::getAnonymousUser());
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $this->normalUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $this->editorUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $this->adminUser);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $this->editorUserNotOwner);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $this->editorAllPagesUser);

    // Published translated node check:
    $this->assertAccessRowsFromDatabaseForNode($de, TRUE);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $de, User::getAnonymousUser());
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $de, $this->normalUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $de, $this->editorUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $de, $this->adminUser);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $de, $this->editorUserNotOwner);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $de, $this->editorAllPagesUser);

  }

  /**
   * Test a published restricted with a published unrestricted translation.
   */
  public function testPublishedOriginalRestrictedPublishedTranslationUnrestricted() {
    // Create a node, set it to published.
    $node = $this->createRestrictedNode($this->editorUser, 'en', NULL, TRUE);
    // Add a translation for de:
    $node->addTranslation('de', $node->toArray());
    $node->save();
    $de = $node->getTranslation('de');
    $de->set('field_tags', []);
    $de->save();

    // Original node check:
    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, User::getAnonymousUser());
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $this->normalUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $this->editorUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $this->adminUser);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $this->editorUserNotOwner);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $this->editorAllPagesUser);

    // Published unrestricted translated node check:
    $this->assertAccessRowsFromDatabaseForNode($de, FALSE);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $de, User::getAnonymousUser());
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $de, $this->normalUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $de, $this->editorUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $de, $this->adminUser);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $de, $this->editorUserNotOwner);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $de, $this->editorAllPagesUser);

  }

  /**
   * Test a published unrestricted with a published restricted translation.
   */
  public function testPublishedOriginalUnrestrictedPublishedTranslationRestricted() {
    // Create a node, set it to published.
    $node = $this->createRestrictedNode($this->editorUser, 'en', NULL, TRUE);
    // Add a translation for de:
    $node->addTranslation('de', $node->toArray());
    $node->save();
    $de = $node->getTranslation('de');
    $node->set('field_tags', []);
    $node->save();

    // Original node check:
    $this->assertAccessRowsFromDatabaseForNode($node, FALSE);

    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, User::getAnonymousUser());
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $this->normalUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $this->editorUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $this->adminUser);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $this->editorUserNotOwner);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $this->editorAllPagesUser);

    // Published translated node check:
    $this->assertAccessRowsFromDatabaseForNode($de, TRUE);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $de, User::getAnonymousUser());
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $de, $this->normalUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $de, $this->editorUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $de, $this->adminUser);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $de, $this->editorUserNotOwner);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $de, $this->editorAllPagesUser);

  }

  /**
   * Test access for a published node with an unpublished translation.
   */
  public function testPublishedOriginalPublishedTranslationRestrictedDifferentOwners() {

    $author_original_node = $this->editorUser;
    $author_translation_node = $this->editorUserNotOwner;
    // Create a term allowed for admins.
    $term = $this->createTestAccessTaxonomyTerm([self::ROLE_ADMINISTRATOR]);

    // Create a node, set it to published.
    $node = $this->createRestrictedNode($author_original_node, 'en', $term, TRUE);
    // Add a translation for de, set the owner of the translation
    // to a different user.
    $node->addTranslation('de', $node->toArray());
    $node->save();
    $de = $node->getTranslation('de');
    $de->setOwner($author_translation_node);
    $de->save();

    // Original node check:
    $this->assertAccessRowsFromDatabaseForNode($node, TRUE);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, User::getAnonymousUser());
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $this->normalUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $author_original_node);
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $node, $author_translation_node);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $node, $this->adminUser);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $node, $this->editorAllPagesUser);

    // Translated node check with a different user:
    $this->assertAccessRowsFromDatabaseForNode($de, TRUE);

    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $de, User::getAnonymousUser());
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $de, $this->normalUser);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $de, $author_translation_node);
    $this->assertNodeAccess(self::NO_VIEW_NO_UPDATE_NO_DELETE, $de, $author_original_node);
    $this->assertNodeAccess(self::YES_VIEW_YES_UPDATE_YES_DELETE, $de, $this->adminUser);
    $this->assertNodeAccess(self::YES_VIEW_NO_UPDATE_NO_DELETE, $de, $this->editorAllPagesUser);
  }

}
