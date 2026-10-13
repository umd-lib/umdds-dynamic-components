<?php

namespace Drupal\umdds_dynamic_components\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Builds the search form displayed by SearcherBlock.
 */
class SearcherForm extends FormBase {

  /**
   * Token in query URLs replaced with the submitted keywords.
   */
  public const QUERY_PLACEHOLDER = '[PLACEHOLDER]';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'umdds_searcher_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $searchers = [], $search_placeholder = '') {
    $options = [];
    foreach ($searchers as $index => $searcher) {
      $options[$index] = $searcher['name'];
    }

    $form['searcher'] = [
      '#type' => 'select',
      '#title' => $this->t('Search with'),
      '#options' => $options,
      '#required' => TRUE,
    ];
    $form['keywords'] = [
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#placeholder' => $search_placeholder,
      '#required' => FALSE,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    $form['#searchers'] = $searchers;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $searchers = $form['#searchers'];
    $selected = $form_state->getValue('searcher');
    if (!isset($searchers[$selected])) {
      $form_state->setErrorByName('searcher', $this->t('Select a valid searcher.'));
      return;
    }

    $searcher = $searchers[$selected];
    $keywords = trim($form_state->getValue('keywords') ?? '');
    $url = $keywords === ''
      ? $searcher['empty_query_url']
      : str_replace(self::QUERY_PLACEHOLDER, rawurlencode($keywords), $searcher['query_url']);

    $form_state->setResponse(new TrustedRedirectResponse($url));
  }

}
