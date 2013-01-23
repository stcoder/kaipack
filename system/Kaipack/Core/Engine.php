<?php

/**
 * Kaipack
 * 
 * @package kaipack/core
 */
namespace Kaipack\Core;

use Zend\EventManager\EventManager;

/**
 * Движок ядра системы.
 * 
 * Основной класс системы kaipack. 
 * Подключаем конфиг проекта, инициализируем основные компоненты и
 * регистрируем слушателей на события ядра.
 * 
 * @author Sergey Tihonov
 * @package kaipack/core
 * @version 1.1-a2
 */
class Engine
{
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

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var string
     */
    protected $_componentDir;

    /**
     * @var string
     */
    protected $_baseDir;

    /**
     * @var array
     */
    protected $_cache;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected $_container;

    /**
     * @var \Zend\Cache\Storage\Adapter\AbstractAdapter
     */
    protected $_storageComponents;

    /**
     * Конуструктор движка.
     * 
     * @param string $configPath
     */
    public function __construct($configPath)
    {
        // Подключаем конфиг.
        $this->_config = new Config($configPath);

        // Определяем менеджер событий.
        $this->_eventManager = new EventManager();

        // Определяем события движка.
        $this->_event = new EngineEvent();
        $this->_event->setTarget($this);
        $this->setBaseDir(dirname($_SERVER['SCRIPT_FILENAME']));
        $this->setComponentDir(__DIR__ . '/../Component');

        // Регистрируем слушателей.
        $this->_eventManager->attachAggregate(new Listener\CacheListener());
        $this->_eventManager->attachAggregate(new Listener\ComponentListener());
    }

    /**
     * @return \Zend\EventManager\EventManager
     */
    public function getEventManager()
    {
        return $this->_eventManager;
    }

    /**
     * @param string $dir
     * @return Engine
     */
    public function setComponentDir($dir)
    {
        $this->_componentDir = $dir;
        return $this;
    }

    /**
     * @return string
     */
    public function getComponentDir()
    {
        return $this->_componentDir;
    }

    /**
     * @param $dir
     * @return Engine
     */
    public function setBaseDir($dir)
    {
        $this->_baseDir = $dir;
        return $this;
    }

    /**
     * @return string
     */
    public function getBaseDir()
    {
        return $this->_baseDir;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        if (isset($this->_config->parameters->debug) && $this->_config->parameters->debug === false) {
            return false;
        }

        return true;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * @param array $cache
     * @return Engine
     */
    public function setCache($cache)
    {
        $this->_cache = $cache;
        return $this;
    }

    /**
     * @return string
     */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * @param \Composer\Autoload\ClassLoader $loader
     * @return Engine
     */
    public function setClassLoader(\Composer\Autoload\ClassLoader $loader)
    {
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
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @return Engine
     */
    public function setContainer(\Symfony\Component\DependencyInjection\ContainerBuilder $container)
    {
        $this->_container = $container;
        return $this;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    public function getContainer()
    {
        return $this->_container;
    }

    /**
     * @return \Zend\Cache\Storage\Adapter\AbstractAdapter
     */
    public function getStorageComponents()
    {
        if ($this->_storageComponents) {
            return $this->_storageComponents;
        }

        $cacheAdapters = $this->getCache();

        $storage_components = '';
        if (isset($this->_config->parameters->storage_components)) {
            $storage_components = $this->_config->parameters->storage_components;
        }

        $storage_components = ($storage_components)?:'filesystem';

        if (!isset($cacheAdapters[$storage_components])) {
            throw new \Exception(sprintf(
                'Для хранилища компонентов используется неизвестный адаптер "%s" кэша',
                $storage_components
            ));
        }

        $this->_storageComponents = $cacheAdapters[$storage_components];
        return $this->_storageComponents;
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