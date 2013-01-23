<?php

/**
 * Kaipack component.
 *
 * @package kaipack/component/http
 */
namespace Kaipack\Component\Http;

use Kaipack\Core\Component\ComponentAbstract;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Загрузчик компонента Http.
 *
 * Регистрирует слушателей на события диспетчера.
 *
 * @author Sergey Tihonov
 * @package kaipack/component/http
 * @version 1.1-a2
 */
class Dispatcher extends ComponentAbstract
{
    const ERROR_CONTROLLER_CANNOT_DISPATCH = 'error-controller-cannot-dispatch';
    const ERROR_CONTROLLER_NOT_FOUND       = 'error-controller-not-found';
    const ERROR_CONTROLLER_INVALID         = 'error-controller-invalid';
    const ERROR_MODULE_INVALID             = 'error-module-invalid';
    const ERROR_EXCEPTION                  = 'error-exception';
    const ERROR_ROUTER_NO_MATCH            = 'error-router-no-match';

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
        $container = $this->getContainer();
        $em        = $container->get('event-manager');

        $this->_event = new DispatcherEvent();
        $this->_event->setTarget($this);
        $this->_event->setRequest($container->get('http.request'));
        $this->_event->setResponse($container->get('http.response'));

        $container->set('event-dispatcher', $this->_event);

        // Регистрируем слушателей.
        $em->attachAggregate(new Listener\DispatcherListener());
        $em->attachAggregate(new Listener\ResponseListener());
        $em->attachAggregate(new Listener\RouterListener());
    }
}