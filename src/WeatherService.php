<?php

namespace Drupal\wkweather;

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\key\KeyRepositoryInterface;
use Drupal\wkweather\WeatherServiceInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Weather Service.
 */
class WeatherService implements WeatherServiceInterface {

  /**
   * The current user.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The key repository.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * WeatherService constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP Client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Config Factory.
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   The Key Repository.
   */
  public function __construct(Client $http_client, ConfigFactoryInterface $config_factory, KeyRepositoryInterface $key_repository) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->keyRepository = $key_repository;
  }

  /**
   * Returns the weather for the current user.
   */
  public function getWeather($city, $unit = 'metric') {

    $key_id = $this->configFactory->get('wkweather.settings')->get('api_key');
    $key = $this->keyRepository->getKey($key_id);
    $api_key = $key ? $key->getKeyValue() : '';


    $weather = [];

    if (!empty($api_key)) {

      $api_url = "http://api.weatherapi.com/v1/current.json?key=$api_key&q=$city&aqi=yes";

      try {
        $request = $this->httpClient->get($api_url);
        $response = json_decode($request->getBody());
      }
      catch (GuzzleException $e) {
        if ($e->getCode() == 400) {
          $weather['error'] = ['message' => $e->getMessage(), 'type' => 'invalid_request', 'code' => '400'];
        return $weather;

        }
      }

      if (!empty($response)) {
        $weather = [
          'location' => $response->location->name,
          'condition' => $response->current->condition->text,
          'icon' => $response->current->condition->icon,
          'temperature' => $unit == 'metric' ? $response->current->temp_c : $response->current->temp_f,
          'wind' => $unit == 'metric' ? $response->current->wind_kph : $response->current->wind_mph,
          'wind_direction' => $response->current->wind_dir,
          'precipitation' => $unit == 'metric' ? $response->current->precip_mm : $response->current->precip_in,
          'humidity' => $response->current->humidity,
        ];
      }
    }
    return $weather;
  }

}
