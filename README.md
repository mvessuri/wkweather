## INTRODUCTION

The wkWeather module is a simple Drupal weather module that displays current weather conditions for a configured location. It integrates with a weather API to fetch and display real-time weather data.

The primary use case for this module is:

- Displaying current weather conditions on a Drupal site
- Configuring different locations to show weather information
- Supporting both metric and imperial unit systems

## REQUIREMENTS

This module requires the following:
- Drupal 10 or 11
- Key module (for securely storing API keys)
- AI Agents module (for the wkWeather AI Agent submodule)

## INSTALLATION

Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/node/895232 for further information.

1. Place the module in your [modules/custom/]directory
2. Enable the module via Drush or the Extend admin page
3. Configure an API key for weather data access

## CONFIGURATION
- Navigate to `/admin/config/wkweather/config` to access the configuration form
- Enter your weather API key
- Set your desired city location
- Choose between metric or imperial units

## FEATURES

- Weather display page at `/weather`
- Twig component-based weather widget
- CSS styling with weather icons

## SUBMODULES

### wkWeather AI Agent

The module includes a submodule called "wkWeather AI Agent" that integrates with the AI Agents module to provide:

- Natural language configuration of the weather module
- Ability to ask questions about current weather conditions
- AI-powered responses about weather for the configured location
- Requires the AI Agents module and a configured AI provider

To use the AI Agent functionality, enable the wkweather_ai submodule and ensure you have proper permissions.

## MAINTAINERS

Current maintainers for Drupal 10:

- weKnow Team - https://www.drupal.org/weknow
