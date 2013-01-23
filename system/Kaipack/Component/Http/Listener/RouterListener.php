<?php

namespace Kaipack\Component\Http\Listener;

use Kaipack\Core\EngineEvent;
use Kaipack\Component\Http\DispatcherEvent;
use Kaipack\Component\Http\Router\Route;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

class RouterListener implements ListenerAggregateInterface
{

    protected $_handlers = [];

    /**
     * @param EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events)
    {
        $this->_handlers[] = $events->attach(EngineEvent::ENGINE_BOOTSTRAP, array($this, 'onRouteBoot'), -10);
        $this->_handlers[] = $events->attach(DispatcherEvent::EVENT_ROUTE, array($this, 'onRouteMatch'));
        $this->_handlers[] = $events->attach(DispatcherEvent::EVENT_DISPATCH_ERROR, array($this, 'detectNotFoundError'));
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

    public function onRouteBoot(EngineEvent $e)
    {
        $container = $e->getTarget()->getContainer();
        $router    = $container->get('http.router');
        $config    = $container->get('config');

        if (isset($config->router->routes) && !empty($config->router->routes)) {
            foreach($config->router->routes as $routeName => $routeOptions) {
                $router->addRoute(new Route($routeName, $routeOptions));
            }
        }

        return $router;
    }

    public function onRouteMatch(DispatcherEvent $e)
    {
        $target    = $e->getTarget();
        $container = $target->getContainer();
        $em        = $container->get('event-manager');
        $routes    = $container->get('http.router')->getRoutes();
        $request   = $e->getRequest();
        $uri       = '/' . trim($request->getPathInfo(), '/');
        $matched   = null;

        foreach($routes as $route) {
            if ($route->isMatch($uri)) {
                $matched = $route;
                break;
            }
        }

        if (is_null($matched)) {
            $e->setError($target::ERROR_ROUTER_NO_MATCH);

            $results = $em->trigger(DispatcherEvent::EVENT_DISPATCH_ERROR, $e);
            if (count($results)) {
                $return  = $results->last();
            } else {
                $return = $e->getParams();
            }
            return $return;
        }

        $e->setRoute($matched);
        return $matched;
    }

    public function detectNotFoundError(DispatcherEvent $e)
    {
        $error = $e->getError();
        if (empty($error)) {
            return;
        }

        $target = $e->getTarget();

        switch ($error) {
            case $target::ERROR_CONTROLLER_NOT_FOUND:
            case $target::ERROR_CONTROLLER_INVALID:
            case $target::ERROR_MODULE_INVALID:
            case $target::ERROR_ROUTER_NO_MATCH:
                $response = $e->getResponse();
                $response->setStatusCode(404);
                break;
            default:
                return;
        }
    }
}