<?php

namespace Kaipack\Component\Http;

use Kaipack\Core\Component\ComponentAbstract;

/**
 * Компонент диспетчер.
 */
class Dispatcher extends ComponentAbstract
{
	/**
	 * @var object
	 */
	protected $_request;

	/**
	 * @var object
	 */
	protected $_response;

	/**
	 * @var \Kaipack\Component\Http\DispatcherEvent
	 */
	protected $_event;

	/**
	 * Загрузчик компонента Dispatcher.
	 *
	 * Регистрирует слушателей на события диспетчера.
	 *
	 * @return void
	 */
	public function boot()
    {
        $this->_request = $this->getComponentManager()->get('http.request');

        $this->_response = $this->getComponentManager()->get('http.response');

		$this->_event = new DispatcherEvent();
		$this->_event->setParam('request', $this->_request);
		$this->_event->setParam('response', $this->_response);
		$this->_event->setComponentManager($this->getComponentManager());

		$this->getComponentManager()->set('event-dispatcher', $this->_event);
		$em = $this->getComponentManager()->get('event-manager');

		// Регистрируем слушателей.
		$em->attachAggregate(new Listener\Dispatcher());
		$em->attachAggregate(new Listener\Response());
		$em->attachAggregate(new Listener\Router());
    }
}