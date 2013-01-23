<?php

namespace Kaipack\Component\Http;

use Zend\EventManager\Event;

class DispatcherEvent extends Event
{
    /**
     * events
     */
    const EVENT_DISPATCH_ERROR = 'dispatcher.event.dispatcher-error';
    const EVENT_RESPONSE       = 'dispatcher.event.response';
    const EVENT_DISPATCH       = 'dispatcher.event.dispatch';
    const EVENT_REQUEST        = 'dispatcher.event.request';
    const EVENT_RENDER         = 'dispatcher.event.render';
    const EVENT_ROUTE          = 'dispatcher.event.route';

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $_request;

    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    protected $_response;

    /**
     * @var \Kaipack\Component\Http\Router\Route
     */
    protected $_route;

    /**
     * @var \Kaipack\Component\View\ViewManager
     */
    protected $_view;

    /**
     * @var \Kaipack\Component\Module\ModuleManager
     */
    protected $_moduleManager;

    /**
     * @var \Kaipack\Component\Module\ModuleAbstract
     */
    protected $_module;

    /**
     * @param \Kaipack\Component\Module\ModuleAbstract $module
     * @return DispatcherEvent
     */
    public function setModule(\Kaipack\Component\Module\ModuleAbstract $module)
    {
        $this->_module = $module;
        return $this;
    }

    /**
     * @return \Kaipack\Component\Module\ModuleAbstract
     */
    public function getModule()
    {
        return $this->_module;
    }

    /**
     * @param \Kaipack\Component\Module\ModuleManager $moduleManager
     * @return DispatcherEvent
     */
    public function setModuleManager(\Kaipack\Component\Module\ModuleManager $moduleManager)
    {
        $this->_moduleManager = $moduleManager;
        return $this;
    }

    /**
     * @return \Kaipack\Component\Module\ModuleManager
     */
    public function getModuleManager()
    {
        return $this->_moduleManager;
    }

    /**
     * @param Router\Route $route
     * @return Dispatcher
     */
    public function setRoute(Router\Route $route)
    {
        $this->_route = $route;
        return $this;
    }

    /**
     * @return Router\Route
     */
    public function getRoute()
    {
        return $this->_route;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return DispatcherEvent
     */
    public function setRequest(\Symfony\Component\HttpFoundation\Request $request)
    {
        $this->_request = $request;
        return $this;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return DispatcherEvent
     */
    public function setResponse(\Symfony\Component\HttpFoundation\Response $response)
    {
        $this->_response = $response;
        return $this;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Устанавливаем параметр "ошибка".
     *
     * @param mixed $error
     * @return DispatcherEvent
     */
    public function setError($error)
    {
        $this->setParam('_error_', $error);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->getParam('_error_', null);
    }

    /**
     * Устанавливаем параметр "результат".
     *
     * @param $result
     * @return DispatcherEvent
     */
    public function setResult($result)
    {
        $this->setParam('_result_', $result);
        return $this;
    }

    /**
     * @param \Kaipack\Component\View\ViewManager $view
     * @return DispatcherEvent
     */
    public function setView(\Kaipack\Component\View\ViewManager $view)
    {
        $this->_view = $view;
        return $this;
    }

    /**
     * @return \Kaipack\Component\View\ViewManager
     */
    public function getView()
    {
        return $this->_view;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->getParam('_result_', null);
    }
}