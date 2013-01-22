<?php

namespace Kaipack\Component\View\Listener;

use Kaipack\Component\Http\DispatcherEvent;

use Symfony\Component\HttpFoundation\Response;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

class View implements ListenerAggregateInterface
{
    /**
     * @var array
     */
    protected $_handlers = [];

    /**
     * @param EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events)
    {
        $this->_handlers[] = $events->attach(DispatcherEvent::EVENT_RENDER, array($this, 'onRender'), -500);
        $this->_handlers[] = $events->attach(DispatcherEvent::EVENT_DISPATCH_ERROR, array($this, 'onPageNotFound'));
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

    public function onRender(DispatcherEvent $e)
    {
        $vm = $e->getComponentManager()->get('view.view-manager');

        $result = $e->getResult();

        if (is_array($result)) {
            $vm->setVariables($e->getResult());
        }

        $render = $vm->render();
        $e->setResult($render);
        return $result;
    }

    public function onPageNotFound(DispatcherEvent $e)
    {
        if ($e->getError() !== 404) {
            return;
        }

        $vm = $e->getComponentManager()->get('view.view-manager');
        $vm->setTemplate('404');
    }
}