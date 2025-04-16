<?php

namespace Drupal\Tests\access_by_taxonomy\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\UserInterface;

/**
 * Test the fields are not translatable.
 */
class FieldConfigTest extends WebDriverTestBase {
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

    // Add a language.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    // Create admin user.
    $this->adminUser = $this->drupalCreateUser([], NULL, TRUE);

    // Add the allowed roles and users fields to the vocabulary.
    access_by_taxonomy_install();

  }

  /**
   * Test that the translation table doesn't have our fields in it.
   */
  public function testConfigNotTranslatable(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/regional/content-language');
    $page = $this->getSession()->getPage();
    $page->find('css', 'input#edit-entity-types-taxonomy-term')->check();
    $this->getSession()->wait(1000);
    $page->find('css', 'details#edit-settings-taxonomy-term')->click();
    $page->find('css', 'input#edit-settings-taxonomy-term-tags-translatable')->check();
    $this->getSession()->wait(1000);
    $this->assertSession()->pageTextNotContains('Allowed roles');
    $this->assertSession()->pageTextNotContains('Allowed users');
  }

}
