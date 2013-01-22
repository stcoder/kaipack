<?php

namespace Kaipack\Core\Component;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Zend\EventManager\EventManager;

class ComponentManager
{
	/**
	 * @var ContainerBuilder
	 */
	protected $_containerBuilder;

	public function __construct()
	{
		$this->_containerBuilder = new ContainerBuilder();
	}

	public function process()
	{
		$em = $this->_containerBuilder->get('event-manager');
		$em->attach(new ComponentListener());
	}

	/**
	 * @param string $id
	 * @param string|Definition $definition
	 * @param array $arguments
	 * @return ComponentManager
	 */
	public function definitionComponent($id, $className, $arguments = [])
	{
		if ($className instanceof Definition) {
			$this->_containerBuilder->setDefinition($id, $className);
			return $this;
		}

		$definition = new Definition($className, $arguments);
		$this->_containerBuilder->setDefinition($id, $definition);
		return $this;
	}

	/**
	 * @param $id
	 * @return \Symfony\Component\DependencyInjection\Definition
	 */
	public function getDefinitionComponent($id)
	{
		return $this->_containerBuilder->getDefinition($id);
	}

	/**
	 * @param string $id
	 * @param object $service
	 * @return ComponentManager
	 */
	public function set($id, $service)
	{
		$this->_containerBuilder->set($id, $service);
		return $this;
	}

	/**
	 * @param string $id
	 * @return object
	 */
	public function get($id)
	{
		return $this->_containerBuilder->get($id);
	}

	public function getInstance($id)
	{
		$cb			= $this->_containerBuilder;
		$definition = $cb->getDefinition($id);

		$parameterBag = $cb->getParameterBag();

		if (null !== $definition->getFile()) {
			require_once $parameterBag->resolveValue($definition->getFile());
		}

		$arguments = $cb->resolveServices(
			$parameterBag->unescapeValue($parameterBag->resolveValue($definition->getArguments()))
		);

		if (null !== $definition->getFactoryMethod()) {
			if (null !== $definition->getFactoryClass()) {
				$factory = $parameterBag->resolveValue($definition->getFactoryClass());
			} elseif (null !== $definition->getFactoryService()) {
				$factory = $this->get($parameterBag->resolveValue($definition->getFactoryService()));
			} else {
				throw new \RuntimeException('Cannot create service from factory method without a factory service or factory class.');
			}

			$service = call_user_func_array(array($factory, $definition->getFactoryMethod()), $arguments);
		} else {
			$r = new \ReflectionClass($parameterBag->resolveValue($definition->getClass()));

			$service = null === $r->getConstructor() ? $r->newInstance() : $r->newInstanceArgs($arguments);
		}

		return $service;
	}

	/**
	 * @return \Symfony\Component\DependencyInjection\ContainerBuilder
	 */
	public function getContainer()
	{
		return $this->_containerBuilder;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return ComponentManager
	 */
	public function setParam($key, $value)
	{
		$this->_containerBuilder->setParameter($key, $value);
		return $this;
	}

	/**
	 * @param $key
	 * @return mixed
	 */
	public function getParam($key)
	{
		return $this->_containerBuilder->getParameter($key);
	}
}