<?php

namespace Kaipack\Core\Component;

abstract class ComponentAbstract
{
    /**
     * @var ComponentManager
     */
    protected $_componentManager;

    /**
     * @param ComponentManager $cm
     * @return ComponentAbstract
     */
    public function setComponentManager(ComponentManager $cm)
    {
        $this->_componentManager = $cm;
        return $this;
    }

    /**
     * @return ComponentManager
     */
    public function getComponentManager()
    {
        return $this->_componentManager;
    }
}