<?php

namespace Kaipack\Core\Component;

use Kaipack\Core\EngineEvent;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ComponentListener implements ListenerAggregateInterface
{
	protected $_handlers = [];

	/**
	 * @var \Zend\Cache\Storage\Adapter\Filesystem
	 */
	protected $_cache;

	public function __construct()
	{
		return $this;
	}

	public function attach(EventManagerInterface $events)
	{
		$this->_handlers[] = $events->attach(EngineEvent::ENGINE_START, array($this, 'onLoadComponents'));
	}

	public function detach(EventManagerInterface $events)
	{
		foreach ($this->_handlers as $key => $handler) {
			$events->detach($handler);
			unset($this->_handlers[$key]);
		}
		$this->_handlers = array();
	}

	public function onLoadComponents(EngineEvent $e)
	{
		$cm		= $e->getComponentManager();
		$cache	= $this->_cache = $cm->get('cache.filesystem');
		$config	= $cm->get('config');

		$componentsConfig = [];

		if ($cache->hasItem('components-config')) {
			$componentsConfig = unserialize($cache->getItem('components-config'));
		}

		if (empty($componentsConfig)) {
			$componentDir = $cm->getParam('component-dir');

			// Ищем настройки всех компонентов.
			$configFiles = new \GlobIterator($componentDir . '/*/config.json', \FilesystemIterator::KEY_AS_FILENAME);

			if ($configFiles->count() === 0) {
				return;
			}

			foreach($configFiles as $configFile) {
				$configRealPath = $configFile->getRealPath();

				$configDefinition = $cm->getDefinitionComponent('config.factory');
				$configDefinition->setArguments(array(
					$configRealPath
				));

				$componentConfig = $cm->getInstance('config.factory');
				$componentsConfig[$componentConfig->name] = $componentConfig;
			}

			if (isset($config->application->debug) && $config->application->debug === false) {
				$cache->setItem('components-config', serialize($componentsConfig));
			}
		}

		// Определяем компоненты.
		foreach($componentsConfig as $component => $componentConfig) {

			$parameters = isset($componentConfig->parameters) ? $componentConfig->parameters : [];
			$services	= isset($componentConfig->services) ? $componentConfig->services : [];

			// Определяем параметры.
			$this->_setParams($component, $parameters, $cm);

			// Переопределяем параметры из конфига проекта.
			if (isset($config->components->{$component})) {
				$this->_setParams($component, $config->components->{$component}, $cm);
			}

			// Определяем службы.
			foreach($services as $serviceName => $serviceParams) {

				$serviceName = sprintf('%s.%s', $component, $serviceName);

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

				$cm->definitionComponent($serviceName, $definition);
			}

			// Определяем загрузчик компонента.
			if (isset($componentConfig->boot)) {

				if (!isset($componentConfig->boot->name)) {
					continue;
				}

				$name = sprintf('%s.%s', $component, $componentConfig->boot->name);

				if (!isset($componentConfig->boot->class)) {
					continue;
				}

				$bootClass	= $componentConfig->boot->class;
				$boot		= new $bootClass;

				if (!($boot instanceof ComponentAbstract)) {
					throw new \DomainException(sprintf(
						'Загрузчик компонента %s недействителен. Должен расширять ComponentAbstract',
						$component
					));
				}

				$boot->setComponentManager($cm);
				$boot->boot();

				$cm->set($name, $boot);
			}

		}
	}

	protected function _setParams($component, $parameters, ComponentManager $cm)
	{
		$paramName = $component;

		foreach($parameters as $key => $val) {
			$paramName = $component . '.' . $key;
			if (is_object($val)) {
				$this->_setParams($paramName, $val, $cm);
				continue;
			}
			$cm->setParam($paramName, $val);
		}
	}
}