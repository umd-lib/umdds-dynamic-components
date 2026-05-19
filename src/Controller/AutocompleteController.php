<?php

namespace Drupal\umdds_dynamic_components\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for autocomplete callbacks.
 */
class AutocompleteController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an AutocompleteController instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Autocomplete callback for Person content.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing matching person nodes.
   */
  public function personAutocomplete(Request $request) {
    $q = $request->query->get('q', '');

    dsm($q, 'Autocomplete query');
    $matches = [];

    if (strlen($q) < 2) {
      return new JsonResponse($matches);
    }

    dsm(strlen($q), 'Query length');

    try {
      // Query for Person nodes that match the title
      dsm('Executing node query', 'Autocomplete query');
      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $query->accessCheck(FALSE)
        ->condition('type', 'person')
        ->condition('status', 1)
        ->condition('title', '%' . $q . '%', 'LIKE')
        ->range(0, 10)
        ->sort('title', 'ASC');

      $nids = $query->execute();

      dsm($nids, 'Matching node IDs');

      if (!empty($nids)) {
        $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

        foreach ($nodes as $node) {
          $matches[] = [
            'value' => $node->getTitle() . ' (' . $node->id() . ')',
            'label' => $node->getTitle(),
          ];
        }
        dsm($matches, 'Autocomplete matches');
      }
    } catch (\Exception $e) {
      \Drupal::logger('umdds_dynamic_components')->error('Error in person autocomplete: @error', ['@error' => $e->getMessage()]);
    }

    return new JsonResponse($matches);
  }

}
