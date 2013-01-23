<?php

/**
 * Kaipack
 * 
 * @package kaipack/core
 */
namespace Kaipack\Core\Listener;

use Kaipack\Core\EngineEvent;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * Регистрирует адаптеры кэша в момент вызова системного события 'engine.start'.
 * 
 * @author Sergey Tihonov
 * @package kaipack/core
 * @version 1.1-a2
 */
class CacheListener implements ListenerAggregateInterface
{
    /**
     * @var array
     */
    protected $_handlers = [];

    /**
     * Регистрируем обработчики событий.
     * 
     * @param \Zend\EventManager\EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->_handlers[] = $events->attach(EngineEvent::ENGINE_START, array($this, 'onInitAdapters'), 100);
    }

    public function detach(EventManagerInterface $events)
    {
        foreach ($this->_handlers as $key => $handler) {
            $events->detach($handler);
            unset($this->_handlers[$key]);
        }
        $this->_handlers = array();
    }

    /**
     * Инициализируем адаптеры кэша указанные в конфиге.
     * 
     * @param \Kaipack\Core\EngineEvent $e
     * @return null|array
     */
    public function onInitAdapters(EngineEvent $e)
    {
        $config = $e->getTarget()->getConfig();

        if (!isset($config->components->cache->adapters) && empty($config->components->cache->adapters)) {
            return null;
        }

        $adapterOptions = [];
        $cache = [];

        if (isset($config->components->cache->parameters) && !empty($config->components->cache->parameters)) {
            $adapterOptions = $config->components->cache->parameters->toArray();
        }

        foreach($config->components->cache->adapters as $name => $options) {
            $name = strtolower($name);

            if ($name === 'filesystem') {
                $cache_dir = (isset($options->cache_dir)) ? $options->cache_dir : '/misc/cache';
                $cache_dir = $e->getTarget()->getBaseDir() . $cache_dir;
                $options->cache_dir = $cache_dir;
            }

            $options = array_merge($adapterOptions, $options->toArray());

            $classAdapter = '\\Zend\\Cache\\Storage\\Adapter\\' . ucfirst($name);
            $cache[$name] = new $classAdapter($options);
        }

        $e->getTarget()->setCache($cache);
        return $cache;
    }
}