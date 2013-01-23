<?php

namespace Kaipack\Component\Module\Listener;

use Kaipack\Core\EngineEvent;
use Kaipack\Component\Http\Dispatcher;
use Kaipack\Component\Http\DispatcherEvent;
use Kaipack\Component\Module\ModuleAbstract;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

class ModuleListener implements ListenerAggregateInterface
{
    /**
     * @var array
     */
    protected $_handlers = [];

    /**
     * @param EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events)
    {
        $this->_handlers[] = $events->attach(EngineEvent::ENGINE_BOOTSTRAP, array($this, 'onBoot'));
        $this->_handlers[] = $events->attach(DispatcherEvent::EVENT_DISPATCH, array($this, 'onDispatch'), -50);
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

    public function onBoot(EngineEvent $e)
    {
        $target  = $e->getTarget();
        $mm      = $target->getContainer()->get('module.module-manager');
        $cache   = $target->getStorageComponents();
        $modules = [];

        // Если дебаг режим выключен и есть запись в кэше.
        if (!$target->isDebug() && $cache->hasItem('modules')) {
            $modules = unserialize($cache->getItem('modules'));
        }

        if (empty($modules)) {
            $model            = $target->getContainer()->get('database.database-manager')->getModel('kaipack/module');
            $modulesActivated = $model->getActivatedModules();

            foreach($modulesActivated as $module) {
                $moduleName = ucfirst($module->name);
                $moduleClass = sprintf(
                    '\\module\\%s\\%sModule',
                    $moduleName,
                    $moduleName
                );

                $moduleInstance = new $moduleClass;

                if (!($moduleInstance instanceof ModuleAbstract)) {
                    throw new \DomainException(sprintf(
                        'Модуль %s недействителен. Должен расширять Kaipack\Component\Module\ModuleAbstract',
                        $module->name
                    ));
                }

                $modulePath = strtr($moduleClass, array('\\' => '/'));
                $modulePath = substr($modulePath, 0, strrpos($modulePath, '/'));
                $modulePath = $target->getContainer()->getParameter('module.module-dir') . $modulePath;

                $moduleLocalConfig = $modulePath . '/config.json';
                if (is_file($moduleLocalConfig)) {
                    $moduleInstance->setLocalConfig($moduleLocalConfig);
                }

                $moduleInstance->setConfig($target->getConfig());
                $moduleInstance->boot();
                $modules[$module->name] = $moduleInstance;
            }

            if (!$target->isDebug() && $cache->hasItem('modules')) {
                $cache->setItem('modules', serialize($modules));
            }
        }

        $mm->setModules($modules);
    }

    public function onDispatch(DispatcherEvent $e)
    {
        $moduleManager = $e->getTarget()->getContainer()->get('module.module-manager');
        $router        = $e->getRoute();

        try {
            if (!($module = $moduleManager->getModule($router->getModule()))) {
                $e->setError(Dispatcher::ERROR_MODULE_INVALID);
                throw new \Exception(sprintf(
                    'Запрашиваемый модуль "%s" не инициализирован в системе',
                    $router->getModule()
                ));
            }

            $module->setContainer($e->getTarget()->getContainer());
            $module->setEvent($e);
            $module->setEventManager($e->getTarget()->getContainer()->get('event-manager'));
            $result = $module->dispatch();

            if ($result instanceof \Symfony\Component\HttpFoundation\Response) {
                $e->setResponse($result);
                return $result;
            }
        } catch(\Exception $exception) {
            $e->setParam('exception', $exception);
            $results = $e->getTarget()->getContainer()->get('event-manager')->trigger(DispatcherEvent::EVENT_DISPATCH_ERROR, $e);
            if (count($results)) {
                $return  = $results->last();
            } else {
                $return = $e->getParams();
            }
            return $return;
        }
    }

    public function Dispatch(DispatcherEvent $e)
    {
        $route = $e->getParam('route');
        $em    = $e->getComponentManager()->get('event-manager');
        $mm    = $e->getComponentManager()->get('module.module-manager');

        $moduleOptions = $mm->getModule($route->getModule());

        // Если модуль не найден. Показываем страницу 404.
        if (is_null($moduleOptions)) {
            $e->setError(404);
            $em->trigger(DispatcherEvent::EVENT_DISPATCH_ERROR, $e);
            return;
        }

        $module = new $moduleOptions['class'];

        // Модуль должен расширять абстрактный класс ModuleAbstract.
        // Если это не так то, показываем страницу 404.
        if (!($module instanceof ModuleAbstract)) {
            $e->setError(404);
            $em->trigger(DispatcherEvent::EVENT_DISPATCH_ERROR, $e);
            return;
        }

        // Передаем модулю объект менеджера компонентов.
        $module->setComponentManager($e->getComponentManager());

        // Выполняем загрузку модуля.
        $module->boot();

        $result = $module->dispatch();

        if ($result instanceof \Symfony\Component\HttpFoundation\Response) {
            return $result;
        }

        $e->setResult($result);
        return $result;
    }
}