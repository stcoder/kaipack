<?php

namespace Kaipack\Component\Http;

use Kaipack\Core\Component\ComponentManager;

use Zend\EventManager\Event;

class DispatcherEvent extends Event
{
	/**
	 * events
	 */
	const EVENT_DISPATCH_ERROR = 'dispatcher.event.dispatcher-error';
	const EVENT_RESPONSE       = 'dispatcher.event.response';
	const EVENT_DISPATCH       = 'dispatcher.event.dispatch';
	const EVENT_REQUEST        = 'dispatcher.event.request';
	const EVENT_RENDER         = 'dispatcher.event.render';
	const EVENT_ROUTE          = 'dispatcher.event.route';

	/**
	 * errors type
	 */
	const ERROR_ROUTE_NOT_FOUND	= 'dispatcher.error.route-not-found';

	/**
	 * Устанавливаем параметр "ошибка".
	 *
	 * @param mixed $error
	 * @return DispatcherEvent
	 */
	public function setError($error)
	{
		$this->setParam('_error', $error);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getError()
	{
		return $this->getParam('_error', null);
	}

	/**
	 * Устанавливаем параметр "результат".
	 *
	 * @param $result
	 * @return DispatcherEvent
	 */
	public function setResult($result)
	{
		$this->setParam('_result_', $result);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getResult()
	{
		return $this->getParam('_result_', null);
	}

	/**
	 * @var \Kaipack\Core\Component\ComponentManager
	 */
	protected $_componentManager;

	/**
	 * @param \Kaipack\Core\Component\ComponentManager $componentManager
	 * @return EngineEvent
	 */
	public function setComponentManager(ComponentManager $componentManager)
	{
		$this->_componentManager = $componentManager;
		return $this;
	}

	/**
	 * @return \Kaipack\Core\Component\ComponentManager
	 */
	public function getComponentManager()
	{
		return $this->_componentManager;
	}
}