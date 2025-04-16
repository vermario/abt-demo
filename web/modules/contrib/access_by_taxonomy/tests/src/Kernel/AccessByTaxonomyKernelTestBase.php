<?php

namespace Drupal\Tests\access_by_taxonomy\Kernel;

use Drupal\Core\Database\Database;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\node\Kernel\NodeAccessTestBase;
use Drupal\access_by_taxonomy\AccessByTaxonomyService;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Class AccessByTaxonomyKernelTestBase provides common functionality for tests.
 *
 * @package Drupal\Tests\access_by_taxonomy\Kernel
 */
abstract class AccessByTaxonomyKernelTestBase extends NodeAccessTestBase {

  const NO_VIEW_NO_UPDATE_NO_DELETE = ['view' => FALSE, 'update' => FALSE, 'delete' => FALSE];
  const YES_VIEW_NO_UPDATE_NO_DELETE = ['view' => TRUE, 'update' => FALSE, 'delete' => FALSE];
  const YES_VIEW_YES_UPDATE_YES_DELETE = ['view' => TRUE, 'update' => TRUE, 'delete' => TRUE];
  const YES_VIEW_YES_UPDATE_NO_DELETE = ['view' => TRUE, 'update' => TRUE, 'delete' => FALSE];

  const ROLE_EDITOR = 'editor';
  const ROLE_EDITOR_ALL_PAGES = 'editor_all_pages';
  const ROLE_ADMINISTRATOR = 'administrator';

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * Normal user (authenticated).
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $normalUser;

  /**
   * Editor user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $editorUser;

  /**
   * Editor all pages user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $editorAllPagesUser;

  /**
   * A second Editor user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editorUserNotOwner;


  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'taxonomy',
    'node',
    'user',
    'text',
    'field',
    'system',
    'access_by_taxonomy',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    ConfigurableLanguage::createFromLangcode('en')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();

    $this->installSchema('access_by_taxonomy', ['access_by_taxonomy_role_gid']);
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig('access_by_taxonomy');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->createTestVocabularies();
    $this->createPageNodeType();
    $this->createUntranslatedNodeType();
    // Give anon users permission to access content.
    $this->config('user.role.' . RoleInterface::ANONYMOUS_ID)
      ->set('permissions', ['access content'])
      ->save();
    // Insert anonymous role.
    $nodeAccess = \Drupal::service('access_by_taxonomy.service');
    $nodeAccess->insertRoleGrant(RoleInterface::ANONYMOUS_ID);
    // Give authenticated users permission to access content.
    $this->config('user.role.' . RoleInterface::AUTHENTICATED_ID)
      ->set('permissions', ['access content'])
      ->save();
    // Insert authenticated role.
    $nodeAccess = \Drupal::service('access_by_taxonomy.service');
    $nodeAccess->insertRoleGrant(RoleInterface::AUTHENTICATED_ID);

    // Set up the editor role.
    $this->drupalCreateRole([
      'access content',
      'edit own page content',
      'view own unpublished content',
      'delete own page content',
    ], self::ROLE_EDITOR);

    // Set up the editor_all_pages role.
    $this->drupalCreateRole([
      'access content',
      'edit own page content',
      'delete own page content',
      "access by taxonomy view any page content",
    ], self::ROLE_EDITOR_ALL_PAGES);

    // Set up the administrator role.
    $this->drupalCreateRole([
      'access content',
      'administer content types',
      'administer nodes',
      'bypass node access',
    ], self::ROLE_ADMINISTRATOR);

    $this->adminUser = $this->createUserWithRole(self::ROLE_ADMINISTRATOR);
    $this->editorUser = $this->createUserWithRole(self::ROLE_EDITOR);
    $this->editorUserNotOwner = $this->createUserWithRole(self::ROLE_EDITOR);
    $this->editorAllPagesUser = $this->createUserWithRole(self::ROLE_EDITOR_ALL_PAGES);
    $this->normalUser = $this->createUser();

    $this->installConfig(['language']);
    ConfigurableLanguage::createFromLangcode('de')->save();
    // Set up language negotiation.
    $config = $this->config('language.types');
    $config->set('configurable', [
      LanguageInterface::TYPE_INTERFACE,
      LanguageInterface::TYPE_CONTENT,
    ]);
    $this->container->get('content_translation.manager')->setEnabled('node', 'page', TRUE);
    $config->save();
    $this->container->get('kernel')->rebuildContainer();
  }

  /**
   * Creates test vocabularies.
   */
  protected function createTestVocabularies(): void {
    Vocabulary::create([
      'name' => 'test',
      'vid' => 'test',
    ])->save();

    Vocabulary::create([
      'name' => 'test2',
      'vid' => 'test2',
    ])->save();
  }

  /**
   * Creates a page node type.
   */
  protected function createPageNodeType(): void {
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_tags',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();

    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_tags2',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_tags',
      'entity_type' => 'node',
      'bundle' => 'page',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_tags2',
      'entity_type' => 'node',
      'bundle' => 'page',
      'translatable' => TRUE,
    ])->save();
  }

  /**
   * Creates a page node type with untranslated fields.
   */
  protected function createUntranslatedNodeType(): void {
    $this->drupalCreateContentType([
      'type' => 'page_2',
      'name' => 'Page 2',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    // Add field tags.
    FieldConfig::create([
      'field_name' => 'field_tags',
      'entity_type' => 'node',
      'bundle' => 'page_2',
    ])->save();
  }

  /**
   * Creates and returns a user with the given role id.
   *
   * @param string $rid
   *   The role id.
   *
   * @return \Drupal\user\Entity\User
   *   The created user.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   If the user cannot be saved.
   */
  protected function createUserWithRole(string $rid): User {
    $user = $this->drupalCreateUser();
    $user->addRole($rid);
    $user->save();
    return $user;
  }

  /**
   * Creates a term with a reference to allowed roles.
   *
   * @param array $rids
   *   An array of role ids.
   * @param string|null $langcode
   *   The desired language code for the term.
   * @param \Drupal\user\UserInterface[] $users
   *   The user to allow for this term.
   */
  protected function createTestAccessTaxonomyTerm(array $rids, string | NULL $langcode = 'en', array $users = []) {
    $term = Term::create([
      'name' => !empty($rids) ? implode('_', $rids) : 'no_roles',
      'vid' => 'test',
      'langcode' => 'en',
    ]);

    if (!empty($rids) && $term->hasField(AccessByTaxonomyService::ALLOWED_ROLES_FIELD)) {
      foreach ($rids as $rid) {
        $term->{AccessByTaxonomyService::ALLOWED_ROLES_FIELD}[] = ['target_id' => $rid];
      }
    }

    if (!empty($users) && $term->hasField(AccessByTaxonomyService::ALLOWED_USERS_FIELD)) {
      foreach ($users as $user) {
        $term->{AccessByTaxonomyService::ALLOWED_USERS_FIELD}[] = ['target_id' => $user->id()];
      }
    }

    $term->save();

    return $term;
  }

  /**
   * Helper function to get out of the database the access for a node.
   *
   * @param int $nid
   *   The node id.
   * @param string $realm
   *   The realm.
   * @param string $langcode
   *   The langcode.
   * @param int|null $gid
   *   The gid to check for.
   *
   * @return array
   *   The results from the database.
   */
  protected function getAccessFromDatabase(int $nid, string $realm, string $langcode, ?int $gid = NULL): array {
    if ($gid) {
      return Database::getConnection()->query('SELECT * FROM {node_access} WHERE nid = :nid AND realm = :realm AND langcode = :langcode AND gid = :gid',
        [
          ':nid' => $nid,
          ':realm' => $realm,
          ':langcode' => $langcode,
          ':gid' => $gid,
        ])->fetchAll();
    }

    return Database::getConnection()->query('SELECT * FROM {node_access} WHERE nid = :nid AND realm = :realm AND langcode = :langcode',
      [
        ':nid' => $nid,
        ':realm' => $realm,
        ':langcode' => $langcode,
      ])->fetchAll();
  }

  /**
   * Asserts that the Database has the correct rows for the realm.
   *
   * @param int $nid
   *   The nid.
   * @param string $realm
   *   The realm.
   * @param string $langcode
   *   The langcode.
   * @param int $expected_rows_count
   *   The expected number of rows.
   * @param int|null $gid
   *   The gid to check for.
   */
  protected function assertAccessRowsFromDatabase(int $nid, string $realm, string $langcode, int $expected_rows_count, ?int $gid = NULL): void {
    if ($gid) {
      $results = $this->getAccessFromDatabase($nid, $realm, $langcode, $gid);
      $this->assertCount($expected_rows_count, $results, "Realm: $realm, Langcode: $langcode, Gid: $gid, nid: $nid. Expected: $expected_rows_count, got: " . count($results));
      return;
    }
    $results = $this->getAccessFromDatabase($nid, $realm, $langcode);
    $this->assertCount($expected_rows_count, $results, "Realm: $realm, Langcode: $langcode, nid: $nid. Expected: $expected_rows_count, got: " . count($results));
  }

  /**
   * Asserts that the Database has the correct rows for the realm.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The nid.
   * @param bool $restricted
   *   Is the node restricted.
   */
  protected function assertAccessRowsFromDatabaseForNode(Node $node, bool $restricted): void {
    $nid = (int) $node->id();
    $published = $node->isPublished();
    if (!$node->isDefaultTranslation()) {
      $node = $node->getTranslation($node->language()->getId());
    }

    if ($published && $restricted) {
      // Published and restricted: ROLE + VIEW_ANY.
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::ROLE_REALM, $node->language()->getId(), 1);
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::USER_REALM, $node->language()->getId(), 0);
      $this->assertAccessRowsFromDatabase($nid, 'all', $node->language()->getId(), 0);
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::OWNER_REALM, $node->language()->getId(), 1, $node->getOwnerId());
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::VIEW_ANY_REALM . $node->getType(), $node->language()->getId(), 1, 1);
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::PUBLIC_REALM . $node->getType(), $node->language()->getId(), 0);
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::OWN_UNPUBLISHED_REALM, $node->language()->getId(), 0);

      return;
    }
    if ($published) {
      // Published and unrestricted: PUBLIC_REALM.
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::ROLE_REALM, $node->language()->getId(), 0);
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::USER_REALM, $node->language()->getId(), 0);
      $this->assertAccessRowsFromDatabase($nid, 'all', $node->language()->getId(), 0);
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::OWNER_REALM, $node->language()->getId(), 1, $node->getOwnerId());
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::VIEW_ANY_REALM . $node->bundle(), $node->language()->getId(), 0, 1);
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::PUBLIC_REALM, $node->language()->getId(), 1, 1);
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::OWN_UNPUBLISHED_REALM, $node->language()->getId(), 0);

      return;
    }
    if ($restricted) {
      // Unpublished and restricted: OWN_UNPUBLISHED, VIEW_ANY.
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::ROLE_REALM, $node->language()->getId(), 0);
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::USER_REALM, $node->language()->getId(), 0);
      $this->assertAccessRowsFromDatabase($nid, 'all', $node->language()->getId(), 0);
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::OWN_UNPUBLISHED_REALM, $node->language()->getId(), 1, $node->getOwnerId());
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::OWNER_REALM, $node->language()->getId(), 0);
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::VIEW_ANY_REALM . $node->getType(), $node->language()->getId(), 1, 1);
      $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::PUBLIC_REALM, $node->language()->getId(), 0, 1);
    }
    // Unpublished and unrestricted: OWN_UNPUBLISHED, VIEW_ANY.
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::ROLE_REALM, $node->language()->getId(), 0);
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::USER_REALM, $node->language()->getId(), 0);
    $this->assertAccessRowsFromDatabase($nid, 'all', $node->language()->getId(), 0);
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::OWN_UNPUBLISHED_REALM, $node->language()->getId(), 1, $node->getOwnerId());
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::OWNER_REALM, $node->language()->getId(), 0);
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::VIEW_ANY_REALM . $node->getType(), $node->language()->getId(), 1, 1);
    $this->assertAccessRowsFromDatabase($nid, AccessByTaxonomyService::PUBLIC_REALM, $node->language()->getId(), 0, 1);
  }

  /**
   * Creates an unpublished unrestricted node.
   *
   * @param \Drupal\user\UserInterface $owner
   *   The owner of the node.
   * @param string $langcode
   *   The language of the node.
   * @param bool $published
   *   Whether the node is published.
   *
   * @return \Drupal\node\NodeInterface
   *   The node.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createUnrestrictedNode(UserInterface $owner, string $langcode = 'en', bool $published = FALSE): NodeInterface {
    $node = $this->createNode([
      'type' => 'page',
      'title' => 'Unrestricted',
      'langcode' => $langcode,
    ]);
    if (!$published) {
      $node->setTitle($node->getTitle() . ' unpublished');
      $node->setUnpublished();
    }
    else {
      $node->setTitle($node->getTitle() . ' published');
    }
    $node->setOwner($owner);
    $node->save();
    return $node;
  }

  /**
   * Creates an unpublished restricted node.
   *
   * @param \Drupal\user\UserInterface $owner
   *   The owner of the node.
   * @param string $langcode
   *   The language of the node.
   * @param \Drupal\taxonomy\Entity\Term|null $term
   *   The term to restrict the node.
   * @param bool $published
   *   Whether the node is published.
   *
   * @return \Drupal\node\NodeInterface
   *   The node.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createRestrictedNode(UserInterface $owner, string $langcode = 'en', ?Term $term = NULL, bool $published = FALSE): NodeInterface {
    if ($term == NULL) {
      $term = $this->createTestAccessTaxonomyTerm([self::ROLE_EDITOR]);
    }

    $node = $this->createNode([
      'type' => 'page',
      'title' => 'Restricted',
      'langcode' => $langcode,
      'field_tags' => [
        [
          'target_id' => $term->id(),
        ],
      ],
    ]);
    if (!$published) {
      $node->setTitle($node->getTitle() . ' unpublished');
      $node->setUnpublished();
    }
    else {
      $node->setTitle($node->getTitle() . ' published');
    }
    $node->setOwner($owner);
    $node->save();
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function assertNodeAccess(array $ops, NodeInterface $node, AccountInterface $account): void {
    // Check if the node would be shown in a query for the user:
    $this->assertEquals($this->nodeShowsInQueryForUser($node, $account), $ops['view']);
    // Then run the parent function we are extending.
    parent::assertNodeAccess($ops, $node, $account);
  }

  /**
   * Check if a node would be shown to a user in the context of entity query.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check for.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to check for.
   *
   * @return bool
   *   True if the entity query would include the node, false otherwise.
   */
  protected function nodeShowsInQueryForUser(NodeInterface $node, AccountInterface $user): bool {
    // Query for the specified node, passing
    // the related metadata to the query:
    $query = \Drupal::entityQuery('node')
      ->condition('nid', $node->id())
      ->addMetaData('account', $user)
      ->addMetaData('langcode', $node->language()->getId())
      ->accessCheck(TRUE);

    // If the node is not in the current language,
    // add the langcode to the query metadata:
    $currentLanguage = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if ($node->language()->getId() !== $currentLanguage) {
      $query->addMetaData('langcode', $node->language()->getId());
    }

    $result = $query->execute();
    return !empty($result);
  }

}
