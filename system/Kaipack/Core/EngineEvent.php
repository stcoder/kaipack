<?php

namespace Kaipack\Core;

use Zend\EventManager\Event;

class EngineEvent extends Event
{
    const ENGINE_BOOTSTRAP = 'engine.event.bootstrap';
    const ENGINE_START     = 'engine.event.start';
    const ENGINE_STOP      = 'engine.event.stop';

    /**
     * @var Component\ComponentManager
     */
    protected $_componentManager;

    /**
     * @param Component\ComponentManager $componentManager
     * @return EngineEvent
     */
    public function setComponentManager(Component\ComponentManager $componentManager)
    {
        $this->_componentManager = $componentManager;
        return $this;
    }

    /**
     * @return Component\ComponentManager
     */
    public function getComponentManager()
    {
        return $this->_componentManager;
    }
}