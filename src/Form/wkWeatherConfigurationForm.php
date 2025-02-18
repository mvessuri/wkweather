<?php

namespace Drupal\wkweather\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * wkWeather Configuration Form.
 */
class wkWeatherConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wkweather_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('wkweather.settings');

    $form['api_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
      '#key_filters' => ['type' => 'authentication'],
      '#description' => $this->t('Select the key to use for the weather API.'),
    ];

    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#default_value' => $config->get('city'),
    ];

    $form['unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Unit'),
      '#options' => [
        'metric' => $this->t('Metric'),
        'imperial' => $this->t('Imperial'),
      ],
      '#default_value' => $config->get('unit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('wkweather.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('city', $form_state->getValue('city'))
      ->set('unit', $form_state->getValue('unit'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['wkweather.settings'];
  }

}
