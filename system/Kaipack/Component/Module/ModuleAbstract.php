<?php

namespace Kaipack\Component\Module;

use Kaipack\Core\Component\ComponentManager;

abstract class ModuleAbstract
{
	/**
	 * @var \Kaipack\Core\Component\ComponentManager
	 */
	protected $_componentManager;

	/**
	 * @param \Kaipack\Core\Component\ComponentManager $cm
	 * @return ModuleAbstract
	 */
	public function setComponentManager(ComponentManager $cm)
	{
		$this->_componentManager = $cm;
		return $this;
	}

	/**
	 * @return \Kaipack\Core\Component\ComponentManager
	 */
	public function getComponentManager()
	{
		return $this->_componentManager;
	}

	/**
	 * Загрузчик модуля.
	 *
	 * @return void
	 */
	public function boot()
	{
		// функционал загрузки определить в модуле.
	}

	public function dispatch()
	{
		$e     = $this->_componentManager->get('event-dispatcher');
		$vm    = $this->_componentManager->get('view.view-manager');
		$route = $e->getParam('route');

		// Определяем контроллер.
		$namespace = get_class($this);
		$namespace = lcfirst(substr($namespace, 0, strrpos($namespace, '\\')));
		$controllerClass = sprintf(
			'%s\\controller\\%sController',
			$namespace,
			$this->_normalize($route->getController())
		);

		$controller = new $controllerClass;

		// Определяем действие.
		$action = lcfirst($this->_normalize($route->getAction())) . 'Action';

		if (!method_exists($controller, $action)) {
			$action = 'notFoundAction';
		}

		$arguments = [];
		foreach($route->getVariables() as $var) {
			$arguments[] = $var['value'];
		}

		// Выполняем и получаем результат действия.
		$actionResult = call_user_func_array(array($controller, $action), $arguments);

		// Если результатом выполненного метода является объект Response.
		// Возвращаем его.
		if ($actionResult instanceof \Symfony\Component\HttpFoundation\Response) {
			return $actionResult;
		}

		// Устанавливаем шаблон.
		if (!$vm->getTemplate()) {
			$vm->setTemplate(sprintf(
				'modules/%s/%s/%s',
				$route->getModule(),
				$route->getController(),
				$route->getAction()
			));
		}

		return $actionResult;
	}

	protected function _normalize($string)
	{
		$string = strtr($string, array('.' => ' '));
		$string = ucwords($string);
		$string = strtr($string, array(' ' => ''));
		return $string;
	}
}