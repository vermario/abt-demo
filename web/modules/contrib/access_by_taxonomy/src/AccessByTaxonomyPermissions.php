<?php

namespace Drupal\access_by_taxonomy;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\BundlePermissionHandlerTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeTypeInterface;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for each node type.
 */
class AccessByTaxonomyPermissions implements ContainerInjectionInterface {
  use BundlePermissionHandlerTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new AccessByTaxonomyPermissions object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns an array of node type permissions.
   *
   * @return array
   *   The node type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function nodeTypePermissions(): array {
    // Generate access by taxonomy permissions for all node types.
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    return $this->generatePermissions($node_types, [$this, 'buildNodePermissions']);
  }

  /**
   * Returns an array of vocabulary permissions.
   *
   * @return array
   *   The vocabulary permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function taxonomyVocabularyPermissions() : array {
    // Generate access by taxonomy permissions for all vocabularies.
    $vocabularies = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
    return $this->generatePermissions($vocabularies, [$this, 'buildTaxonomyPermissions']);
  }

  /**
   * Returns a list of 'view any %type_name content' permissions.
   *
   * @param \Drupal\node\NodeTypeInterface $type
   *   The node type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildNodePermissions(NodeTypeInterface $type): array {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "access by taxonomy view any $type_id content" => [
        'title' => $this->t('View any %type_name content', $type_params),
      ],
    ];
  }

  /**
   * Returns a list of 'administer permissions for terms in %vocabulary' perms.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $type
   *   The vocabulary.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildTaxonomyPermissions(VocabularyInterface $type): array {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "access by taxonomy administer access for terms in $type_id" => [
        'title' => $this->t('Administer access for terms in %type_name', $type_params),
      ],
    ];
  }

}
