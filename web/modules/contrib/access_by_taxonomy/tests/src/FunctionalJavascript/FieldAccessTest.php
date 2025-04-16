<?php

namespace Drupal\Tests\access_by_taxonomy\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\UserInterface;

/**
 * Test the fields are not available if permissions are not there.
 */
class FieldAccessTest extends WebDriverTestBase {
  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';
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
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * Editor user with access to Abt fields.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $editorWithAbtAccessRights;

  /**
   * Editor user with access to Administer Taxonomy.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $editorWithAdministerTaxonomyRights;

  /**
   * Editor user without access to AbT fields.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $editorWithoutAbtAccessRights;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();
    // Create a vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
    ]);
    $vocabulary->save();

    // Add a language.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    // Create an admin user.
    $this->adminUser = $this->drupalCreateUser([], 'administer', TRUE);
    // Create a user with permission to edit terms and their access fields.
    $this->editorWithAbtAccessRights = $this->drupalCreateUser([
      'access by taxonomy administer access for terms in tags',
      'edit terms in tags',
    ], 'editorWithAbtAccess', FALSE);
    // Create a user with permission to administer taxonomy.
    $this->editorWithAdministerTaxonomyRights = $this->drupalCreateUser([
      'administer taxonomy',
    ], 'editorWithAdministerTaxonomy', FALSE);
    // Create a user with permission to edit terms, but not their access fields.
    $this->editorWithoutAbtAccessRights = $this->drupalCreateUser([
      'edit terms in tags',
    ], 'editorWithoutAbtAccess', FALSE);

    // Add the allowed roles and users fields to the vocabulary.
    access_by_taxonomy_install();

    // Add a term in the tags vocabulary.
    Term::create([
      'name' => 'Test term',
      'vid' => 'tags',
      'langcode' => 'en',
    ])->save();
  }

  /**
   * Test that the term edit page shows our fields.
   */
  public function testFieldsAccessAsAdminUser(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/taxonomy/term/1/edit');
    $this->assertSession()->elementExists('css', 'input[name="name[0][value]"]');
    $this->assertSession()->elementAttributeContains('css', 'input[name="name[0][value]"]', 'value', 'Test term');
    $this->assertSession()->elementExists('css', 'input[name="field_allowed_users[0][target_id]"]');
    $this->assertSession()->fieldExists('field_allowed_users[0][target_id]');
    $this->assertSession()->elementExists('css', 'input[name^=field_allowed_roles]');
  }

  /**
   * Test that the term edit page shows our fields: Abt permissions.
   */
  public function testFieldsAccessWithAbtPermission(): void {
    $this->drupalLogin($this->editorWithAbtAccessRights);
    $this->drupalGet('/taxonomy/term/1/edit');
    $this->assertSession()->elementExists('css', 'input[name="name[0][value]"]');
    $this->assertSession()->elementAttributeContains('css', 'input[name="name[0][value]"]', 'value', 'Test term');
    $this->assertSession()->fieldExists('field_allowed_users[0][target_id]');
    $this->assertSession()->elementExists('css', 'input[name^=field_allowed_roles]');
  }

  /**
   * Test that the term edit page shows our fields: Administer Taxonomy.
   */
  public function testFieldsAccessWithAdministerTaxonomyPermission(): void {
    $this->drupalLogin($this->editorWithAdministerTaxonomyRights);
    $this->drupalGet('/taxonomy/term/1/edit');
    $this->assertSession()->elementExists('css', 'input[name="name[0][value]"]');
    $this->assertSession()->elementAttributeContains('css', 'input[name="name[0][value]"]', 'value', 'Test term');
    $this->assertSession()->fieldExists('field_allowed_users[0][target_id]');
    $this->assertSession()->elementExists('css', 'input[name^=field_allowed_roles]');
  }

  /**
   * Test that the term edit page doesn't show our fields for others.
   */
  public function testFieldsAccessWithoutPermissions(): void {
    $this->drupalLogin($this->editorWithoutAbtAccessRights);
    $this->drupalGet('/taxonomy/term/1/edit');
    $this->assertSession()->elementExists('css', 'input[name="name[0][value]"]');
    $this->assertSession()->elementAttributeContains('css', 'input[name="name[0][value]"]', 'value', 'Test term');
    $this->assertSession()->fieldNotExists('input[name="field_allowed_users[0][target_id]"]');
    $this->assertSession()->elementNotExists('css', 'input[name^=field_allowed_roles]');
  }

}
