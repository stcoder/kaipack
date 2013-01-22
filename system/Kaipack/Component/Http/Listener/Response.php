<?php

namespace Kaipack\Component\Http\Listener;

use Kaipack\Component\Http\DispatcherEvent;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

class Response implements ListenerAggregateInterface
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
        $this->_handlers[] = $events->attach(DispatcherEvent::EVENT_RESPONSE, array($this, 'onResponse'), -1000);
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

    public function onResponse(DispatcherEvent $e)
    {
        $response = $e->getComponentManager()->get('http.response');
        $response->setContent($e->getResult());
        $response->send();
    }
}