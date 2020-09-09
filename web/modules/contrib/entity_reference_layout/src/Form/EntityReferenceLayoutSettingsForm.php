<?php

namespace Drupal\entity_reference_layout\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityReferenceLayoutSettingsForm.
 */
class EntityReferenceLayoutSettingsForm extends ConfigFormBase {

  /**
   * The typed config service.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager
  ) {
    parent::__construct($config_factory);
    $this->typedConfigManager = $typedConfigManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_reference_layout_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_reference_layout.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('entity_reference_layout.settings');
    $erl_config_schema = $this->typedConfigManager->getDefinition('entity_reference_layout.settings') + ['mapping' => []];
    $erl_config_schema = $erl_config_schema['mapping'];

    $form['show_paragraph_labels'] = [
      '#type' => 'checkbox',
      '#title' => $erl_config_schema['show_paragraph_labels']['label'],
      '#description' => $erl_config_schema['show_paragraph_labels']['description'],
      '#default_value' => $config->get('show_paragraph_labels'),
    ];

    $form['show_layout_labels'] = [
      '#type' => 'checkbox',
      '#title' => $erl_config_schema['show_layout_labels']['label'],
      '#description' => $erl_config_schema['show_layout_labels']['description'],
      '#default_value' => $config->get('show_layout_labels'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('entity_reference_layout.settings');
    $config->set('show_paragraph_labels', $form_state->getValue('show_paragraph_labels'));
    $config->set('show_layout_labels', $form_state->getValue('show_layout_labels'));
    $config->save();
    // Confirmation on form submission.
    $this->messenger()->addMessage($this->t('The Entoty Reference Layout settings have been saved.'));
  }

}
