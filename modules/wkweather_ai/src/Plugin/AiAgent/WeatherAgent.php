<?php

namespace Drupal\wkweather_ai\Plugin\AiAgent;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai_agents\Attribute\AiAgent;
use Drupal\ai_agents\PluginBase\AiAgentBase;
use Drupal\ai_agents\PluginInterfaces\AiAgentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\wkweather\WeatherServiceInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin implementation of the wK Weather Agent.
 */
#[AiAgent(
  id: 'wkweather',
  label: new TranslatableMarkup('wK Weather Agent'),
)]
class WeatherAgent extends AiAgentBase implements ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  /**
   * Task type.
   *
   * @var string
   */
  protected $taskType;

  /**
   * Weather Service.
   *
   * @var \Drupal\wkweather\WeatherServiceInterface
   */
  protected WeatherServiceInterface $weatherService;

  /**
   * The wkweather settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $config;

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $parent_instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $parent_instance->config = $container->get('config.factory');
    $parent_instance->weatherService = $container->get('wkweather.weather');
    $parent_instance->logger = $container->get('logger.factory')->get('wkweather');
    return $parent_instance;
  }

  /**
   * {@inheritDoc}
   */
  public function agentsNames() {
    return [
      'wK Weather Agent',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function agentsCapabilities() {
    return [
      'wkweather'  => [
        'name' => 'wK Weather Agent',
        'description' => 'This is able to configure the weather page to display the weather for a city and answer questions about the weather conditions for the configured location, including temperature, wind information, and rainfall. This can answer how is the weather without specifying a city or location. You do no need to ask for a city or location use the configuration. It is also able to answer questions about the weather configuration.',
        'inputs' => [
          'free_text' => [
            'name' => 'prompt',
            'type' => 'string',
            'description' => 'The prompt to change the city configuration or a question.',
            'default_value' => '',
          ],
        ],
        'outputs' => [
          'answers' => [
            'description' => 'The answers to the questions asked about weather.',
            'type' => 'string',
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function answerQuestion() {
    $data = $this->agentHelper->runSubAgent('answerQuestion',
    [
      'Weather configuration' => $this->getWeatherConfigurationAsString(),
      'Current weather conditions' => $this->getCurrentWeatherAsString(),
    ]);

    $answer = "";
    if (isset($data[0]['answer'])) {
      foreach ($data as $dataPoint) {
        $answer .= $dataPoint['answer'] . "\n";
      }
      return $answer;
    }

    return $this->t("Sorry, I got no answers for you.");
  }

  /**
   * Determine if the context is asking a question or wants a configuration change.
   *
   * @return string
   *   The context.
   */
  public function determineTypeOfTask() {
    $data = $this->agentHelper->runSubAgent('determineLocation', []);

    if (isset($data[0]['action']) && in_array($data[0]['action'], ['configure'])) {
      $this->data = $data;
      return $data[0]['action'];
    }

    if (isset($data[0]['action']) && $data[0]['action'] == 'question') {
      return 'question';
    }

    if (isset($data[0]['action']) && $data[0]['action'] == 'fail') {

      if (!empty($data[0]['fail_reason'])) {
        $this->information = $data[0]['fail_reason'];
      }

      return 'fail';
    }

    throw new \Exception('Invalid action in Web Determining task.');
  }

  /**
   * {@inheritDoc}
   */
  public function determineSolvability() {
    parent::determineSolvability();
    $this->taskType = $this->determineTypeOfTask();
    switch ($this->taskType) {
      case 'configure':
        return AiAgentInterface::JOB_SOLVABLE;

      case 'question':
        return AiAgentInterface::JOB_SHOULD_ANSWER_QUESTION;

      case 'fail':
        return AiAgentInterface::JOB_INFORMS;
    }

    return AiAgentInterface::JOB_NOT_SOLVABLE;
  }

  /**
   * Get the current weather as a string.
   *
   * @return string
   */
  public function getCurrentWeatherAsString() {

    $wkconfig = $this->config->get('wkweather.settings');
    $city = $wkconfig->get('city');
    $unit = $wkconfig->get('unit');
    $weather = $this->weatherService->getWeather($city, $unit);

    if (empty($weather) || isset($weather['error'])) {
      return $this->t('Unable to get the weather for @city', ['@city' => $city]);
    }

    $weather_condition = $this->t('The current weather in @city is @condition.
    The temperature is @temperature degrees.
    The wind is @wind on @wind_direction direction.
    The humidity is @humidity and the precipitation is @precipitation.
    ', [
      '@locatiom' => $weather['location'],
      '@condition' => $weather['condition'],
      '@temperature' => $weather['temperature'] . '°' . ($unit == 'metric' ? 'C' : 'F'),
      '@wind' => $weather['wind'] . ' ' . ($unit == 'metric' ? 'km/h' : 'mph'),
      '@wind_direction' => $weather['wind_direction'],
      '@humidity' => $weather['humidity'],
      '@precipitation' => $weather['precipitation'],
    ]);

    return $weather_condition;
  }

  /**
   * Get the weather configuration as a string.
   * @return string
   * */
  public function getWeatherConfigurationAsString() {
    $wkconfig = $this->config->get('wkweather.settings');
    $city = $wkconfig->get('city');
    $unit = $wkconfig->get('unit');
    return $this->t('The current city is @city and the unit is @unit.', ['@city' => $city, '@unit' => $unit]);
  }

  /**
   * {@inheritDoc}
   */
  public function solve() {

    switch ($this->data[0]['action']) {
      case 'configure':

        $conf = $this->config->getEditable('wkweather.settings');
        $conf->set('city', $this->data[0]['location']);
        $conf->set('unit', 'metric');
        $conf->save();

        // Log the configuration change.
        $this->logger->notice('The city has been configured to @city in metric units.', ['@city' => $this->data[0]['location']]);

        $message = $this->t('The city has been configured to @city.', ['@city' => $this->data[0]['location']]);
        return $message;

      default:
        $message = 'We could not figure out what you wanted to do.';
    }
    return $message;
  }

  /**
   * {@inheritDoc}
   */
  public function hasAccess() {
    // Check for permissions.
    if (!$this->currentUser->hasPermission('administer site configuration')) {
      return AccessResult::forbidden();
    }
    return parent::hasAccess();
  }

}
