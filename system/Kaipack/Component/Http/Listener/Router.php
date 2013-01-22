<?php

namespace Kaipack\Component\Http\Listener;

use Kaipack\Core\EngineEvent;
use Kaipack\Component\Http\DispatcherEvent;
use Kaipack\Component\Http\Router\Route;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

class Router implements ListenerAggregateInterface
{

	protected $_handlers = [];

	/**
	 * @param EventManagerInterface $events
	 */
	public function attach(EventManagerInterface $events)
	{
		$this->_handlers[] = $events->attach(EngineEvent::ENGINE_BOOTSTRAP, array($this, 'onRouteBoot'), -10);
		$this->_handlers[] = $events->attach(DispatcherEvent::EVENT_ROUTE, array($this, 'onRouteMatch'));
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
		$cm		= $e->getComponentManager();
		$router = $cm->get('http.router');
		$config = $cm->get('config');

		if (isset($config->router->routes) && !empty($config->router->routes)) {
			foreach($config->router->routes as $routeName => $routeOptions) {
				$router->addRoute(new Route($routeName, $routeOptions));
			}
		}

		return $router;
	}

	public function onRouteMatch(DispatcherEvent $e)
	{
		$cm			= $e->getComponentManager();
		$routes		= $cm->get('http.router')->getRoutes();
		$request	= $cm->get('http.request');

		$method = strtolower($request->getMethod());

		if (!isset($routes[$method])) {
			return null;
		}

		$uri = '/' . trim($request->getPathInfo(), '/');

		foreach($routes[$method] as $route) {
			if ($route->isMatch($uri)) {
				$e->setParam('route', $route);
				return $route;
			}
		}

		return null;
	}
}