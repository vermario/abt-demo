<?php

declare(strict_types=1);

namespace Drupal\access_by_taxonomy;

use Drupal\Component\Diff\Diff;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Class AccessByTaxonomyService.
 *
 * Contains generic functionality for the module.
 */
final class AccessByTaxonomyService {

  use StringTranslationTrait;

  const ROLE_REALM = 'access_by_taxonomy_role';
  const USER_REALM = 'access_by_taxonomy_user';
  const OWNER_REALM = 'access_by_taxonomy_owner';
  const OWN_UNPUBLISHED_REALM = 'access_by_taxonomy_own_unpublished';
  const VIEW_ANY_REALM = 'access_by_taxonomy_view_any_';
  const PUBLIC_REALM = 'access_by_taxonomy_public';

  const ALLOWED_ROLES_FIELD = 'field_allowed_roles';
  const ALLOWED_USERS_FIELD = 'field_allowed_users';

  const DEFAULT_GRANT_VIEW = 1;
  const DEFAULT_GRANT_UPDATE = 0;
  const DEFAULT_GRANT_DELETE = 0;

  /**
   * Constructs a new \Drupal\access_by_taxonomy\AccessByTaxonomyService object.
   */
  public function __construct(
    private readonly EntityFieldManagerInterface $entityFieldManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerChannelInterface $loggerChannelAccessByTaxonomy,
    private readonly Connection $connection,
    private readonly AccountInterface $account,
  ) {}

  /**
   * Checks whether there are taxonomy fields defined for a given node type.
   */
  public function getNodeTypeTaxonomyTermFieldDefinitions($nodeType): array {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', $nodeType);
    $return = [];
    foreach ($field_definitions as $field_definition) {
      if ($field_definition->getType() === 'entity_reference' && is_numeric(strpos($field_definition->getSetting('handler'), 'taxonomy_term'))) {
        $return[$field_definition->getName()] = $field_definition->getName();
      }
    }
    return $return;
  }

  /**
   * Determine the grants for a translation of a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node translation to examine.
   *
   * @return array
   *   An array of grants.
   */
  public function getTranslationGrants(NodeInterface $node): array {
    $restricted_grants = [];
    // Get the nid from the node.
    $nid = (int) $node->id();
    // Get the langcode of this translation.
    $langcode = $node->language()->getId();
    $authorUserId = (int) $node->getOwnerId();

    // The translation is not published.
    if (!$node->isPublished()) {
      // We add a grant to allow the author of the translation to view it
      // if they have the right permission.
      $restricted_grants[] = $this->createGrantsArray(
        $langcode,
        self::OWN_UNPUBLISHED_REALM,
        $authorUserId,
        self::DEFAULT_GRANT_VIEW,
        self::DEFAULT_GRANT_UPDATE,
        self::DEFAULT_GRANT_DELETE,
        $nid
      );

      // We add a grant specifying the node type, so that users with
      // the 'access by taxonomy view any $type_id content'
      // are able to access the translation even if it's unpublished.
      $realm = self::VIEW_ANY_REALM;
      $realm .= $node->getType();
      $restricted_grants[] = $this->createGrantsArray(
        $langcode,
        $realm,
        1,
        self::DEFAULT_GRANT_VIEW,
        self::DEFAULT_GRANT_UPDATE,
        self::DEFAULT_GRANT_DELETE,
        $nid,
      );
      return $restricted_grants;
    }
    // The node translation is published.
    $role_grants = $this->getRoleGrant();

    // In our module, grants are applied to the node via referenced taxonomy
    // terms. We need to check all the taxonomy fields that the node translation
    // has, and apply the grants accordingly.
    $taxonomy_fields = $this->getNodeTypeTaxonomyTermFieldDefinitions($node->getType());

    $written_grant_users = [];
    $written_grant_roles = [];
    if (!empty($taxonomy_fields)) {
      // The node type has taxonomy fields associated with it, let's examine
      // them:
      foreach ($taxonomy_fields as $field) {
        // Get the values of the field:
        $values = $node->get($field)->getValue();
        foreach ($values as $val) {
          if (!isset($val['target_id'])) {
            continue;
          }
          /** @var \Drupal\taxonomy\Entity\Term $term */
          $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($val['target_id']);
          if (empty($term)) {
            continue;
          }
          // We check whether any of the terms that the node translation
          // references have allowed roles or users in our special field:
          if ($term->hasField(self::ALLOWED_ROLES_FIELD)) {
            $roles = $term->get(self::ALLOWED_ROLES_FIELD)->getValue();
            foreach ($roles as $role) {
              // Sanity check. The role could be missing from the table.
              if (!isset($role_grants[$role['target_id']])) {
                $this->loggerChannelAccessByTaxonomy->error(
                  'Role :rid not found in access_by_taxonomy_role_gid table.',
                  [
                    ':rid' => $role['target_id'],
                  ]
                );
                continue;
              }
              // Sanity check. Make sure we haven't
              // written this grant already.
              if (isset($written_grant_roles[$role['target_id']])) {
                continue;
              }
              $grant_id = $role_grants[$role['target_id']];
              $written_grant_roles[$role['target_id']] = TRUE;
              $restricted_grants[] = $this->createGrantsArray($langcode,
                self::ROLE_REALM,
                (int) $grant_id,
                self::DEFAULT_GRANT_VIEW,
                self::DEFAULT_GRANT_UPDATE,
                self::DEFAULT_GRANT_DELETE,
                $nid
              );
            }
          }
          // Extract the allowed users from the term
          // and insert grants for them.
          if ($term->hasField(self::ALLOWED_USERS_FIELD)) {
            $users = $term->get(self::ALLOWED_USERS_FIELD)->getValue();
            foreach ($users as $user) {
              // Sanity check.
              // Make sure we haven't written this grant already.
              if (isset($written_grant_users[$user['target_id']])) {
                continue;
              }
              $written_grant_users[$user['target_id']] = TRUE;
              $grant_id = $user['target_id'];
              $restricted_grants[] = $this->createGrantsArray($langcode,
                self::USER_REALM,
                (int) $grant_id,
                self::DEFAULT_GRANT_VIEW,
                self::DEFAULT_GRANT_UPDATE,
                self::DEFAULT_GRANT_DELETE,
                $nid
              );
            }
          }
        }
      }
    }
    // If we have added custom grants for this node, we want to also
    // add a grant for the owner of the node to be able to view it.
    if (count($restricted_grants) > 0) {
      // Make the 'access by taxonomy view any $type_id content'
      // permission work.
      $realm = self::VIEW_ANY_REALM;
      $realm .= $node->getType();
      $restricted_grants[] = $this->createGrantsArray(
        $langcode,
        $realm,
        1,
        self::DEFAULT_GRANT_VIEW,
        self::DEFAULT_GRANT_UPDATE,
        self::DEFAULT_GRANT_DELETE,
        $nid,
      );
    }
    else {
      // The translation is not restricted to any specific role or user.
      // Then we will mark it with our public realm.
      $restricted_grants[] = $this->createGrantsArray(
        $langcode,
        self::PUBLIC_REALM,
        1,
        self::DEFAULT_GRANT_VIEW,
        self::DEFAULT_GRANT_UPDATE,
        self::DEFAULT_GRANT_DELETE,
        $nid,
      );
    }

    // We add a grant to allow the author of the translation to view it.
    $restricted_grants[] = $this->createGrantsArray(
      $langcode,
      self::OWNER_REALM,
      $authorUserId,
      self::DEFAULT_GRANT_VIEW,
      self::DEFAULT_GRANT_UPDATE,
      self::DEFAULT_GRANT_DELETE,
      $nid
    );

    return $restricted_grants;
  }

  /**
   * Creates a grants array.
   */
  public function createGrantsArray(string $langcode, string $realm, int $grant_id, int $grant_view, int $grant_update, int $grant_delete, int $nid): array {
    return [
      'langcode' => $langcode,
      'realm' => $realm,
      'gid' => $grant_id,
      'grant_view' => $grant_view,
      'grant_update' => $grant_update,
      'grant_delete' => $grant_delete,
      'nid' => $nid,
    ];
  }

  /**
   * Gets the node access records for a given node.
   */
  public function getNodeAccessRecords(int $id, string $langcode, string $realm = '*'): array {
    $query = $this->connection->select('node_access');
    $query->fields('node_access', ['nid', 'langcode', 'realm', 'gid']);
    $query->condition('nid', $id);
    $query->condition('realm', $realm);
    $query->condition('langcode', $langcode);
    try {
      return $query->execute()->fetchAll();
    }
    catch (\Exception $e) {
      $this->loggerChannelAccessByTaxonomy->error('Error fetching node access records: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Checks access to a term.
   *
   * @param int $tid
   *   The taxonomy term ID to check for.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The optional account. If not passed, the current user is used.
   *
   * @return bool
   *   Whether the user has access to the term.
   */
  public function canUserAccessTerm(int $tid, ?AccountInterface $account = NULL): bool {

    // If the account is not passed, use the currently logged-in user.
    if (!isset($account)) {
      $account = $this->account;
    }

    $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
    if (!$term) {
      return FALSE;
    }
    // Cut it short if the user has the 'bypass node access' permission.
    if ($account->hasPermission('bypass node access')) {
      return TRUE;
    }
    $allowed_roles = [];
    $allowed_users = [];
    // Check if the role field exists.
    $role_field_exists = $term->hasField(AccessByTaxonomyService::ALLOWED_ROLES_FIELD);
    if ($role_field_exists) {
      $allowed_roles = $term->get(AccessByTaxonomyService::ALLOWED_ROLES_FIELD)->getValue();
      // In case the term has specified roles check if the user has any of them.
      if ($allowed_roles) {
        $user_roles = $account->getRoles();
        $allowed_roles_simplified = [];
        foreach ($allowed_roles as $role) {
          $allowed_roles_simplified[] = $role['target_id'];
        }
        foreach ($user_roles as $role) {
          if (in_array($role, $allowed_roles_simplified)) {
            return TRUE;
          }
        }
      }
    }
    // Now check for the user field.
    $users_field_exists = $term->hasField(AccessByTaxonomyService::ALLOWED_USERS_FIELD);
    if ($users_field_exists) {
      // User has not been allowed by roles, check if in the allowed users list.
      $allowed_users = $term->get(AccessByTaxonomyService::ALLOWED_USERS_FIELD)->referencedEntities();
      if ($allowed_users) {
        $allowed_users_simplified = [];
        foreach ($allowed_users as $user) {
          $allowed_users_simplified[] = $user->id();
        }
        if (in_array($account->id(), $allowed_users_simplified)) {
          return TRUE;
        }
      }
    }
    // Allow in case no fields exists at all.
    if (!$role_field_exists && !$users_field_exists) {
      return TRUE;
    }
    // Allow in case the fields exist, but are empty.
    if (empty($allowed_roles) && empty($allowed_users)) {
      return TRUE;
    }
    // Otherwise, we would have returned TRUE already.
    return FALSE;

  }

  /**
   * Adds a field to a vocabulary.
   */
  public function addFieldToVocabulary(string $vocabulary, array $field): void {
    $field_name = array_keys($field)[0];
    $field_label = $field[$field_name];

    // Get the storage for the field storage configuration entity:
    $field_config_storage = $this->entityTypeManager->getStorage('field_storage_config');

    // Load field from configuration.
    $field_storage = $field_config_storage->load('taxonomy_term.' . $field_name);
    if ($field_storage) {
      // Define the field.
      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'field_name' => $field_name,
        'entity_type' => 'taxonomy_term',
        'bundle' => $vocabulary,
        'label' => $field_label,
        'translatable' => FALSE,
      ]);
      // Add it to the entity.
      try {
        $field->save();
      }
      catch (EntityStorageException $e) {
        $this->loggerChannelAccessByTaxonomy->error('Error saving field definition: @error', [
          '@error' => $e->getMessage(),
        ]);
      }

      // Get the storage for the EntityFormDisplay configuration entity:
      $entity_form_display_storage = $this->entityTypeManager->getStorage('entity_form_display');

      // Set the form display.
      // Try to load the existing form display for the taxonomy term bundle:
      $form_display = $entity_form_display_storage->load('taxonomy_term.' . $vocabulary . '.default');

      if (!$form_display) {
        // If the form display does not exist, create it:
        $form_display = $entity_form_display_storage->create([
          'targetEntityType' => 'taxonomy_term',
          'bundle' => $vocabulary,
          'mode' => 'default',
          'status' => TRUE,
        ]);
      }
      if ($field_name == AccessByTaxonomyService::ALLOWED_USERS_FIELD) {
        $form_display->setComponent($field_name, [
          'type' => 'entity_reference_autocomplete',
        ]);
      }
      else {
        $form_display->setComponent($field_name, [
          'type' => 'options_buttons',
        ]);
      }
      try {
        $form_display->save();
      }
      catch (EntityStorageException $e) {
        $this->loggerChannelAccessByTaxonomy->error('Error setting form display: @error', [
          '@error' => $e->getMessage(),
        ]);
      }

      // Set the view display.
      $view_display = $entity_form_display_storage->load('taxonomy_term' . $vocabulary . 'default');
      if ($view_display) {
        $view_display->setComponent($field_name, [
          'label' => 'above',
          'type' => 'entity_reference_label',
        ]);
        try {
          $view_display->save();
        }
        catch (EntityStorageException $e) {
          $this->loggerChannelAccessByTaxonomy->error('Error setting view display: @error', [
            '@error' => $e->getMessage(),
          ]);
        }
      }
    }
  }

  /**
   * Populates role grants.
   *
   * If the table is not empty, it does not insert anything.
   * Otherwise, it populates the access_by_taxonomy_role_gid table with roles.
   */
  public function populateRoleGrants():void {
    // Sanity check: if the table is not empty, do not insert anything.
    if ($this->getRoleGrant() !== []) {
      return;
    }
    // Populate the access_by_taxonomy_role_gid table.
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    foreach ($roles as $role) {
      $roleId = $role->id();
      try {
        $this->insertRoleGrant((string) $roleId);
      }
      catch (\Exception $e) {
        $this->loggerChannelAccessByTaxonomy->error('Error inserting role grant for role @role: @error',
          [
            '@error' => $e->getMessage(),
            '@role' => $roleId,
          ]
        );
      }
    }
  }

  /**
   * Gets role grants.
   *
   * @param array $rids
   *   An array of role IDs.
   *
   * @return array
   *   An array of role grants.
   *
   * @throws \Exception
   */
  public function getRoleGrant(array $rids = []) :array {
    $query = $this->connection->select('access_by_taxonomy_role_gid', 't')
      ->fields('t', ['rid', 'gid']);
    if ($rids !== []) {
      $query->condition('t.rid', $rids, 'IN');
    }
    $result = $query->orderBy('t.gid')
      ->execute();
    $grants = [];
    foreach ($result as $grant) {
      $grants[$grant->rid] = $grant->gid;
    }
    return $grants;
  }

  /**
   * Inserts a role grant.
   *
   * @param string $roleId
   *   The role ID.
   *
   * @throws \Exception
   */
  public function insertRoleGrant(string $roleId) : void {
    $fields = [
      'rid' => $roleId,
    ];
    $this->connection->insert('access_by_taxonomy_role_gid')
      ->fields($fields)
      ->execute();
  }

  /**
   * Deletes a role grant.
   *
   * @param string $roleId
   *   The role ID.
   */
  public function deleteRoleGrant(string $roleId) : void {
    $this->connection->delete('access_by_taxonomy_role_gid')
      ->condition('rid', $roleId)
      ->execute();
  }

  /**
   * Cleans up references to a role grant in the node_access table.
   *
   * @param string $roleId
   *   The role ID.
   */
  public function cleanUpNodeAccessTableForRoleId(string $roleId) : void {
    // Given the rid, find the corresponding gid in the
    // access_by_taxonomy_role_gid table:
    $gid = $this->connection->select('access_by_taxonomy_role_gid')
      ->fields('access_by_taxonomy_role_gid', ['gid'])
      ->condition('rid', $roleId)
      ->execute()
      ->fetchField();

    // Nothing to do if the role is not found:
    if (!$gid) {
      return;
    }

    // Now we can clean up all rows in node_access that refer to this role:
    $num_deleted = $this->connection->delete('node_access')
      ->condition('realm', self::ROLE_REALM)
      ->condition('gid', $gid)
      ->execute();

    if ($num_deleted > 0) {
      $this->loggerChannelAccessByTaxonomy->info('Deleted @num rows from node_access table after deletion or role %role', [
        '@num' => $num_deleted,
        '%role' => $roleId,
      ]);
    }

  }

  /**
   * Deletes all grants for a specific user.
   *
   * @param int $uid
   *   The user ID.
   *
   * @throws \Exception
   */
  public function cleanUserAccess(int $uid) : void {
    $num_deleted = $this->connection->delete('node_access')
      ->condition('gid', $uid)
      ->condition('realm', [self::USER_REALM, self::OWNER_REALM, self::OWN_UNPUBLISHED_REALM], 'IN')
      ->execute();
    if ($num_deleted > 0) {
      $this->loggerChannelAccessByTaxonomy->info('Deleted @num rows from node_access table after deletion of user %user', [
        '@num' => $num_deleted,
        '%user' => $uid,
      ]);
    }
  }

  /**
   * Generates a diff between grants of different versions of a node.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Either a dialog with the diff, or a message saying there's no diff.
   */
  public static function nodeChangedAccessDiff(array $form, FormStateInterface $form_state) : AjaxResponse {
    /** @var \Drupal\Core\Diff\DiffFormatter $diff_formatter */
    $diff_formatter = \Drupal::service('diff.formatter');

    // Copy values from the form and form state to test
    // form and test form state.
    $test_form = $form;
    $test_form_state = $form_state;

    /** @var \Drupal\Core\Entity\ContentEntityFormInterface $form_object */
    $form_object = $test_form_state->getFormObject();

    try {
      // Validate the form:
      $form_object->validateForm($test_form, $test_form_state);
    }
    // Validation could trigger more errors depending on special validation
    // needed for some of the fields in the form. We are not interested in those
    // errors, so we catch them and ignore them.
    catch (\Exception) {
      // Do nothing.
    }

    // If there are validation errors, display them as messages:
    if ($test_form_state->hasAnyErrors()) {
      $response = new AjaxResponse();
      $errors = $form_state->getErrors();
      $form_state->clearErrors();
      $response->addCommand(
        new MessageCommand(
          t('To preview access changes, please fix these validation errors: <strong>:errors</strong>', [':errors' => implode(' ', $errors)]),
          '#access_by_taxonomy_status_messages',
          ['type' => 'warning'],
          TRUE
        )
      );

      // Since we validated the form, and there were errors, we need to delete
      // error messages, because we don't want them to be displayed twice
      // when the user reloads the page or navigates away from the page:
      /** @var \Drupal\Core\Messenger\MessengerInterface $messenger */
      $messenger = \Drupal::service('messenger');
      $messenger->deleteByType('error');

      return $response;
    }

    // Get the grant info for the current node:
    $current_node_grants_info = self::getDiffableGrantsInfoFromNodeFormObject($form_object);
    $form_object->submitForm($test_form, $form_state);

    // Get the grant info for the node being edited:
    $node_being_edited_grant_info = self::getDiffableGrantsInfoFromNodeFormObject($form_object);

    $from = explode("\n", Yaml::encode($current_node_grants_info));
    $to = explode("\n", Yaml::encode($node_being_edited_grant_info));

    $diff = new Diff($from, $to);
    $diff_formatter->show_header = FALSE;
    $element = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['diff'],
        'id' => 'diff_element',
      ],
      '#header' => [
        ['data' => t('From'), 'colspan' => '2'],
        ['data' => t('To'), 'colspan' => '2'],
      ],
      '#rows' => $diff_formatter->format($diff),
    ];
    $response = new AjaxResponse();
    $edits = $diff->getEdits();
    // No changes.
    if (count($edits) === 1) {
      $response->addCommand(
        new MessageCommand(
          t('No changes in access settings.'),
          '#access_by_taxonomy_status_messages',
          ['type' => 'status'],
          TRUE
        )
      );
      return $response;
    }

    // Remove the status message if it exists:
    $response->addCommand(new InvokeCommand('#access_by_taxonomy_status_messages .messages', 'remove'));
    // Open the diff information in a modal dialog.
    $response->addCommand(new OpenModalDialogCommand(
        t('Changes in access settings'),
        $element,
        ['width' => '800']
      )
    );
    return $response;
  }

  /**
   * Get the restrictions from a term.
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   *   The term to examine.
   *
   * @return array
   *   An array of restrictions.
   */
  public static function getRestrictionsFromTerm(Term $term) :array {
    $restrictions = [];
    $roles_entities = \Drupal::service('entity_type.manager')->getStorage('user_role')->loadMultiple();
    // We check whether any of the terms that the node translation
    // references have allowed roles or users in our special field:
    if ($term->hasField(self::ALLOWED_ROLES_FIELD)) {
      $roles = $term->get(self::ALLOWED_ROLES_FIELD)->getValue();
      foreach ($roles as $role) {
        $restrictions['roles'][$role['target_id']] = $roles_entities[$role['target_id']]->label();
      }
    }
    // Extract the allowed users from the term
    // and insert grants for them.
    if ($term->hasField(self::ALLOWED_USERS_FIELD)) {
      $users = $term->get(self::ALLOWED_USERS_FIELD)->getValue();
      foreach ($users as $user) {
        /** @var \Drupal\user\Entity\User $user */
        $user = \Drupal::service('entity_type.manager')->getStorage('user')->load($user['target_id']);
        if ($user) {
          $restrictions['users'][$user->id()] = $user->getDisplayName();
        }
      }
    }
    return $restrictions;
  }

  /**
   * Describe the grant array for a node.
   *
   * @param array $grants
   *   A Grants array.
   * @param \Drupal\node\NodeInterface $node
   *   A node entity.
   *
   * @return array
   *   An array of messages.
   *
   * @throws \Exception
   */
  public function describeGrants($grants, $node) :array {
    $roles_grants = $this->getRoleGrant();

    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();

    $msg = [];
    $view_any_access = [];
    $owner_access = [];
    $own_unpublished_access = [];
    $allowed_roles = [];
    $allowed_users = [];
    $public_access = [];

    foreach ($grants as $grant) {
      if ($grant['realm'] === self::VIEW_ANY_REALM . $node->getType()) {
        $view_any_access[] = $grant['gid'];
        continue;
      }
      if ($grant['realm'] === self::OWNER_REALM) {
        $owner_access[] = $grant['gid'];
        continue;
      }
      if ($grant['realm'] === self::OWN_UNPUBLISHED_REALM) {
        $own_unpublished_access[] = $grant['gid'];
        continue;
      }
      if ($grant['realm'] === self::ROLE_REALM) {
        $role_id = array_search($grant['gid'], $roles_grants);
        $role_label = $roles[$role_id]->label();
        $allowed_roles[] = $role_label . ' (' . $grant['gid'] . ')';
        continue;
      }
      if ($grant['realm'] === self::USER_REALM) {
        /** @var \Drupal\user\Entity\User $user */
        $user = $this->entityTypeManager->getStorage('user')->load($grant['gid']);

        if (!$user) {
          $this->loggerChannelAccessByTaxonomy->error(
            'User :uid not found. Marked as owner of node :nid.',
            [
              ':uid' => $grant['gid'],
              ':nid' => $node->id(),
            ]
          );
          continue;
        }
        $user = $user->getDisplayName();
        $allowed_users[] = $user . ' (' . $grant['gid'] . ')';
        continue;
      }
      if ($grant['realm'] === self::PUBLIC_REALM) {
        $public_access[] = $grant['gid'];
        continue;
      }
    }
    // VIEW_ANY_REALM.
    if (count($view_any_access) > 0) {
      $msg[30] = $this->t('Users with the permission %permission', [
        '%permission' => self::VIEW_ANY_REALM . $node->getType(),
      ]);
    }
    // Owner access.
    if (count($owner_access) > 0) {
      $author = $this->entityTypeManager->getStorage('user')->load($owner_access[0]);
      $author = $author->getDisplayName();
      $msg[40] = $this->t('Author: %name', [
        '%name' => $author,
      ]);
    }
    // Own unpublished access.
    if (count($own_unpublished_access) > 0) {
      $author = $own_unpublished_access[0];
      $author = $this->entityTypeManager->getStorage('user')->load($author)->getDisplayName();
      $msg[40] = $this->t('Author, %name can access this content if they have the %permission permission', [
        '%name' => $author,
        '%permission' => 'view own unpublished content',
      ]);
    }
    // Allowed roles.
    if (count($allowed_roles) > 0) {
      $allowed_roles_string = implode(', ', $allowed_roles);
      $msg[10] = $this->formatPlural(count($allowed_roles), 'Allowed role in terms: %roles', 'Allowed roles in terms: %roles', [
        '%roles' => $allowed_roles_string,
      ]);

    }
    // Allowed users.
    if (count($allowed_users) > 0) {
      $allowed_users_string = implode(', ', $allowed_users);
      $msg[20] = $this->formatPlural(count($allowed_users), 'Allowed user in terms: %users', 'Allowed users in terms: %users', [
        '%users' => $allowed_users_string,
      ]);
    }
    // Is the content public?
    if (count($public_access) > 0) {
      $msg = [];
      $msg[] = $this->t('No access restrictions are set for this content.');
    }
    return $msg;
  }

  /**
   * Extracts information about node grants from a node edit form object.
   *
   * The format of the returned array is suitable for diffing.
   *
   * @param \Drupal\Core\Entity\ContentEntityFormInterface $form_object
   *   The form state.
   *
   * @return array
   *   An array representing the access information.
   *
   * @throws \Exception
   */
  private static function getDiffableGrantsInfoFromNodeFormObject(ContentEntityFormInterface $form_object) {
    /** @var \Drupal\access_by_taxonomy\AccessByTaxonomyService $access_by_taxonomy_service */
    $access_by_taxonomy_service = \Drupal::service('access_by_taxonomy.service');
    /** @var \Drupal\node\NodeInterface $node */
    $node = $form_object->getEntity();
    $langcode = $node->language()->getId();
    $translation = $node->getTranslation($langcode);
    $grants = $access_by_taxonomy_service->getTranslationGrants($translation);
    $messages = $access_by_taxonomy_service->describeGrants($grants, $translation);
    $grants_array = [];
    ksort($messages);
    foreach ($messages as $message) {
      $grants_array[] = strip_tags($message->render());
    }
    return $grants_array;
  }

}
