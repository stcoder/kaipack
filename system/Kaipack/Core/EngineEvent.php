<?php

/**
 * Kaipack
 * 
 * @package kaipack/core
 */
namespace Kaipack\Core;

use Zend\EventManager\Event;

/**
 * События движка системы.
 * 
 * @author Sergey Tihonov
 * @package kaipack/core
 * @version 1.1-a2
 */
class EngineEvent extends Event
{
    const ENGINE_BOOTSTRAP = 'engine.event.bootstrap';
    const ENGINE_START     = 'engine.event.start';
    const ENGINE_STOP      = 'engine.event.stop';
}