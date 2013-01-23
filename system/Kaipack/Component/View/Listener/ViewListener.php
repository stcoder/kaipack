<?php

namespace Kaipack\Component\View\Listener;

use Kaipack\Component\Http\DispatcherEvent;

use Symfony\Component\HttpFoundation\Response;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

class ViewListener implements ListenerAggregateInterface
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
        $this->_handlers[] = $events->attach(DispatcherEvent::EVENT_RENDER, array($this, 'injectTemplate'), -400);
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

    public function injectTemplate(DispatcherEvent $e)
    {
        $view = $e->getView();

        if ($view->getTemplate()) {
            return;
        }

        $route = $e->getRoute();

        $template = sprintf(
            'modules/%s/%s/%s',
            $route->getModule(),
            $route->getController(),
            $route->getAction()
        );

        $view->setTemplate($template);
    }

    public function onRender(DispatcherEvent $e)
    {
        $result = $e->getResult();
        if ($result instanceof Response) {
            return $result;
        }

        $view = $e->getView();
        if (is_array($result)) {
            $view->setVariables($result);
        }

        $response = $e->getResponse();
        $renderer = $view->render();
        $response->setContent($renderer);
        $e->setResult($renderer);
        return $renderer;
    }

    public function onPageNotFound(DispatcherEvent $e)
    {
        $vars = $e->getResult();
        if ($vars instanceof Response) {
            return;
        }

        $response = $e->getResponse();
        if ($response->getStatusCode() !== 404) {
            return;
        }

        $vm = $e->getView();

        if (is_string($vars)) {
            $vm->setVariable('message', $vars);
        } else {
            $vm->setVariable('message', 'Page not found');
        }

        $exception = $e->getParam('exception', null);
        if (!is_null($exception)) {
            $vm->setVariable('exception_message', $exception->getMessage());
            $vm->setVariable('exception_trace', $exception->getTraceAsString());
        }

        $vm->setTemplate('404');
    }
}