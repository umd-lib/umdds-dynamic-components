<?php

namespace Drupal\umdds_dynamic_components\Plugin\Block;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\umdds_dynamic_components\Form\SearcherForm;

/**
 * Provides a search form for selecting a searcher.
 *
 * @Block(
 *   id = "umdds_searcher_block",
 *   admin_label = @Translation("UMD Libraries Searcher"),
 *   category = @Translation("UMD Libraries"),
 * )
 */
class SearcherBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'available_searchers' => [],
      'search_placeholder' => 'Enter your search terms here',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $searchers = $this->configuration['available_searchers'] ?? [];

    $form['available_searchers_yaml'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Available Searchers'),
      '#description' => $this->t('Enter searchers as YAML with a top-level "searchers" list. Each searcher must have a name, query_url, and empty_query_url.'),
      '#default_value' => $searchers ? Yaml::encode(['searchers' => $searchers]) : '',
      '#required' => TRUE,
      '#element_validate' => [[$this, 'validateAvailableSearchers']],
    ];
    $form['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#description' => $this->t('Text shown in the search field when it is empty. Use [PLACEHOLDER] in query_url as the separate token for submitted search terms.'),
      '#default_value' => $this->configuration['search_placeholder'] ?? 'Enter your search terms here',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * Validates the YAML searcher configuration.
   */
  public function validateAvailableSearchers(array &$element, FormStateInterface $form_state, array &$complete_form) {
    try {
      $configuration = Yaml::decode($element['#value']);
    }
    catch (\Exception $exception) {
      $form_state->setError($element, $this->t('The Available Searchers value must contain valid YAML.'));
      return;
    }

    if (!is_array($configuration) || !isset($configuration['searchers']) || !is_array($configuration['searchers']) || !$configuration['searchers']) {
      $form_state->setError($element, $this->t('The YAML must contain a non-empty searchers list.'));
      return;
    }

    $searchers = $configuration['searchers'];
    if (array_keys($searchers) !== range(0, count($searchers) - 1)) {
      $form_state->setError($element, $this->t('The searchers value must be a YAML list.'));
      return;
    }

    foreach ($searchers as $index => $searcher) {
      if (!is_array($searcher)) {
        $form_state->setError($element, $this->t('Searcher @number must be a mapping.', ['@number' => $index + 1]));
        return;
      }

      foreach (['name', 'query_url', 'empty_query_url'] as $key) {
        if (!isset($searcher[$key]) || !is_string($searcher[$key]) || trim($searcher[$key]) === '') {
          $form_state->setError($element, $this->t('Searcher @number must have a non-empty @key.', [
            '@number' => $index + 1,
            '@key' => $key,
          ]));
          return;
        }
      }

      foreach (['query_url', 'empty_query_url'] as $key) {
        $url = $searcher[$key];
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!UrlHelper::isValid($url, TRUE) || !in_array(strtolower((string) $scheme), ['http', 'https'], TRUE)) {
          $form_state->setError($element, $this->t('Searcher @number has an invalid @key. Use an absolute HTTP or HTTPS URL.', [
            '@number' => $index + 1,
            '@key' => $key,
          ]));
          return;
        }
      }

      if (strpos($searcher['query_url'], SearcherForm::QUERY_PLACEHOLDER) === FALSE) {
        $form_state->setError($element, $this->t('Searcher @number query_url must contain the [PLACEHOLDER] token.', ['@number' => $index + 1]));
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $configuration = Yaml::decode($form_state->getValue('available_searchers_yaml'));
    $this->configuration['available_searchers'] = $configuration['searchers'];
    $this->configuration['search_placeholder'] = trim($form_state->getValue('placeholder'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $searchers = $this->configuration['available_searchers'] ?? [];
    if (!$searchers) {
      return [];
    }

    return \Drupal::formBuilder()->getForm(
      'Drupal\umdds_dynamic_components\Form\SearcherForm',
      $searchers,
      $this->configuration['search_placeholder'] ?? 'Enter your search terms here'
    );
  }

}
