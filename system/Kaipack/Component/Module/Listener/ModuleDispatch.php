<?php

namespace Kaipack\Component\Module\Listener;

use Kaipack\Component\Http\DispatcherEvent;

use Kaipack\Component\Module\ModuleAbstract;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

class ModuleDispatch implements ListenerAggregateInterface
{
	/**
	 * @var array
	 */
	protected $_handlers = [];

	/**
	 * @var
	 */
	protected $_cm = null;

	/**
	 * @param EventManagerInterface $events
	 */
	public function attach(EventManagerInterface $events)
	{
		$this->_handlers[] = $events->attach(DispatcherEvent::EVENT_DISPATCH, array($this, 'onDispatch'), -50);
	}

	/**
	 * @param EventManagerInterface $events
	 */
	public function detach(EventManagerInterface $events)
	{
		foreach ($this->_handlers as $key => $handler) {
			$events->detach($handler);
			unset($this->_handlers[$key]);
		}
		$this->_handlers = array();
	}

	public function onDispatch(DispatcherEvent $e)
	{
		$route = $e->getParam('route');
		$em    = $e->getComponentManager()->get('event-manager');
		$mm    = $e->getComponentManager()->get('module.module-manager');

		$moduleOptions = $mm->getModule($route->getModule());

		// Если модуль не найден. Показываем страницу 404.
		if (is_null($moduleOptions)) {
			$e->setError(404);
			$em->trigger(DispatcherEvent::EVENT_DISPATCH_ERROR, $e);
			return;
		}

		$module = new $moduleOptions['class'];

		// Модуль должен расширять абстрактный класс ModuleAbstract.
		// Если это не так то, показываем страницу 404.
		if (!($module instanceof ModuleAbstract)) {
			$e->setError(404);
			$em->trigger(DispatcherEvent::EVENT_DISPATCH_ERROR, $e);
			return;
		}

		// Передаем модулю объект менеджера компонентов.
		$module->setComponentManager($e->getComponentManager());

		// Выполняем загрузку модуля.
		$module->boot();

		$result = $module->dispatch();

		if ($result instanceof \Symfony\Component\HttpFoundation\Response) {
			return $result;
		}

		$e->setResult($result);
		return $result;
	}
}