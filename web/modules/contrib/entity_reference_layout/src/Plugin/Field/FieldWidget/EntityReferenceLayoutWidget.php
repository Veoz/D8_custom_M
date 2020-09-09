<?php

namespace Drupal\entity_reference_layout\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Layout\LayoutPluginManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\entity_reference_layout\Ajax\ErlStateResetCommand;

/**
 * Entity Reference with Layout field widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_layout_widget",
 *   label = @Translation("Entity reference layout (With layout builder)"),
 *   description = @Translation("Layout builder for paragraphs."),
 *   field_types = {
 *     "entity_reference_layout_revisioned"
 *   },
 * )
 */
class EntityReferenceLayoutWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;
  /**
   * The Renderer service property.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Entity Type Manager service property.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Layouts Manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManager
   */
  protected $layoutPluginManager;

  /**
   * The plugin form manager.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * The entity that contains this field.
   *
   * @var \Drupal\Core\Entity\Entity
   */
  protected $host;

  /**
   * The name of the field.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The Html Id of the wrapper element.
   *
   * @var string
   */
  protected $wrapperId;

  /**
   * The Html Id of the item form wrapper element.
   *
   * @var string
   */
  protected $itemFormWrapperId;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Indicates whether the current widget instance is in translation.
   *
   * @var bool
   */
  protected $isTranslating;

  /**
   * Constructs a WidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Core renderer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Core entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Layout\LayoutPluginManager $layout_plugin_manager
   *   Core layout plugin manager service.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   The plugin form manager.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   Core language manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    Renderer $renderer,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    LayoutPluginManager $layout_plugin_manager,
    PluginFormFactoryInterface $plugin_form_manager,
    LanguageManager $language_manager,
    AccountProxyInterface $current_user,
    EntityDisplayRepositoryInterface $entity_display_repository) {

    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->layoutPluginManager = $layout_plugin_manager;
    $this->pluginFormFactory = $plugin_form_manager;
    $this->fieldName = $this->fieldDefinition->getName();
    $this->languageManager = $language_manager;
    $this->currentUser = $current_user;
    $this->entityDisplayRepository = $entity_display_repository;
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
      $configuration['third_party_settings'],
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.core.layout'),
      $container->get('plugin_form.factory'),
      $container->get('language_manager'),
      $container->get('current_user'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $elements = parent::form($items, $form, $form_state, $get_delta);

    // Signal to content_translation that this field should be treated as
    // multilingual and not be hidden, see
    // \Drupal\content_translation\ContentTranslationHandler::entityFormSharedElements().
    $elements['#multilingual'] = TRUE;
    return $elements;
  }

  /**
   * Builds the main widget form array container/wrapper.
   *
   * Form elements for individual items are built by formElement().
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {

    $parents = $form['#parents'];
    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);

    $this->wrapperId = Html::getId(implode('-', $parents) . $this->fieldName . '-wrapper');
    $this->itemFormWrapperId = Html::getId(implode('-', $parents) . $this->fieldName . '-form');

    $handler_settings = $items->getSetting('handler_settings');
    $layout_bundles = $handler_settings['layout_bundles'] ?? [];
    $target_bundles = $handler_settings['target_bundles'] ?? [];

    if (!empty($handler_settings['negate'])) {
      $target_bundles_options = array_keys($handler_settings['target_bundles_drag_drop']);
      $target_bundles = array_diff($target_bundles_options, $target_bundles);
    }
    $title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

    /** @var \Drupal\Core\Entity\ContentEntityInterface $host */
    $host = $items->getEntity();
    // Detect if we are translating.
    $this->initIsTranslating($form_state, $host);

    // Save items to widget state when the form first loads.
    if (empty($widget_state['items'])) {
      $widget_state['items'] = [];
      foreach ($items as $delta => $item) {
        if ($item->entity instanceof ParagraphInterface) {
          $langcode = $form_state->get('langcode');
          if (!$this->isTranslating) {
            // Set the langcode if we are not translating.
            $langcode_key = $item->entity->getEntityType()->getKey('langcode');
            if ($item->entity->get($langcode_key)->value != $langcode) {
              // If a translation in the given language already exists,
              // switch to that. If there is none yet, update the language.
              if ($item->entity->hasTranslation($langcode)) {
                $item->entity = $item->entity->getTranslation($langcode);
              }
              else {
                $item->entity->set($langcode_key, $langcode);
              }
            }
          }
          else {
            // Add translation if missing for the target language.
            if (!$item->entity->hasTranslation($langcode)) {
              // Get the selected translation of the paragraph entity.
              $entity_langcode = $item->entity->language()->getId();
              $source = $form_state->get(['content_translation', 'source']);
              $source_langcode = $source ? $source->getId() : $entity_langcode;
              // Make sure the source language version is used if available.
              // Fetching the translation without this check could lead valid
              // scenario to have no paragraphs items in the source version of
              // to an exception.
              if ($item->entity->hasTranslation($source_langcode)) {
                $entity = $item->entity->getTranslation($source_langcode);
              }
              // The paragraphs entity has no content translation source field
              // if no paragraph entity field is translatable,
              // even if the host is.
              if ($item->entity->hasField('content_translation_source')) {
                // Initialise the translation with source language values.
                $item->entity->addTranslation($langcode, $entity->toArray());
                $translation = $item->entity->getTranslation($langcode);
                $manager = \Drupal::service('content_translation.manager');
                $manager->getTranslationMetadata($translation)
                  ->setSource($item->entity->language()->getId());
              }
            }
            // If any paragraphs type is translatable do not switch.
            if ($item->entity->hasField('content_translation_source')) {
              // Switch the paragraph to the translation.
              $item->entity = $item->entity->getTranslation($langcode);
            }
          }
        }

        $widget_state['items'][$delta] = [
          'entity' => $item->entity,
          'layout' => $item->layout,
          'config' => $item->config,
          'options' => $item->options,
          'new_region' => NULL,
          'parent_weight' => NULL,
        ];
      }
    }
    // Handle asymmetric translation if field is translatable
    // by duplicating items for enabled languages.
    if ($items->getFieldDefinition()->isTranslatable()) {
      $langcode = $this->languageManager
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();

      foreach ($widget_state['items'] as $delta => $item) {
        if (empty($item['entity']) || $item['entity']->get('langcode')->value == $langcode) {
          continue;
        }
        $duplicate = $item['entity']->createDuplicate();
        /** @var \Drupal\Core\Entity\EntityInterface $duplicate */
        $duplicate->set('langcode', $langcode);
        $widget_state['items'][$delta]['entity'] = $duplicate;
      }
    }
    static::setWidgetState($parents, $this->fieldName, $form_state, $widget_state);

    $elements = parent::formMultipleElements($items, $form, $form_state);
    if (isset($elements['#prefix'])) {
      unset($elements['#prefix']);
    }
    if (isset($elements['#suffix'])) {
      unset($elements['#suffix']);
    }
    $elements += [
      '#title' => $title,
      '#description' => $description,
    ];
    $elements['#theme'] = 'entity_reference_layout_widget';
    $elements['#id'] = $this->wrapperId;

    // Add logic for new elements Add, if not in a translation context.
    if ($this->allowReferenceChanges()) {
      // Button to add new section and other paragraphs.
      $elements['add_more'] = [
        'actions' => [
          '#attributes' => ['class' => ['js-hide']],
          '#type' => 'container',
        ],
      ];
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo('paragraph');
      foreach ($layout_bundles as $bundle_id) {
        $elements['add_more']['actions']['section'] = [
          '#type' => 'submit',
          '#bundle_id' => $bundle_id,
          '#host' => $items->getEntity(),
          '#value' => $this->t('Add @label', ['@label' => $bundle_info[$bundle_id]['label']]),
          '#modal_label' => $this->t('Add new @label', ['@label' => $bundle_info[$bundle_id]['label']]),
          '#name' => implode('_', $parents) . '_add_' . $bundle_id,
          '#submit' => [
            [$this, 'newItemSubmit'],
          ],
          '#attributes' => ['class' => ['erl-add-section']],
          '#limit_validation_errors' => [array_merge($parents, [$this->fieldName])],
          '#ajax' => [
            'callback' => [$this, 'elementAjax'],
            'wrapper' => $this->wrapperId,
          ],
          '#element_parents' => $parents,
        ];
      }

      // Add other paragraph types.
      $options = [];
      try {
        $types = [];
        $bundle_ids = array_diff($target_bundles, $layout_bundles);
        $target_type = $items->getSetting('target_type');
        $definition = $this->entityTypeManager->getDefinition($target_type);
        $storage = $this->entityTypeManager->getStorage($definition->getBundleEntityType());
        foreach ($bundle_ids as $bundle_id) {
          $type = $storage->load($bundle_id);
          $path = '';
          // Get the icon and pass to Javascript.
          if (method_exists($type, 'getIconFile')) {
            try {
              /** @var \Drupal\file\FileInterface $icon */
              if ($icon = $type->getIconFile()) {
                $path = $icon->toUrl();
              }
            }
            catch (\Exception $e) {
              watchdog_exception('Erl, Paragraph Type Icon Generation', $e);
            }
          }
          $options[$bundle_id] = $bundle_info[$bundle_id]['label'];
          $types[] = [
            'id' => $bundle_id,
            'name' => $bundle_info[$bundle_id]['label'],
            'image' => $path,
          ];
        }
      }
      catch (\Exception $e) {
        watchdog_exception('Erl, add Paragraph Type', $e);
      }
      $elements['add_more']['actions']['type'] = [
        '#title' => $this->t('Choose type'),
        '#type' => 'select',
        '#options' => $options,
        '#attributes' => ['class' => ['erl-item-type']],
      ];
      $elements['add_more']['actions']['item'] = [
        '#type' => 'submit',
        '#host' => $items->getEntity(),
        '#value' => $this->t('Create New'),
        '#submit' => [[$this, 'newItemSubmit']],
        '#limit_validation_errors' => [array_merge($parents, [$this->fieldName])],
        '#attributes' => ['class' => ['erl-add-item']],
        '#ajax' => [
          'callback' => [$this, 'elementAjax'],
          'wrapper' => $this->wrapperId,
        ],
        '#name' => implode('_', $parents) . '_add_item',
        '#element_parents' => $parents,
      ];
      // Add region and parent_delta hidden items only in this is a new entity.
      // Prefix with underscore to prevent namespace collisions.
      $elements['add_more']['actions']['_region'] = [
        '#type' => 'hidden',
        '#attributes' => ['class' => ['erl-new-item-region']],
      ];
      $elements['add_more']['actions']['_parent_weight'] = [
        '#type' => 'hidden',
        '#attributes' => ['class' => ['erl-new-item-parent']],
      ];
      // Template for javascript behaviors.
      $elements['add_more']['menu'] = [
        '#type' => 'inline_template',
        '#template' => '
        <div class="erl-add-more-menu hidden">
          <h4 class="visually-hidden">Add Item</h4>
          <div class="erl-add-more-menu__search hidden">
            <input type="text" placeholder="{{ search_text }}" />
          </div>
          <div class="erl-add-more-menu__group">
            {% for type in types %}
              <div class="erl-add-more-menu__item">
                <a data-type="{{ type.id }}" href="#{{ type.id }}">
                {% if type.image %}
                <img src="{{ type.image }}" alt ="" />
                {% endif %}
                <div>{{ type.name }}</div>
                </a>
              </div>
            {% endfor %}
          </div>
        </div>',
        '#context' => [
          'types' => $types,
          'search_text' => $this->t('Search'),
        ],
      ];
    }
    else {
      // Add the #isTranslating attribute, if in a translation context.
      $elements['add_more'] = [
        'actions' => [
          '#isTranslating' => TRUE,
        ],
      ];
    }
    return $elements;
  }

  /**
   * Builds the widget form array for an individual item.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $parents = $form['#parents'];
    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);
    $handler_settings = $items->getSetting('handler_settings');
    $layout_bundles = $handler_settings['layout_bundles'] ?? [];

    if (empty($widget_state['items'][$delta]['entity'])) {
      return [];
    }

    // Flatten layouts array for use with radio buttons.
    $available_layouts = [];
    foreach ($handler_settings['allowed_layouts'] as $group) {
      foreach ($group as $layout_id => $layout_name) {
        $available_layouts[$layout_id] = $layout_name;
      }
    }

    /** @var \Drupal\paragraphs\ParagraphInterface $entity */
    $entity = !empty($widget_state['items'][$delta]) ? $widget_state['items'][$delta]['entity'] : NULL;
    $options = !empty($widget_state['items'][$delta]['options']) ? $widget_state['items'][$delta]['options'] : [];
    $config = !empty($widget_state['items'][$delta]['config']) ? $widget_state['items'][$delta]['config'] : [];
    $layout_path = array_merge($parents, [
      $this->fieldName,
      $delta,
      'entity_form',
      'layout_selection',
      'layout',
    ]);
    if (!$layout = $form_state->getValue($layout_path)) {
      $layout = !empty($widget_state['items'][$delta]['layout']) ? $widget_state['items'][$delta]['layout'] : NULL;
    }

    $element = [
      '#type' => 'container',
      '#delta' => $delta,
      '#entity' => $entity,
      '#layout' => !empty($items[$delta]->layout) ? $items[$delta]->layout : '',
      '#region' => !empty($items[$delta]->region) ? $items[$delta]->region : '',
      '#layout_options' => $items[$delta]->options ?? [],
      '#attributes' => [
        'class' => [
          'erl-item',
          'erl-item--' . ($entity->isPublished() ? 'published' : 'unpublished'),
        ],
        'id' => [
          $this->fieldName . '--item-' . $delta,
        ],
      ],
      'region' => [
        '#type' => 'hidden',
        '#attributes' => ['class' => ['erl-region']],
        '#default_value' => !empty($items[$delta]->region) ? $items[$delta]->region : '',
      ],
      // These properties aren't modified by the main form,
      // but are modified when a user edits a specific item.
      'entity' => [
        '#type' => 'value',
        '#value' => $entity,
      ],
      'config' => [
        '#type' => 'value',
        '#value' => $config,
      ],
      'options' => [
        '#type' => 'value',
        '#value' => $options,
      ],
      'layout' => [
        '#type' => 'value',
        '#value' => $layout,
      ],
      '#process' => [],
    ];

    // Add Edit and Remove button, if the current user has appropriate
    // permissions.
    if ($this->currentUser->hasPermission('manage entity reference layout sections')) {
      $element['actions'] = [
        '#type' => 'container',
        '#weight' => -1000,
        '#attributes' => ['class' => ['erl-actions']],
        'edit' => [
          '#type' => 'submit',
          '#name' => 'edit_' . $this->fieldName . '_' . $delta,
          '#value' => $this->t('Edit'),
          '#attributes' => ['class' => ['erl-edit']],
          '#limit_validation_errors' => [array_merge($parents, [$this->fieldName])],
          '#submit' => [[$this, 'editItemSubmit']],
          '#delta' => $delta,
          '#ajax' => [
            'callback' => [$this, 'elementAjax'],
            'wrapper' => $this->wrapperId,
            'progress' => 'none',
          ],
          '#element_parents' => $parents,
        ],
        'remove' => [
          '#type' => 'submit',
          '#name' => 'remove_' . $this->fieldName . '_' . $delta,
          '#value' => $this->t('Remove'),
          '#attributes' => ['class' => ['erl-remove']],
          '#limit_validation_errors' => [array_merge($parents, [$this->fieldName])],
          '#submit' => [[$this, 'removeItemSubmit']],
          '#delta' => $delta,
          '#ajax' => [
            'callback' => [$this, 'elementAjax'],
            'wrapper' => $this->wrapperId,
            'progress' => 'none',
          ],
          '#element_parents' => $parents,
        ],
      ];
    }

    // If this is a new entity, pass the region and parent
    // item's weight to the theme.
    if (!empty($widget_state['items'][$delta]['is_new'])) {
      $element['#is_new'] = TRUE;
      $element['#new_region'] = $widget_state['items'][$delta]['new_region'];
      $element['#parent_weight'] = $widget_state['items'][$delta]['parent_weight'];
      $element['#attributes']['class'][] = 'js-hide';
    }

    // Build the preview and render it in the form.
    $preview = [];
    if (isset($entity)) {
      $preview_view_mode = $this->getSetting('preview_view_mode');
      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $preview = $view_builder->view($entity, $preview_view_mode);
      $preview['#cache']['max-age'] = 0;
    }
    $element += [
      'preview' => $preview,
    ];

    // Add remove confirmation form if we're removing.
    if (isset($widget_state['remove_item']) && $widget_state['remove_item'] === $delta) {
      $element['remove_form'] = [
        '#prefix' => '<div class="erl-form">',
        '#suffix' => '</div>',
        '#type' => 'container',
        '#attributes' => ['data-dialog-title' => [$this->t('Confirm removal')]],
        'message' => [
          '#type' => 'markup',
          '#markup' => $this->t('Are you sure you want to permanently remove this <b>@type?</b><br />This action cannot be undone.', ['@type' => $entity->type->entity->label()]),
        ],
        'actions' => [
          '#type' => 'container',
          'confirm' => [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#delta' => $delta,
            '#submit' => [[$this, 'removeItemConfirmSubmit']],
            '#ajax' => [
              'callback' => [$this, 'elementAjax'],
              'wrapper' => $this->wrapperId,
            ],
            '#element_parents' => $parents,
          ],
          'cancel' => [
            '#type' => 'submit',
            '#value' => $this->t('Cancel'),
            '#delta' => $delta,
            '#submit' => [[$this, 'removeItemCancelSubmit']],
            '#attributes' => [
              'class' => ['erl-cancel', 'button--danger'],
            ],
            '#ajax' => [
              'callback' => [$this, 'elementAjax'],
              'wrapper' => $this->wrapperId,
            ],
            '#element_parents' => $parents,
          ],
        ],
        '#weight' => 1000,
        '#delta' => $delta,
      ];
    }

    // Add edit form if open.
    if (isset($widget_state['open_form']) && $widget_state['open_form'] === $delta) {
      $display = EntityFormDisplay::collectRenderDisplay($entity, 'default');
      $bundle_label = $entity->type->entity->label();
      $element['entity_form'] = [
        '#prefix' => '<div class="erl-form entity-type-' . $entity->bundle() . '">',
        '#suffix' => '</div>',
        '#type' => 'container',
        '#parents' => array_merge($parents, [
          $this->fieldName,
          $delta,
          'entity_form',
        ]),
        '#weight' => 1000,
        '#delta' => $delta,
        '#display' => $display,
        '#attributes' => [
          'data-dialog-title' => [
            $entity->id() ? $this->t('Edit @type', ['@type' => $bundle_label]) : $this->t('Create new @type', ['@type' => $bundle_label]),
          ],
        ],
      ];

      // Support for Field Group module based on Paragraphs module.
      // @todo Remove as part of https://www.drupal.org/node/2640056
      if (\Drupal::moduleHandler()->moduleExists('field_group')) {
        $context = [
          'entity_type' => $entity->getEntityTypeId(),
          'bundle' => $entity->bundle(),
          'entity' => $entity,
          'context' => 'form',
          'display_context' => 'form',
          'mode' => $display->getMode(),
        ];

        field_group_attach_groups($element['entity_form'], $context);
        if (function_exists('field_group_form_pre_render')) {
          $element['entity_form']['#pre_render'][] = 'field_group_form_pre_render';
        }
        if (function_exists('field_group_form_process')) {
          $element['entity_form']['#process'][] = 'field_group_form_process';
        }
      }

      $display->buildForm($entity, $element['entity_form'], $form_state);

      // Add the layout plugin form if applicable.
      if (in_array($entity->bundle(), $layout_bundles)) {
        $element['entity_form']['layout_selection'] = [
          '#type' => 'container',
          'layout' => [
            '#weight' => -100,
            '#type' => 'radios',
            '#title' => $this->t('Select a layout:'),
            '#options' => $available_layouts,
            '#default_value' => $layout,
            '#attributes' => [
              'class' => ['erl-layout-select'],
            ],
            '#required' => TRUE,
            '#after_build' => [[get_class($this), 'processLayoutOptions']],
          ],
          'update' => [
            '#type' => 'submit',
            '#value' => $this->t('Update'),
            '#name' => 'update_layout',
            '#delta' => $element['#delta'],
            '#limit_validation_errors' => [
              array_merge($parents, [
                $this->fieldName,
                $delta,
                'entity_form',
                'layout_selection',
              ]),
            ],
            '#submit' => [
              [$this, 'editItemSubmit'],
            ],
            '#attributes' => [
              'class' => ['js-hide'],
            ],
            '#element_parents' => $parents,
          ],
        ];

        // Switching layouts should change the layout plugin options form
        // with Ajax for users with adequate permissions.
        $element['entity_form']['layout_selection']['layout']['#ajax'] = [
          'event' => 'change',
          'callback' => [$this, 'buildLayoutConfigurationFormAjax'],
          'trigger_as' => ['name' => 'update_layout'],
          'wrapper' => 'layout-config',
          'progress' => 'none',
        ];
        $element['entity_form']['layout_selection']['update']['#ajax'] = [
          'callback' => [$this, 'buildLayoutConfigurationFormAjax'],
          'wrapper' => 'layout-config',
          'progress' => 'none',
        ];

        $element['entity_form']['layout_plugin_form'] = [
          '#prefix' => '<div id="layout-config">',
          '#suffix' => '</div>',
          '#access' => $this->currentUser->hasPermission('manage entity reference layout sections'),
        ];
        // Add the layout configuration form if applicable.
        if (!empty($layout)) {
          try {
            $layout_instance = $this->layoutPluginManager->createInstance($layout, $config);
            if ($layout_plugin = $this->getLayoutPluginForm($layout_instance)) {
              $element['entity_form']['layout_plugin_form'] += [
                '#type' => 'details',
                '#title' => $this->t('Layout Configuration'),
              ];
              $element['entity_form']['layout_plugin_form'] += $layout_plugin->buildConfigurationForm([], $form_state);
            }
          }
          catch (\Exception $e) {
            watchdog_exception('Erl, add the layout configuration form', $e);
          }
        }
        // Add the additional options form if applicable.
        // This is deprecated and included only for backwards compatibility.
        if ($this->getSetting('always_show_options_form')) {
          // Other layout options.
          $element['entity_form']['options'] = [
            '#type' => 'details',
            '#title' => $this->t('Basic Layout Options'),
            '#description' => $this->t('Classes will be applied to the container for this field item.'),
            '#open' => FALSE,
          ];
          $element['entity_form']['options']['container_classes'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Custom Classes for Layout Container'),
            '#description' => $this->t('Classes will be applied to the container for this field item.'),
            '#size' => 50,
            '#default_value' => $options['options']['container_classes'] ?? '',
            '#placeholder' => $this->t('CSS Classes'),
          ];
          $element['entity_form']['options']['bg_color'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Background Color for Layout Container'),
            '#description' => $this->t('Background will be applied to the layout container.'),
            '#size' => 10,
            '#default_value' => $options['options']['bg_color'] ?? '',
            '#placeholder' => $this->t('Hex Code'),
          ];
        }
      }

      $paragraphs_type = $entity->getParagraphType();
      // @todo: Check translation functionality.
      if ($paragraphs_type &&
        $this->currentUser->hasPermission('edit behavior plugin settings') &&
        (!$this->isTranslating || !$entity->isDefaultTranslationAffectedOnly()) &&
        $behavior_plugins = $paragraphs_type->getEnabledBehaviorPlugins()) {

        $element['entity_form']['behavior_plugins'] = [
          '#type' => 'details',
          '#title' => $this->t('Behaviors'),
          '#element_validate' => [[$this, 'validateBehaviors']],
          '#entity' => $entity,
        ];
        $has_behavior_form_options = FALSE;
        /* @var \Drupal\paragraphs\ParagraphsBehaviorInterface $plugin */
        foreach ($behavior_plugins as $plugin_id => $plugin) {
          $element['entity_form']['behavior_plugins'][$plugin_id] = ['#type' => 'container'];
          $subform_state = SubformState::createForSubform($element['entity_form']['behavior_plugins'][$plugin_id], $form, $form_state);
          $plugin_form = $plugin->buildBehaviorForm($entity, $element['entity_form']['behavior_plugins'][$plugin_id], $subform_state);
          if (!empty(Element::children($plugin_form))) {
            $element['entity_form']['behavior_plugins'][$plugin_id] = $plugin_form;
            $has_behavior_form_options = TRUE;
          }
        }
        // No behaviors were added, remove the behavior form.
        if (!$has_behavior_form_options) {
          unset($element['entity_form']['behavior_plugins']);
        }
      }

      // Add save, cancel, etc.
      $element['entity_form'] += [
        'actions' => [
          '#weight' => 1000,
          '#type' => 'container',
          '#attributes' => ['class' => ['erl-item-form-actions']],
          'save_item' => [
            '#type' => 'submit',
            '#name' => 'save',
            '#value' => $this->t('Save'),
            '#delta' => $element['#delta'],
            '#limit_validation_errors' => [array_merge($parents, [$this->fieldName])],
            '#submit' => [
              [$this, 'saveItemSubmit'],
            ],
            '#ajax' => [
              'callback' => [$this, 'elementAjax'],
              'wrapper' => $this->wrapperId,
              'progress' => 'none',
            ],
            '#element_parents' => $parents,
          ],
          'cancel' => [
            '#type' => 'submit',
            '#name' => 'cancel',
            '#value' => $this->t('Cancel'),
            '#limit_validation_errors' => [],
            '#delta' => $element['#delta'],
            '#submit' => [
              [$this, 'cancelItemSubmit'],
            ],
            '#attributes' => [
              'class' => ['erl-cancel', 'button--danger'],
            ],
            '#ajax' => [
              'callback' => [$this, 'elementAjax'],
              'wrapper' => $this->wrapperId,
              'progress' => 'none',
            ],
            '#element_parents' => $parents,
          ],
        ],
      ];

      $hide_untranslatable_fields = $entity->isDefaultTranslationAffectedOnly();
      foreach (Element::children($element['entity_form']) as $field) {
        if ($entity->hasField($field)) {
          /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
          $field_definition = $entity->get($field)->getFieldDefinition();
          $translatable = $entity->{$field}->getFieldDefinition()->isTranslatable();

          // Do a check if we have to add a class to the form element. We need
          // those classes (paragraphs-content and paragraphs-behavior) to show
          // and hide elements, depending of the active perspective.
          // We need them to filter out entity reference revisions fields that
          // reference paragraphs, cause otherwise we have problems with showing
          // and hiding the right fields in nested paragraphs.
          $is_paragraph_field = FALSE;
          if ($field_definition->getType() == 'entity_reference_revisions') {
            // Check if we are referencing paragraphs.
            if ($field_definition->getSetting('target_type') == 'paragraph') {
              $is_paragraph_field = TRUE;
            }
          }

          if (!$translatable && $this->isTranslating && !$is_paragraph_field) {
            if ($hide_untranslatable_fields) {
              $element['entity_form'][$field]['#access'] = FALSE;
            }
            else {
              $element['entity_form'][$field]['widget']['#after_build'][] = [
                static::class,
                'addTranslatabilityClue',
              ];
            }
          }
        }
      }
    }
    return $element;
  }

  /**
   * Add theme wrappers to layout selection radios.
   *
   * Theme function injects layout icons into radio buttons.
   */
  public static function processLayoutOptions($element) {
    foreach (Element::children($element) as $radio_item) {
      $element[$radio_item]['#theme_wrappers'][] = 'entity_reference_layout_radio';
    }
    return $element;
  }

  /**
   * Validate paragraph behavior form plugins.
   *
   * @param array $element
   *   The element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The complete form array.
   */
  public function validateBehaviors(array $element, FormStateInterface $form_state, array $form) {

    $element_array_parents = $element['#array_parents'];
    $item_array_parents = array_splice($element_array_parents, 0, -2);
    $item_form = NestedArray::getValue($form, $item_array_parents);

    /** @var \Drupal\paragraphs\ParagraphInterface $paragraphs_entity */
    $entity = $item_form['#entity'];

    // Validate all enabled behavior plugins.
    $paragraphs_type = $entity->getParagraphType();
    if ($this->currentUser->hasPermission('edit behavior plugin settings')) {
      foreach ($paragraphs_type->getEnabledBehaviorPlugins() as $plugin_id => $plugin_values) {
        if (!empty($element[$plugin_id])) {
          $subform_state = SubformState::createForSubform($element[$plugin_id], $form_state->getCompleteForm(), $form_state);
          $plugin_values->validateBehaviorForm($entity, $element[$plugin_id], $subform_state);
        }
      }
    }
  }

  /**
   * Form submit handler - adds a new item and opens its edit form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function newItemSubmit(array $form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];
    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);

    if (!empty($element['#bundle_id'])) {
      $bundle_id = $element['#bundle_id'];
    }
    else {
      $element_parents = $element['#parents'];
      array_splice($element_parents, -1, 1, 'type');
      $bundle_id = $form_state->getValue($element_parents);
    }

    try {
      $entity_type = $this->entityTypeManager->getDefinition('paragraph');
      $bundle_key = $entity_type->getKey('bundle');

      $paragraphs_entity = $this->entityTypeManager->getStorage('paragraph')
        ->create([
          $bundle_key => $bundle_id,
        ]);
      $paragraphs_entity->setParentEntity($element['#host'], $this->fieldDefinition->getName());

      $path = array_merge($parents, [
        $this->fieldDefinition->getName(),
        'add_more',
        'actions',
      ]);
      $new_region = $form_state->getValue(array_merge($path, ['_region']));
      $parent_weight = intval($form_state->getValue(array_merge($path, ['_parent_weight'])));

      $widget_state['items'][] = [
        'entity' => $paragraphs_entity,
        'is_new' => TRUE,
        'new_region' => $new_region,
        'parent_weight' => $parent_weight,
      ];
      $widget_state['open_form'] = $widget_state['items_count'];
      $widget_state['items_count']++;

      static::setWidgetState($parents, $this->fieldName, $form_state, $widget_state);
      $form_state->setRebuild();
    }
    catch (\Exception $e) {
      watchdog_exception('Erl, new Item Submit', $e);
    }
  }

  /**
   * Form submit handler - opens the edit form for an existing item.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function editItemSubmit(array $form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];
    $delta = $element['#delta'];

    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);
    $widget_state['open_form'] = $delta;

    static::setWidgetState($parents, $this->fieldName, $form_state, $widget_state);
    $form_state->setRebuild();
  }

  /**
   * Form submit handler - opens confirm removal form for an item.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function removeItemSubmit(array $form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];
    $delta = $element['#delta'];

    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);
    $widget_state['remove_item'] = $delta;

    static::setWidgetState($parents, $this->fieldName, $form_state, $widget_state);
    $form_state->setRebuild();
  }

  /**
   * Form submit handler - removes/deletes an item.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function removeItemConfirmSubmit(array $form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];
    $delta = $element['#delta'];

    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);

    unset($widget_state['items'][$delta]['entity']);
    unset($widget_state['remove_item']);

    static::setWidgetState($parents, $this->fieldName, $form_state, $widget_state);
    $form_state->setRebuild();
  }

  /**
   * Form submit handler - cancels item removal and closes confirmation form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function removeItemCancelSubmit(array $form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];

    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);

    unset($widget_state['remove_item']);

    static::setWidgetState($parents, $this->fieldName, $form_state, $widget_state);
    $form_state->setRebuild();
  }

  /**
   * Form submit handler - saves an item.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function saveItemSubmit(array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];
    $delta = $element['#delta'];
    $element_array_parents = $element['#array_parents'];
    $item_array_parents = array_splice($element_array_parents, 0, -2);

    $item_form = NestedArray::getValue($form, $item_array_parents);
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    $display = $item_form['#display'];
    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);

    // Remove is_new flag since we're saving the entity.
    unset($widget_state['items'][$delta]['is_new']);

    // Set correct default language for the paragraph.
    $paragraph = $widget_state['items'][$delta]['entity'];
    $paragraph->set('langcode', $form_state->get('langcode'));

    // Save field values to entity.
    $display->extractFormValues($paragraph, $item_form, $form_state);

    // Submit behavior forms.
    $paragraphs_type = $paragraph->getParagraphType();
    if ($this->currentUser->hasPermission('edit behavior plugin settings')) {
      foreach ($paragraphs_type->getEnabledBehaviorPlugins() as $plugin_id => $plugin_values) {
        $plugin_form = isset($item_form['behavior_plugins']) ? $item_form['behavior_plugins'][$plugin_id] : [];
        if (!empty($plugin_form) && !empty(Element::children($plugin_form))) {
          $subform_state = SubformState::createForSubform($item_form['behavior_plugins'][$plugin_id], $form_state->getCompleteForm(), $form_state);
          $plugin_values->submitBehaviorForm($paragraph, $item_form['behavior_plugins'][$plugin_id], $subform_state);
        }
      }
    }

    // Save paragraph back to widget state.
    $widget_state['items'][$delta]['entity'] = $paragraph;

    // Save layout settings.
    if (!empty($item_form['layout_selection']['layout'])) {

      $layout = $form_state->getValue($item_form['layout_selection']['layout']['#parents']);
      $widget_state['items'][$delta]['layout'] = $layout;

      // Save layout config:
      if (!empty($item_form['layout_plugin_form'])) {
        try {
          $layout_instance = $this->layoutPluginManager->createInstance($layout);
          if ($this->getLayoutPluginForm($layout_instance)) {
            $subform_state = SubformState::createForSubform($item_form['layout_plugin_form'], $form_state->getCompleteForm(), $form_state);
            $layout_instance->submitConfigurationForm($item_form['layout_plugin_form'], $subform_state);
            $layout_config = $layout_instance->getConfiguration();
            $widget_state['items'][$delta]['config'] = $layout_config;
          }
        }
        catch (\Exception $e) {
          watchdog_exception('Erl, Layout Instance generation', $e);
        }
      }
    }

    // Save layout options (deprecated).
    if (!empty($item_form['options'])) {
      $options_path = array_merge($parents, [
        $this->fieldName,
        $delta,
        'entity_form',
        'options',
      ]);
      $widget_state['items'][$delta]['options']['options'] = $form_state->getValue($options_path);
    }

    // Close the entity form.
    $widget_state['open_form'] = FALSE;

    static::setWidgetState($parents, $this->fieldName, $form_state, $widget_state);
    $form_state->setRebuild();
  }

  /**
   * Form submit handler - cancels editing an item and closes form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancelItemSubmit(array $form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];
    $delta = $element['#delta'];
    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);

    // If canceling an item that hasn't been created yet, remove it.
    if (!empty($widget_state['items'][$delta]['is_new'])) {
      array_splice($widget_state['items'], $delta, 1);
      $widget_state['items_count'] = count($widget_state['items']);
    }
    $widget_state['open_form'] = FALSE;
    static::setWidgetState($parents, $this->fieldName, $form_state, $widget_state);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback to return the entire ERL element.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax Response.
   */
  public function elementAjax(array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];
    $field_state = static::getWidgetState($parents, $this->fieldName, $form_state);
    $erl_field = NestedArray::getValue($form, $field_state['array_parents']);

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#' . $this->wrapperId, $erl_field));
    $response->addCommand(new ErlStateResetCommand('#' . $this->wrapperId));
    return $response;
  }

  /**
   * Ajax callback to return a layout plugin configuration form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax Response.
   */
  public function buildLayoutConfigurationFormAjax(array $form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();
    $parents = $element['#array_parents'];
    $parents = array_splice($parents, 0, -2);
    $parents = array_merge($parents, ['layout_plugin_form']);
    $response = new AjaxResponse();
    if ($layout_plugin_form = NestedArray::getValue($form, $parents)) {
      $response->addCommand(new ReplaceCommand('#layout-config', $layout_plugin_form));
      $response->addCommand(new ErlStateResetCommand('#' . $this->wrapperId));
    }
    return $response;
  }

  /**
   * Field instance settings form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   the form array.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $entity_type_id = $this->getFieldSetting('target_type');
    $form['preview_view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Preview view mode'),
      '#default_value' => $this->getSetting('preview_view_mode'),
      '#options' => $this->entityDisplayRepository->getViewModeOptions($entity_type_id),
      '#required' => TRUE,
      '#description' => $this->t("View mode for the referenced entity preview on the edit form. Automatically falls back to 'default', if it is not enabled in the referenced entity type displays."),
    ];

    $form['always_show_options_form'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('always_show_options_form'),
      '#title' => $this->t('Always show layout options form'),
      '#description' => $this->t('Show options for additional classes and background color when adding or editing layouts, even if a layout plugin form exists. The preferred method is to rely on Layout Plugin configuration forms.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Preview view mode: @preview_view_mode', ['@preview_view_mode' => $this->getSetting('preview_view_mode')]);

    if ($this->getSetting('always_show_options_form')) {
      $summary[] = $this->t('Layout configuration: Show extra options form (deprecated).');
    }
    else {
      $summary[] = $this->t('Layout configuration: Rely on Layout Plugins (preferred).');
    }
    return $summary;
  }

  /**
   * Default settings for widget.
   */
  public static function defaultSettings() {
    $defaults = parent::defaultSettings();
    $defaults += [
      'always_show_options_form' => FALSE,
      'preview_view_mode' => 'default',
    ];

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => &$item) {
      unset($values[$delta]['actions']);
      if ($item['entity'] instanceof ParagraphInterface) {
        /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph_entity */
        $paragraph_entity = $item['entity'];
        $paragraph_entity->setNeedsSave(TRUE);
        $item['target_id'] = $paragraph_entity->id();
        $item['target_revision_id'] = $paragraph_entity->getRevisionId();
      }
    }
    return $values;
  }

  /**
   * Retrieves the plugin form for a given layout.
   *
   * @param \Drupal\Core\Layout\LayoutInterface $layout
   *   The layout plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface|null
   *   The plugin form for the layout.
   */
  protected function getLayoutPluginForm(LayoutInterface $layout) {
    if ($layout instanceof PluginWithFormsInterface) {
      try {
        return $this->pluginFormFactory->createInstance($layout, 'configure');
      }
      catch (\Exception $e) {
        watchdog_exception('Erl, Layout Configuration', $e);
      }
    }

    if ($layout instanceof PluginFormInterface) {
      return $layout;
    }

    return NULL;
  }

  /**
   * Determine if widget is in translation.
   *
   * Initializes $this->isTranslating.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Entity\ContentEntityInterface $host
   *   The host entity.
   */
  protected function initIsTranslating(FormStateInterface $form_state, ContentEntityInterface $host) {
    if ($this->isTranslating != NULL) {
      return;
    }
    $this->isTranslating = FALSE;
    if (!$host->isTranslatable()) {
      return;
    }
    if (!$host->getEntityType()->hasKey('default_langcode')) {
      return;
    }
    $default_langcode_key = $host->getEntityType()->getKey('default_langcode');
    if (!$host->hasField($default_langcode_key)) {
      return;
    }

    if (!empty($form_state->get('content_translation'))) {
      // Adding a language through the ContentTranslationController.
      $this->isTranslating = TRUE;
    }
    $langcode = $form_state->get('langcode');
    if ($host->hasTranslation($langcode) && $host->getTranslation($langcode)->get($default_langcode_key)->value == 0) {
      // Editing a translation.
      $this->isTranslating = TRUE;
    }
  }

  /**
   * Checks if we can allow reference changes.
   *
   * @return bool
   *   TRUE if we can allow reference changes, otherwise FALSE.
   */
  protected function allowReferenceChanges() {
    return !$this->isTranslating;
  }

  /**
   * After-build callback for adding the translatability clue from the widget.
   *
   * ContentTranslationHandler::addTranslatabilityClue() adds an
   * "(all languages)" suffix to the widget title, replicate that here.
   *
   * @param array $element
   *   The Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The element containing a translatability clue.
   */
  public static function addTranslatabilityClue(array $element, FormStateInterface $form_state) {
    static $suffix, $fapi_title_elements;

    // Widgets could have multiple elements with their own titles, so remove the
    // suffix if it exists, do not recurse lower than this to avoid going into
    // nested paragraphs or similar nested field types.
    // Elements which can have a #title attribute according to FAPI Reference.
    if (!isset($suffix)) {
      $suffix = ' <span class="translation-entity-all-languages">(' . t('all languages') . ')</span>';
      $fapi_title_elements = array_flip([
        'checkbox',
        'checkboxes',
        'date',
        'details',
        'fieldset',
        'file',
        'item',
        'password',
        'password_confirm',
        'radio',
        'radios',
        'select',
        'textarea',
        'textfield',
        'weight',
      ]);
    }

    // Update #title attribute for all elements that are allowed to have a
    // #title attribute according to the Form API Reference. The reason for this
    // check is because some elements have a #title attribute even though it is
    // not rendered; for instance, field containers.
    if (isset($element['#type']) && isset($fapi_title_elements[$element['#type']]) && isset($element['#title'])) {
      $element['#title'] .= $suffix;
    }
    // If the current element does not have a (valid) title, try child elements.
    elseif ($children = Element::children($element)) {
      foreach ($children as $delta) {
        $element[$delta] = static::addTranslatabilityClue($element[$delta], $form_state);
      }
    }
    // If there are no children, fall back to the current #title attribute if it
    // exists.
    elseif (isset($element['#title'])) {
      $element['#title'] .= $suffix;
    }
    return $element;
  }

}
