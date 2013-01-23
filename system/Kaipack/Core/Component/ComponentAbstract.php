<?php

namespace Kaipack\Core\Component;

use Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class ComponentAbstract
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected $_container;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @return ComponentAbstract
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

    abstract public function boot();
}