<?php

namespace Drupal\entity_reference_layout\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Layout\LayoutPluginManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity Reference with Layout field formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_layout",
 *   label = @Translation("Entity reference layout"),
 *   description = @Translation("Display the referenced entities recursively rendered by entity_view()."),
 *   field_types = {
 *     "entity_reference_layout",
 *     "entity_reference_layout_revisioned"
 *   }
 * )
 */
class EntityReferenceLayoutFormatter extends EntityReferenceRevisionsEntityFormatter implements ContainerFactoryPluginInterface {

  /**
   * The layout plugin manager service.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    LoggerChannelFactoryInterface $logger_factory,
    EntityDisplayRepositoryInterface $entity_display_repository,
    LayoutPluginManager $layout_plugin_manager) {

    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $logger_factory, $entity_display_repository);
    $this->layoutPluginManager = $layout_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('logger.factory'),
      $container->get('entity_display.repository'),
      $container->get('plugin.manager.core.layout')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $elements['#theme'] = 'entity_reference_layout';
    $elements['#elements']['field_name'] = $this->fieldDefinition->getName();
    $elements['#elements']['#view_mode'] = $this->getSetting('view_mode');
    try {
      /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items */
      $elements['#content'] = $this->buildLayoutTree($items, $langcode);
    }
    catch (\Exception $e) {
      watchdog_exception('Erl formatter, build layout tree', $e);
    }
    return $elements;
  }

  /**
   * Builds the structure for the entire layout.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items
   *   The items to render.
   * @param string $langcode
   *   The current language code.
   *
   * @return array
   *   A render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @see EntityReferenceRevisionsEntityFormatter::viewElements()
   */
  public function buildLayoutTree(EntityReferenceFieldItemListInterface $items, $langcode) {
    $build = [];
    $containerUUID = FALSE;
    /* @var \Drupal\paragraphs\ParagraphInterface $entity */
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem referringItem */
      $referringItem = $entity->_referringItem;
      // Protect ourselves from recursive rendering.
      static $depth = 0;
      $depth++;
      if ($depth > 20) {
        $this->loggerFactory->get('entity')->error(
          $this->t('Recursive rendering detected when rendering entity @entity_type @entity_id. Aborting rendering.',
            ['@entity_type' => $entity->getEntityTypeId(), '@entity_id' => $entity->id()])
        );
        return $build;
      }
      // Container.
      if ($referringItem->get('layout')->getString()) {
        $containerUUID = $entity->uuid();
        $build[$containerUUID] = $this->buildLayoutContainer($entity);
      }
      else {
        if (!$containerUUID) {
          $messenger = \Drupal::messenger();
          $messenger->addMessage($this->t('No parent container defined'), 'warning');
        }
        else {
          $referringItem = $entity->_referringItem;
          $region = $referringItem->get('region')->getString();
          $build[$containerUUID]['#regions'][$region][] = $this->buildEntityView($entity);
        }
      }
      $depth = 0;
    }
    foreach ($build as &$layout_paragraph) {
      if (isset($layout_paragraph['#layout_instance'])) {
        $regions = $layout_paragraph['#regions'];
        $layout_paragraph['rendered_layout'] = ['#weight' => 1000] + $layout_paragraph['#layout_instance']->build($regions);
      }
    }
    return $build;
  }

  /**
   * Builds the view array for a single paragraph entity.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $entity
   *   The paragraph entity to render.
   *
   * @return array
   *   Returns a render array.
   */
  public function buildEntityView(ParagraphInterface $entity) {
    $view_mode = $this->getSetting('view_mode');
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId());
    return $view_builder->view($entity, $view_mode, $entity->language()->getId());
  }

  /**
   * Builds the structure for a single layout paragraph item.
   *
   * Also adds elements for the layout instance and regions.
   * Regions will be populated with paragraphs further down the line,
   * then rendered in the layout.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $entity
   *   The paragraph to render.
   *
   * @return array
   *   Returns a build array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function buildLayoutContainer(ParagraphInterface $entity) {
    /** @var \Drupal\entity_reference_layout\Plugin\Field\FieldType\EntityReferenceLayoutRevisioned $referringItem */
    $referringItem = $entity->_referringItem;
    $layout = $referringItem->get('layout')->getString();
    $config = $referringItem->get('config')->getValue();
    if (!$this->layoutPluginManager->getDefinition($layout, FALSE)) {
      $messenger = \Drupal::messenger();
      $messenger->addMessage($this->t('Layout `%layout_id` is unknown.', ['%layout_id' => $layout]), 'warning');
      return [];
    }
    $layout_instance = $this->layoutPluginManager->createInstance($layout, $config);
    $build = $this->buildEntityView($entity);
    $build['#layout_instance'] = $layout_instance;
    $build['#regions'] = [];
    foreach ($layout_instance->getPluginDefinition()->getRegionNames() as $region_name) {
      $build['#regions'][$region_name] = [];
    }
    return $build;
  }

}
