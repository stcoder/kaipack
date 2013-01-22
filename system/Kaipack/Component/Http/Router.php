<?php

namespace Kaipack\Component\Http;

class Router
{
    /**
     * @var array
     */
    protected $_routes = array(
        'get'    => [],
        'post'   => [],
        'put'    => [],
        'delete' => [],
        'head'   => []
    );

    /**
     * @param Router\Route $route
     * @return Router
     */
    public function addRoute(Router\Route $route)
    {
        $this->_routes[$route->getMethod()][] = $route;
        return $this;
    }

    /**
     * @return array
     */
    public function getRoutes()
    {
        return $this->_routes;
    }
}