<?php

namespace Drupal\wkweather;

/**
 * Salutation interface.
 */
interface WeatherServiceInterface {

  /**
   * Returns a werather array.
   */
  public function getWeather($city);

}
