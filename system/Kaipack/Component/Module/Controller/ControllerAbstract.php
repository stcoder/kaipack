<?php

namespace Kaipack\Component\Module\Controller;

use Kaipack\Component\Http\DispatcherEvent;
use Kaipack\Component\Database\DatabaseManager;
use Zend\EventManager\EventManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class ControllerAbstract
{
    /**
     * @var \Kaipack\Component\Http\DispatcherEvent
     */
    protected $_event;

    /**
     * @var \Zend\EventManager\EventManager
     */
    protected $_eventManager;

    /**
     * @var \Kaipack\Component\Database\DatabaseManager;
     */
    protected $_databaseManager;

    protected $_pluginManager;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $_request;

    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    protected $_response;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected $_container;

    /**
     * @return mixed
     */
    public function init()
    {
        // функционал определить в контроллере
    }

    /**
     * @param \Kaipack\Component\Http\DispatcherEvent $e
     * @return ControllerAbstract
     */
    public function setEvent(DispatcherEvent $e)
    {
        $this->_event = $e;
        return $this;
    }

    /**
     * @return \Kaipack\Component\Http\DispatcherEvent
     */
    public function getEvent()
    {
        return $this->_event;
    }

    /**
     * @param \Zend\EventManager\EventManager $em
     * @return ControllerAbstract
     */
    public function setEventManager(EventManager $em)
    {
        $this->_eventManager = $em;
        return $this;
    }

    /**
     * @return \Zend\EventManager\EventManager
     */
    public function getEventManager()
    {
        return $this->_eventManager;
    }

    /**
     * @param \Kaipack\Component\Database\DatabaseManager $dm
     * @return ControllerAbstract
     */
    public function setDatabaseManager(DatabaseManager $dm)
    {
        $this->_databaseManager = $dm;
        return $this;
    }

    /**
     * @return \Kaipack\Component\Database\DatabaseManager
     */
    public function getDatabaseManager()
    {
        return $this->_databaseManager;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return ControllerAbstract
     */
    public function setRequest(Request $request)
    {
        $this->_request = $request;
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
     * @param array $data
     * @param int   $status
     * @param array $headers
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function _json(array $data, $status = 200, $headers = array())
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * @param string $uri
     * @param int    $status
     * @param array  $headers
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function _redirect($uri, $status = 302, $headers = array())
    {
        return new RedirectResponse($uri, $status, $headers);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return ControllerAbstract
     */
    public function setResponse(Response $response)
    {
        $this->_response = $response;
        return $this;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @return ControllerAbstract
     */
    public function setContainer(ContainerBuilder $container)
    {
        $this->_container = $container;
        return $this;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    public function getContainer()
    {
        return $this->_container;
    }
}