<?php

namespace Kaipack\Component\Http\Listener;

use Kaipack\Core\EngineEvent;
use Kaipack\Component\Http\DispatcherEvent;
use Kaipack\Component\Http\Router\Route;

use Symfony\Component\HttpFoundation\Response;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

class Dispatcher implements ListenerAggregateInterface
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
        $this->_handlers[] = $events->attach(EngineEvent::ENGINE_BOOTSTRAP, array($this, 'onDispatch'), -100);
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

    public function onDispatch(EngineEvent $e)
    {
        $em = $e->getComponentManager()->get('event-manager');
        $ed = $e->getComponentManager()->get('event-dispatcher');

        // Запускаем всех слушателей подписанных на событие EVENT_REQUEST дипетчера событий.
        // На данное событие могут быть подписаны например: плагины или другие компоненты.
        // Параметр события request может быть изменен.
        $em->trigger(DispatcherEvent::EVENT_REQUEST, $ed);

        // Получаем измененный параметр request.
        $request  = $ed->getParam('request');
        
        $response = $ed->getParam('response');

        // Запускаем всех слушателей подписанных на событие EVENT_ROUTE диспетчера событий.
        // Прерываем данное событие если результат является объектом маршрута.
        $result = $em->trigger(DispatcherEvent::EVENT_ROUTE, $ed, function($result) {
            return ($result instanceof Route);
        });

        // Результат не является объектом маршрута, значит маршрут не был найден.
        // Формируем ответ о неизвестной странице.
        if (!$result->stopped()) {
            $response->setStatusCode(404);
            $ed->setError(404);
            $em->trigger(DispatcherEvent::EVENT_DISPATCH_ERROR, $ed);
            return $this->_completeRequest($ed, $em);
        }

        // Запускаем всех слушателей подписанных на событие EVENT_DISPATCH диспетчера событий.
        $result = $em->trigger(DispatcherEvent::EVENT_DISPATCH, $ed, function($r) {
            return ($r instanceof Response);
        });

        if ($result->stopped()) {
            $response = $result->last();
            $response->send();
            return $response;
        }

        return $this->_completeRequest($ed, $em);
    }

    /**
     * Заврешить запрос.
     *
     * @param \Kaipack\Component\Http\DispatcherEvent $e
     * @param \Zend\EventManager\EventManager $em
     * @return mixed
     */
    protected function _completeRequest(DispatcherEvent $e, \Zend\EventManager\EventManager $em)
    {
        $em->trigger(DispatcherEvent::EVENT_RENDER, $e);
        $em->trigger(DispatcherEvent::EVENT_RESPONSE, $e);

        $response = $e->getParam('response');
        $request  = $e->getParam('request');

        $response->prepare($request);
        return $response;
    }
}