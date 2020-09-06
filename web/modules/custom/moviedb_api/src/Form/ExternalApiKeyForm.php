<?php

namespace Drupal\moviedb_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ExternalApiKeyForm.
 */
class ExternalApiKeyForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'moviedb_api.externalapikey',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'external_api_key_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('moviedb_api.externalapikey');
    $form['your_external_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MovieDB API Key'),
      '#description' => $this->t('https://www.themoviedb.org/settings/api'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('your_external_api_key'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('moviedb_api.externalapikey')
      ->set('your_external_api_key', $form_state->getValue('your_external_api_key'))
      ->save();
  }

}
