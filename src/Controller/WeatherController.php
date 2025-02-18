<?php

namespace Drupal\wkweather\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\wkweather\WeatherServiceInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WeatherController.
 */
class WeatherController extends ControllerBase {

  /**
   * Weather Service.
   *
   * @var \Drupal\wkweather\WeatherServiceInterface
   */
  protected $weatherService;

  /**
   * The wkweather settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * WeatherController constructor.
   *
   * @param \Drupal\wkweather\WeatherServiceInterface $weather_service
   *   Weather Service.
   */
  public function __construct(WeatherServiceInterface $weather_service, ConfigFactoryInterface $config_factory) {
    $this->weatherService = $weather_service;
    $this->config = $config_factory->get('wkweather.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('wkweather.weather'),
      $container->get('config.factory')
    );
  }

  /**
   * Returns a werather array.
   */
  public function weather() {

    $build = [];

    // Try to get the city from the configuration.
    $city = $this->config->get('city');

  if (empty($city)) {
    // Create a link to the configuration form.
    $config_url = Url::fromRoute('wkweather.config_form');

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['wkweather-message']],
      'content' => [
        '#type' => 'inline_template',
        '#template' => '{{ message }} {{ link }}.',
        '#context' => [
          'message' => $this->t('Please configure a city in the'),
          'link' => [
            '#type' => 'link',
            '#title' => $this->t('configuration form'),
            '#url' => $config_url,
          ],
        ],
      ],
    ];
  }

    else {
      $build = $this->buildWeather($city);
    }

    return $build;
  }

  /**
   * Build the weather array.
   */
  protected function buildWeather($city) {

    $unit = $this->config->get('unit');

    if (empty($unit)) {
      $unit = 'metric';
    }

    $weather = $this->weatherService->getWeather($city, $unit);

    if (empty($weather) || isset($weather['error'])) {
      return [
        '#type' => 'container',
        '#attributes' => ['class' => ['wkweather-message']],
        '#markup' => $this->t('Unable to get the weather for @city', ['@city' => $city]),
      ];
    }

    $build['weather'] = [
      '#type' => 'component',
      '#component' => 'wkweather:weather',
        '#props' => [
          'location' => $weather['location'],
          'condition' => $weather['condition'],
          'icon' => $weather['icon'],
          'temperature' => $weather['temperature'],
          'wind' => $weather['wind'],
          'wind_direction' => $weather['wind_direction'],
          'precipitation' => $weather['precipitation'],
          'humidity' => $weather['humidity'],
          'unit' => $unit,
        ],
    ];

    return $build;
  }

}
