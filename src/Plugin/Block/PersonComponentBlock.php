<?php

namespace Drupal\umdds_dynamic_components\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Person Component Block.
 *
 * @Block(
 *   id = "umdds_person_component_block",
 *   admin_label = @Translation("UMD Libraries Person Block"),
 *   category = @Translation("UMD Libraries"),
 * )
 */
class PersonComponentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PersonComponentBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with the following keys:
   *   - default_label
   *   - label_display
   *   - provider
   *   - label
   *   - status
   *   - visibility
   *   - region
   *   - weight
   *   - plugin_id
   *   - settings
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'selected_person' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['selected_person'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Select Person'),
      '#description' => $this->t('Search and select a Person content item.'),
      '#autocomplete_route_name' => 'umdds_dynamic_components.autocomplete.person',
      '#default_value' => $this->configuration['selected_person'] ?? '',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['selected_person'] = $form_state->getValue('selected_person');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $selected = $this->configuration['selected_person'];
    if (!$selected) {
      return $build;
    }

    // Extract entity ID from autocomplete format "Title (ID)"
    if (preg_match('/\((\d+)\)$/', $selected, $matches)) {
      $person_id = $matches[1];
    } else {
      return $build;
    }

    try {
      $person_node = $this->entityTypeManager->getStorage('node')->load($person_id);

      if (!$person_node || $person_node->bundle() !== 'person') {
        return $build;
      }

      // Map Drupal fields to Person component properties
      $component_data = [
        'person_name' => $person_node->getTitle(),
        'person_title' => $person_node->hasField('field_professional_title') && !$person_node->get('field_professional_title')->isEmpty() ? $person_node->get('field_professional_title')->getString() : '',
        'person_phone' => $person_node->hasField('field_phone') && !$person_node->get('field_phone')->isEmpty() ? $person_node->get('field_phone')->getString() : '',
        'person_email' => $person_node->hasField('field_email') && !$person_node->get('field_email')->isEmpty() ? $person_node->get('field_email')->getString() : '',
        'person_department' => $person_node->hasField('field_library_department') && !$person_node->get('field_library_department')->isEmpty() ? $person_node->get('field_library_department')->getString() : '',
        'person_image' => '',
        'person_image_alt' => '',
        'person_profile_link' => $person_node->toUrl()->toString(),
      ];

      // Handle the photo field
      if ($person_node->hasField('field_photo') && !$person_node->get('field_photo')->isEmpty()) {
        $photo_field = $person_node->get('field_photo');
        if ($photo_field->target_id) {
          $file = $this->entityTypeManager->getStorage('file')->load($photo_field->target_id);
          if ($file) {
            $component_data['person_image'] = $file->createFileUrl(FALSE);
            $component_data['person_image_alt'] = 'Photo of ' . $person_node->getTitle();
          }
        }
      }

      // Render the Person component using Twig
      $build = [
        '#theme' => 'umdds_person_component',
        '#person_data' => $component_data,
        '#cache' => [
          'tags' => $person_node->getCacheTags(),
        ],
      ];
    } catch (\Exception $e) {
      \Drupal::logger('umdds_dynamic_components')->error('Error loading person node: @error', ['@error' => $e->getMessage()]);
    }

    return $build;
  }

}
