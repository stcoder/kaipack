<?php

/**
 * Kaipack
 * 
 * @package kaipack/core
 */
namespace Kaipack\Core\Listener;

use Kaipack\Core\Config;
use Kaipack\Core\EngineEvent;
use Kaipack\Core\Component\ComponentAbstract;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * Регистрирует компоненты в момент вызова системного события 'engine.start'.
 * 
 * @author Sergey Tihonov
 * @package kaipack/core
 * @version 1.1-a2
 */
class ComponentListener implements ListenerAggregateInterface
{
    /**
     * @var array
     */
    protected $_handlers = [];

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected $_container;

    /**
     * Регистрируем обработчики событий.
     * 
     * @param \Zend\EventManager\EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->_handlers[] = $events->attach(EngineEvent::ENGINE_START, array($this, 'onInitComponent'), 90);
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
     * Определяем компоненты системы.
     * 
     * @param \Kaipack\Core\EngineEvent $e
     * @return null|array
     */
    public function onInitComponent(EngineEvent $e)
    {
        $target        = $e->getTarget();
        $config        = $target->getConfig();
        $cacheAdapters = $target->getCache();

        $cache = $target->getStorageComponents();
        $components = [];

        // Если дебаг режим выключен и есть запись в кэше.
        if (!$target->isDebug() && $cache->hasItem('components')) {
            $components = unserialize($cache->getItem('components'));
        }

        if (empty($components)) {
            $componentDir = $target->getComponentDir();

            // Ищем настройки всех компонентов.
            $configFiles = new \GlobIterator($componentDir . '/*/config.json', \FilesystemIterator::KEY_AS_FILENAME);

            if ($configFiles->count() === 0) {
                return null;
            }

            foreach($configFiles as $configFile) {
                $configRealPath = $configFile->getRealPath();
                $componentConfig = new Config($configRealPath);
                $components[$componentConfig->name] = $componentConfig;
            }

            if (!$target->isDebug() && $cache->hasItem('components')) {
                $cache->setItem('components', serialize($components));
            }
        }

        $this->_container = $container = new ContainerBuilder();
        $target->setContainer($container);

        // Устанавливаем параметры из конфига в контейнер.
        if (isset($config->parameters) && !empty($config->parameters)) {
            foreach($config->parameters as $name => $value) {
                $container->setParameter(strtolower($name), $value);
            }
        }

        // Устанавливаем основные параметры и службы.
        $container->setParameter('base-dir', $target->getBaseDir());
        $container->set('class-loader', $target->getClassLoader());
        $container->set('event-manager', $target->getEventManager());

        // Регистрируем адаптеры кэша.
        foreach($cacheAdapters as $cacheName => $cacheInstance) {
            $container->set('cache.' . $cacheName, $cacheInstance);
        }

        // Регистрируем основной конфиг.
        $container->set('config', $config);

        // Определяем компоненты.
        foreach($components as $name => $componentConfig) {
            $parameters = isset($componentConfig->parameters) ? $componentConfig->parameters : [];
            $services   = isset($componentConfig->services) ? $componentConfig->services : [];

            // Определяем параметры.
            $this->_setParams($name, $parameters);

            // Переопределяем параметры из конфига проекта.
            if (isset($config->components->{$name})) {
                $this->_setParams($name, $config->components->{$name});
            }

            // Определяем службы.
            foreach($services as $serviceName => $serviceParams) {

                $serviceName = sprintf('%s.%s', $name, $serviceName);

                if (!isset($serviceParams->class)) {
                    continue;
                }

                $definition = new Definition($serviceParams->class);

                if (isset($serviceParams->factoryMethod)) {
                    $factoryClass = '';

                    if (isset($serviceParams->factoryClass) && $serviceParams->factoryClass !== 'self') {
                        $factoryClass = $serviceParams->factoryClass;
                    }

                    if (empty($factoryClass)) {
                        $factoryClass = $serviceParams->class;
                    }

                    $definition->setFactoryClass($factoryClass);
                    $definition->setFactoryMethod($serviceParams->factoryMethod);
                }

                // Установка аргументов для служб компонента.
                if (isset($serviceParams->arguments)) {
                    $arguments = $serviceParams->arguments;
                    if (is_string($arguments)) {
                        $arguments = array($arguments);
                    }

                    if (is_object($arguments)) {
                        $arguments = $arguments->toArray();
                    }

                    // Ищем аргументы требующие зависимость.
                    foreach($arguments as $index => $argument) {
                        if (is_string($argument) && (false !== strpos($argument, '@'))) {
                            $arguments[$index] = new Reference(substr($argument, 1));
                            continue;
                        }
                    }
                    $definition->setArguments($arguments);
                }

                $container->setDefinition($serviceName, $definition);
            }

            // Определяем загрузчик компонента.
            if (isset($componentConfig->boot)) {

                if (!isset($componentConfig->boot->name)) {
                    continue;
                }

                $bootName = sprintf('%s.%s', $name, $componentConfig->boot->name);

                if (!isset($componentConfig->boot->class)) {
                    continue;
                }

                $bootClass = $componentConfig->boot->class;
                $boot      = new $bootClass;

                if (!($boot instanceof ComponentAbstract)) {
                    throw new \DomainException(sprintf(
                        'Загрузчик компонента %s недействителен. Должен расширять ComponentAbstract',
                        $name
                    ));
                }

                $boot->setContainer($container);
                $boot->boot();

                $container->set($bootName, $boot);
            }
        }
    }

    /**
     * Вспомогательная функция.
     * 
     * Устанавливает параметры компонента.
     * 
     * @param string $component
     * @param mixed $component
     * @return void
     */
    protected function _setParams($component, $parameters)
    {
        $paramName = $component;

        foreach($parameters as $key => $val) {
            $paramName = $component . '.' . $key;
            if (is_object($val)) {
                $this->_setParams($paramName, $val, $cm);
                continue;
            }
            $this->_container->setParameter($paramName, $val);
        }
    }
}