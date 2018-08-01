<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Data\Blueprints;
use RocketTheme\Toolbox\Event\Event;

/**
 * [SEO Robots Plugin]
 *
 * [Provide a simple way to manage robots from admin]
 *
 * Class SeoRobotsPlugin
 * @package Grav\Plugin
 * @license MIT License by russiansmack
 */
class SeoRobotsPlugin extends Plugin
{

  /**
   * @return array
   *
   * The getSubscribedEvents() gives the core a list of events
   *     that the plugin wants to listen to. The key of each
   *     array section is the event that the plugin listens to
   *     and the value (in the form of an array) contains the
   *     callable (or function) as well as the priority. The
   *     higher the number the higher the priority.
   */
  public static function getSubscribedEvents()
  {
    return [
      'onPluginsInitialized' => ['onPluginsInitialized', 0]
    ];
  }

  /**
   * Initialize the plugin
   */
  public function onPluginsInitialized()
  {
    // If we are in admin
    if ($this->isAdmin()) {
      // Enable the main event we are interested in
      $this->enable([
        'onBlueprintCreated' => ['onBlueprintCreated', 0]
      ]);

      return;
    }

    // If plugin is enabled and if we are not in admin
    if ($this->config['plugins.seo-robots.enabled']) {
      // Enable the main event we are interested in
      $this->enable([
        'onPageInitialized' => ['onPageInitialized', 0]
      ]);
    }
  }

  /**
   * On Page Initialized Hook
   */
  public function onPageInitialized()
  {
    $page = $this->grav['page'];
    $meta = $page->metadata();
    $header = $page->header();
    $config = $this->mergeConfig($page);

    /**
     * Set metas
     */
    $meta = $this->getRobotsMeta($meta, $header, $config);

    // Return updated meta
    $page->metadata($meta);
  }

  /**
   * On Blueprint Created Hook
   */
  public function onBlueprintCreated(Event $event)
  {
    static $inEvent = false;

    // Add Tinyseo tab if page is not a modular
    if (0 !== strpos($event['type'], 'modular/')) {
      $blueprint = $event['blueprint'];

      if (!$inEvent && $blueprint->get('form/fields/tabs', null, '/')) {
        $inEvent = true;
        $blueprints = new Blueprints(__DIR__ . '/blueprints/');
        $extends = $blueprints->get($this->name);
        $blueprint->extend($extends, true);
        $inEvent = false;
      }
    }
  }

  /**
   * Get Robots meta
   */
  private function getRobotsMeta($meta, $header, $config)
  {
    function metaRobotsEnabled($param)
    {
      return !empty(array_filter($param, function ($value) {
        return $value === true;
      }));
    }
    // Check if page does not already have meta robots
    if (!isset($meta['robots'])) {

      $pageMetaRobotsEnabled = isset($header->meta_robots) ? metaRobotsEnabled($header->meta_robots) : false;
      $pluginMetaRobotsEnabled = metaRobotsEnabled($config['meta_robots']);

      // If Tinseo have page wide meta robots use it
      // Else if Tinyseo have site wide meta robots use it
      if ($pageMetaRobotsEnabled) $metaRobots = $header->meta_robots;
      elseif ($pluginMetaRobotsEnabled) $metaRobots = $config['meta_robots'];

      // Robots meta
      if (isset($metaRobots)) {
        $filteredArray = array_keys($metaRobots, true);
        $robotsContent = implode(', ', $filteredArray);

        $meta['robots'] = [
          'name' => 'robots',
          'content' => $robotsContent
        ];
      }
    }

    return $meta;
  }
  
  /**
   * Get default robots meta for admin blueprint
   */
  public static function defaultRobotsMeta()
  {
    $config = Grav::instance()['config'];
    $page = Grav::instance()['admin']->page(true);
    $meta = $page->metadata();

    // Default meta robots from Tinyseo config
    $meta_robots = $config['plugins.seo-robots.meta_robots'];

    // If page have meta robots
    if (isset($meta['robots'])) {
      $pageMetaRobotsList = str_replace(' ', '', $meta['robots']['content']);
      $pageMetaRobotsArray = explode(',', $pageMetaRobotsList);
      $pageMetaRobots = array_fill_keys($pageMetaRobotsArray, true);

      $meta_robots = $pageMetaRobots;
    };

    return $meta_robots;
  }

}
