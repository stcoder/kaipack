<?php

namespace Kaipack\Component\Http\Listener;

use Kaipack\Core\EngineEvent;
use Kaipack\Component\Http\DispatcherEvent;
use Kaipack\Component\Http\Router\Route;

use Symfony\Component\HttpFoundation\Response;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

class DispatcherListener implements ListenerAggregateInterface
{
    /**
     * @var array
     */
    protected $_handlers = [];

    /**
     * @var \Zend\EventManager\EventManager
     */
    protected $_eventManager;

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
        $container = $e->getTarget()->getContainer();
        $ed        = $container->get('event-dispatcher');
        $this->_eventManager = $em = $container->get('event-manager');

        $stopped = function($r) use ($ed) {
            if ($r instanceof Response) {
                return true;
            }
            if ($ed->getError()) {
                return true;
            }
            return false;
        };

        if ($ed->getError()) {
            return $this->_completeRequest($ed);
        }

        $result = $em->trigger(DispatcherEvent::EVENT_ROUTE, $ed, $stopped);
        if ($result->stopped()) {
            $response = $result->last();
            if ($response instanceof Response) {
                $em->trigger(DispatcherEvent::EVENT_RESPONSE, $ed);
                return $response;
            }
            if ($ed->getError()) {
                return $this->_completeRequest($ed);
            }
            return $ed->getResponse();
        }
        if ($ed->getError()) {
            return $this->_completeRequest($ed);
        }

        $result = $em->trigger(DispatcherEvent::EVENT_DISPATCH, $ed, $stopped);

        $response = $result->last();
        if ($response instanceof Response) {
            $em->trigger(DispatcherEvent::EVENT_RESPONSE, $ed);
            return $response;
        }

        return $this->_completeRequest($ed);
    }

    /**
     * Заврешить запрос.
     *
     * @param \Kaipack\Component\Http\DispatcherEvent $e
     * @param \Zend\EventManager\EventManager $em
     * @return mixed
     */
    protected function _completeRequest(DispatcherEvent $e)
    {
        $this->_eventManager->trigger(DispatcherEvent::EVENT_RENDER, $e);
        $this->_eventManager->trigger(DispatcherEvent::EVENT_RESPONSE, $e);
        return $e->getResponse();
    }
}