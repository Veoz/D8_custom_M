<?php

namespace Drupal\workspaces\EventSubscriber;

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeEventSubscriberTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeListenerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines a class for listening to entity schema changes.
 */
class EntitySchemaSubscriber implements EntityTypeListenerInterface, EventSubscriberInterface {

  use EntityTypeEventSubscriberTrait;
  use StringTranslationTrait;

  /**
   * The definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The last installed schema definitions.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface
   */
  protected $entityLastInstalledSchemaRepository;

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Constructs a new EntitySchemaSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager
   *   Definition update manager.
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository
   *   Last definitions.
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   */
  public function __construct(EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager, EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository, WorkspaceManagerInterface $workspace_manager) {
    $this->entityDefinitionUpdateManager = $entityDefinitionUpdateManager;
    $this->entityLastInstalledSchemaRepository = $entityLastInstalledSchemaRepository;
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return static::getEntityTypeEvents();
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
    // Nothing to do here.
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    // If the entity type is now supported by Workspaces, add the revision
    // metadata field.
    if ($this->workspaceManager->isEntityTypeSupported($entity_type) && !$this->workspaceManager->isEntityTypeSupported($original)) {
      $revision_metadata_keys = $entity_type->get('revision_metadata_keys');

      if (!isset($revision_metadata_keys['workspace'])) {
        // Bail out if there's an existing field called 'workspace'.
        if ($this->entityDefinitionUpdateManager->getFieldStorageDefinition('workspace', $entity_type->id())) {
          throw new \RuntimeException("An existing 'workspace' field was found for the '{$entity_type->id()}' entity type. Set the 'workspace' revision metadata key to use a different field name and run this update function again.");
        }

        $revision_metadata_keys['workspace'] = 'workspace';
        $entity_type->set('revision_metadata_keys', $revision_metadata_keys);

        // We are only adding a revision metadata key so we don't need to go
        // through the entity update process.
        $this->entityLastInstalledSchemaRepository->setLastInstalledDefinition($entity_type);
      }

      $this->entityDefinitionUpdateManager->installFieldStorageDefinition($revision_metadata_keys['workspace'], $entity_type->id(), 'workspaces', $this->getWorkspaceFieldDefinition());
    }

    // If the entity type is no longer supported by Workspaces, remove the
    // revision metadata field.
    if ($this->workspaceManager->isEntityTypeSupported($original) && !$this->workspaceManager->isEntityTypeSupported($entity_type)) {
      $revision_metadata_keys = $original->get('revision_metadata_keys');
      $field_storage_definition = $this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entity_type->id())[$revision_metadata_keys['workspace']];
      $this->entityDefinitionUpdateManager->uninstallFieldStorageDefinition($field_storage_definition);

      $revision_metadata_keys = $entity_type->get('revision_metadata_keys');
      unset($revision_metadata_keys['workspace']);
      $entity_type->set('revision_metadata_keys', $revision_metadata_keys);

      // We are only removing a revision metadata key so we don't need to go
      // through the entity update process.
      $this->entityLastInstalledSchemaRepository->setLastInstalledDefinition($entity_type);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldableEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original, array $field_storage_definitions, array $original_field_storage_definitions, array &$sandbox = NULL) {
    $this->onEntityTypeUpdate($entity_type, $original);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    // Nothing to do here.
  }

  /**
   * Gets the base field definition for the 'workspace' revision metadata field.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getWorkspaceFieldDefinition() {
    return BaseFieldDefinition::create('entity_reference')
      ->setLabel($this->t('Workspace'))
      ->setDescription($this->t('Indicates the workspace that this revision belongs to.'))
      ->setSetting('target_type', 'workspace')
      ->setInternal(TRUE)
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE);
  }

}
