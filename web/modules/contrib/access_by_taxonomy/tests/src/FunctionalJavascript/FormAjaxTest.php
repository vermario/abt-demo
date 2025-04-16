<?php

namespace Drupal\Tests\access_by_taxonomy\FunctionalJavascript;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\access_by_taxonomy\AccessByTaxonomyService;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\UserInterface;

/**
 * Test the form is altered with AJAX.
 */
class FormAjaxTest extends WebDriverTestBase {
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
   * Editor user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $editorUser;

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * Roles in the system.
   *
   * @var array
   */
  protected array $roles = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();
    // Create a content type and a vocabulary.
    $this->drupalCreateContentType([
      'name' => 'Article',
      'type' => 'article',
    ]);
    $vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
    ]);
    $vocabulary->save();
    // Create tag field for the CT.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_tags',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_tags',
      'entity_type' => 'node',
      'bundle' => 'article',
      'settings' => [
        'handler' => 'default',
      ],
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('field_tags')
      ->save();
    $display_repository->getFormDisplay('node', 'article', 'default')
      ->setComponent('field_tags', [
        'type' => 'entity_reference_autocomplete_tags',
      ])
      ->save();

    // Add a language.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    // Create roles and users.
    $this->drupalCreateRole(['access content',
      'edit own article content',
      'delete own article content',
      "access by taxonomy view any article content",
      "edit any article content",
      "edit terms in tags",
      "administer modules",
      "administer taxonomy",
      "administer content types",
    ], 'editor', 'editor role');
    $this->editorUser = $this->drupalCreateUser();
    $this->editorUser->addRole('editor');
    $this->editorUser->save();

    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'access administration pages',
      'administer site configuration',
      'administer users',
      'administer permissions',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer nodes',
      'administer node form display',
      'bypass node access',
      'administer taxonomy',
    ]);
    $this->drupalLogin($this->adminUser);
    // Add the allowed roles and users fields to the vocabulary.
    access_by_taxonomy_install();

    // Get the roles and create one term per role.
    /** @var \Drupal\user\Entity\Role[] $roles_entities */
    $roles_entities = \Drupal::service('entity_type.manager')->getStorage('user_role')->loadMultiple();
    $role_labels = [];
    foreach ($roles_entities as $role) {
      $role_labels[$role->id()] = $role->label();
    }
    $this->roles = $role_labels;
    foreach ($role_labels as $role_id => $role_label) {
      $term = Term::create([
        'name' => $role_label,
        'vid' => 'tags',
        'langcode' => 'en',
      ]);
      $term->{AccessByTaxonomyService::ALLOWED_ROLES_FIELD}[] = ['target_id' => $role_id];
      $term->save();
    }
  }

  /**
   * Test that the form is altered with AJAX for the autocomplete format.
   */
  public function testFormAlterAutocompleteAjax(): void {
    // Set the field to be entity_reference_autocomplete.
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article', 'default')
      ->setComponent('field_tags', [
        'type' => 'entity_reference_autocomplete',
      ])
      ->save();

    $node = $this->DrupalCreateNode([
      'type' => 'article',
      'title' => 'Unrestricted',
      'langcode' => 'en',
      'uid' => $this->editorUser->id(),
    ]);

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $node->id() . '/edit');
    // The node is public.
    $this->assertSession()->pageTextContains('No access restrictions are set for this content');
    // Check that the form has the tags field.
    // $this->getSession()->wait(3000000);
    $page = $this->getSession()->getPage();
    $this->assertSession()->elementExists('css', '#edit-access-by-taxonomy-show-changes');
    $button = $page->findById('edit-access-by-taxonomy-show-changes');
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();
    $autocomplete_field = $page->findById('edit-field-tags-0-target-id');
    // We can cause the autocomplete to happen by setting the value in the
    // element.
    $random_role = array_rand($this->roles);
    $random_role = $this->roles[$random_role];
    $autocomplete_field->setValue($random_role);
    $this->getSession()
      ->getDriver()
      ->keyDown($autocomplete_field->getXpath(), ' ');
    $assert->waitOnAutocomplete();
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Changes in access settings');
    $this->assertSession()->elementExists('css', '.diff-deletedline .diffchange');
    $this->assertSession()->elementExists('css', '.diff-addedline .diffchange');
    $this->assertSession()->elementTextContains('css', '.diff-deletedline .diffchange', 'No access restrictions are set for this content.');
    // $this->getSession()->wait(3000000);
    $this->assertSession()->elementExists('css', 'tbody tr:first-child .diff-addedline .diffchange');
    $this->assertSession()->elementTextContains('css', 'tbody tr:first-child .diff-addedline .diffchange', 'Allowed role in terms:');
    $this->getSession()->wait(300);
    $this->assertSession()->elementTextContains('css', 'tbody tr:first-child .diff-addedline .diffchange', Html::escape($random_role));
    $close = $page->find('css', '.ui-icon-closethick');
    $close->click();
    $this->getSession()->wait(300);
    $this->submitForm([], 'Add another item');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->wait(300);
    $this->assertSession()->fieldExists('field_tags[1][target_id]');
    $autocomplete_field_2 = $page->findField('field_tags[1][target_id]');
    $random_role2 = array_rand($this->roles);
    $random_role2 = $this->roles[$random_role2];
    while ($random_role2 === $random_role) {
      $random_role2 = array_rand($this->roles);
      $random_role2 = $this->roles[$random_role2];
    }
    $autocomplete_field_2->setValue($random_role2);
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Changes in access settings');
    $this->assertSession()->elementExists('css', '.diff-deletedline');
    $this->assertSession()->elementExists('css', '.diff-addedline');
    $this->assertSession()->elementTextContains('css', '.diff-deletedline', 'No access restrictions are set for this content.');
    $this->assertSession()->elementTextContains('css', 'tbody tr:first-child .diff-addedline .diffchange', 'Allowed roles in terms:');
    $this->assertSession()->elementTextContains('css', 'tbody tr:first-child .diff-addedline .diffchange', Html::escape($random_role));
    $this->assertSession()->elementTextContains('css', 'tbody tr:first-child .diff-addedline .diffchange', Html::escape($random_role2));
    $close->click();
    /* $this->getSession()->wait(300000);
    $txt = $page->find(
    'css',
    'tbody tr:first-child .diff-addedline .diffchange'
    )->getText();
    var_dump($txt);*/
    $autocomplete_field = $page->findField('field_tags[0][target_id]');
    $autocomplete_field->setValue('');
    $autocomplete_field_2->setValue('');
    $button->click();
    $this->getSession()->wait(300);
    $this->assertSession()->elementExists('css', '#access_by_taxonomy_status_messages [data-drupal-message-type="status"] .messages__content');
    $this->assertSession()->elementTextContains('css', '#access_by_taxonomy_status_messages [data-drupal-message-type="status"] .messages__content', 'No changes in access settings.');

  }

}
