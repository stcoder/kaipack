<?php

namespace Kaipack\Core;

use Zend\EventManager\EventManager;
use Zend\Cache\StorageFactory;

class Engine
{
    /**
     * @var Component\ComponentManager
     */
    protected $_componentManager;

    /**
     * @var \Composer\Autoload\ClassLoader
     */
    protected $_loader;

    /**
     * @var \Zend\EventManager\EventManager
     */
    protected $_eventManager;

    /**
     * @var EngineEvent
     */
    protected $_event;

    public function __construct()
    {
        $this->_componentManager = new Component\ComponentManager();

        $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
        $component_dir = __DIR__ . '/../Component';

        $this->_componentManager->setParam('base-dir', $base_dir);
        $this->_componentManager->setParam('component-dir', $component_dir);
        $this->_componentManager->setParam('engine.charset', 'utf-8');

        // регистрируем менеджер событий
        $this->_event        = new EngineEvent();
        $this->_eventManager = new EventManager();

        $this->_event->setComponentManager($this->_componentManager);
        $this->_componentManager->set('event-manager', $this->_eventManager);

        // регистрируем конфиг
        $this->_componentManager->definitionComponent('config.factory', '\\Kaipack\\Core\\Config');

        $configDefinition = $this->_componentManager->getDefinitionComponent('config.factory');
        $configDefinition->setArguments(array(
            $this->_componentManager->getParam('base-dir') . '/config/project.config.json'
        ));

        $config = $this->_componentManager->get('config.factory');
        $this->_componentManager->set('config', $config);

        // регистрируем кэш
        if (isset($config->cache->adapters) && !empty($config->cache->adapters)) {

            foreach($config->cache->adapters as $adapterName => $options) {

                $adapterName = strtolower($adapterName);

                $options = $options->toArray();

                if ($adapterName === 'filesystem') {
                    $cache_dir = (isset($options['cache_dir'])) ? $options['cache_dir'] : '/misc/cache';
                    $cache_dir = $this->_componentManager->getParam('base-dir') . $cache_dir;
                    $options['cache_dir'] = $cache_dir;
                }


                $cacheFactory = StorageFactory::adapterFactory($adapterName, $options);
                $this->_componentManager->set('cache.' . $adapterName, $cacheFactory);

            }

        }

        $this->_componentManager->process();
    }

    /**
     * @return Component\ComponentManager
     */
    public function getComponentManager()
    {
        return $this->_componentManager;
    }

    /**
     * @param \Composer\Autoload\ClassLoader $loader
     * @return Engine
     */
    public function setClassLoader(\Composer\Autoload\ClassLoader $loader)
    {
        $this->_componentManager->set('class-loader', $loader);
        $this->_loader = $loader;
        return $this;
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public function getClassLoader()
    {
        return $this->_loader;
    }

    /**
     * @return void
     */
    public function run()
    {
        $this->_eventManager->trigger(EngineEvent::ENGINE_START,     $this, $this->_event);
        $this->_eventManager->trigger(EngineEvent::ENGINE_BOOTSTRAP, $this, $this->_event);
        $this->_eventManager->trigger(EngineEvent::ENGINE_STOP,      $this, $this->_event);
    }
}