<?php

namespace Kaipack\Component\Module;

use Kaipack\Component\Module\Controller\ControllerAbstract;
use Kaipack\Component\Http\DispatcherEvent;
use Kaipack\Core\Config;
use Zend\EventManager\EventManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class ModuleAbstract
{
    /**
     * @var \Kaipack\Core\Config
     */
    protected $_localConfig;

    /**
     * @var \Kaipack\Core\Config
     */
    protected $_config;

    /**
     * @var \Kaipack\Component\Http\DispatcherEvent
     */
    protected $_event;

    /**
     * @var \Zend\EventManager\EventManager
     */
    protected $_eventManager;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected $_container;

    /**
     * @param \Kaipack\Core\Config $config
     * @return ModuleAbstract
     */
    public function setConfig(Config $config)
    {
        $this->_config;
        return $this;
    }

    /**
     * @return \Kaipack\Core\Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * @param string $config
     * @return ModuleAbstract
     */
    public function setLocalConfig($config)
    {
        $this->_localConfig = new Config($config);
        return $this;
    }

    /**
     * @return \Kaipack\Core\Config
     */
    public function getLocalConfig()
    {
        return $this->_localConfig;
    }

    /**
     * @param \Kaipack\Component\Http\DispatcherEvent $event
     * @return ModuleAbstract
     */
    public function setEvent(DispatcherEvent $event)
    {
        $this->_event = $event;
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
     * @return ModuleAbstract
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
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @return ModuleAbstract
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

    /**
     * Загрузчик модуля.
     *
     * @return void
     */
    public function boot()
    {
        // функционал загрузки определить в модуле.
    }

    public function dispatch()
    {
        $route     = $this->getEvent()->getRoute();
        $namespace = get_class($this);
        $namespace = lcfirst(substr($namespace, 0, strrpos($namespace, '\\')));
        $controllerClass = sprintf(
            '%s\\controller\\%sController',
            $namespace,
            $this->_normalize($route->getController())
        );

        $controller = new $controllerClass;

        if ($controller instanceof ControllerAbstract) {
            $controller->setContainer($this->getContainer())
                ->setEvent($this->getEvent())
                ->setEventManager($this->getEventManager())
                ->setResponse($this->getEvent()->getResponse())
                ->setRequest($this->getEvent()->getRequest());
            $controller->init();
        }

        $action = lcfirst($this->_normalize($route->getAction())) . 'Action';
        if (!method_exists($controller, $action)) {
            $this->getEvent()->setError(\Kaipack\Component\Http\Dispatcher::ERROR_CONTROLLER_NOT_FOUND);
            throw new \Exception(sprintf(
                'Был запрошен неизвестный метод (%s). Модуль "%s" контроллер "%s"',
                $action,
                get_class($this),
                get_class($controller)
            ));
        }

        $arguments = [];
        foreach($route->getVariables() as $var) {
            $arguments[] = $var['value'];
        }

        // Выполняем и получаем результат действия.
        $actionResult = call_user_func_array(array($controller, $action), $arguments);

        // Если результатом выполненного метода является объект Response.
        // Возвращаем его.
        if ($actionResult instanceof \Symfony\Component\HttpFoundation\Response) {
            return $actionResult;
        }

        $this->getEvent()->setResult($actionResult);
        return $actionResult;
    }

    protected function _normalize($string)
    {
        $string = strtr($string, array('.' => ' '));
        $string = ucwords($string);
        $string = strtr($string, array(' ' => ''));
        return $string;
    }
}