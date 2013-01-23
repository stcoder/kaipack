<?php

namespace Kaipack\Component\Http;

class Router
{
    /**
     * @var array
     */
    protected $_routes = [];

    /**
     * @param Router\Route $route
     * @return Router
     */
    public function addRoute(Router\Route $route)
    {
        $this->_routes[] = $route;
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