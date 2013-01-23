<?php

namespace Kaipack\Component\Module;

use Kaipack\Core\Component\ComponentAbstract;

/**
 * Компонент менеджер модулей.
 */
class ModuleManager extends ComponentAbstract
{
    /**
     * Активные модули.
     *
     * @var array
     */
    protected $_modules = [];

    /**
     * @return void
     */
    public function boot()
    {
        $container = $this->getContainer();

        // Установить полный путь к модулям проекта.
        $modulesRealDir = $container->getParameter('base-dir') . $container->getParameter('module.module-dir');

        if (!is_readable($modulesRealDir)) {
            throw new \Exception(sprintf(
                'Каталог модулей "%s" не найден или он не доступен для чтения',
                $modulesRealDir
            ));
        }

        // Переопределяем параметр директории модулей.
        $container->setParameter('module.module-dir', $modulesRealDir);

        $classLoader = $container->get('class-loader');
        $classLoader->add('module', $container->getParameter('module.module-dir'));

        $em = $container->get('event-manager');
        $em->attach(new Listener\ModuleListener());
    }

    /**
     * @param  array $modules
     * @return ModuleManager
     */
    public function setModules($modules)
    {
        $this->_modules = $modules;
        return $this;
    }

    /**
     * @param string $module
     * @return bool
     */
    public function hasModule($module)
    {
        return isset($this->_modules[$module]);
    }

    /**
     * @param $module
     * @return null|array
     */
    public function getModule($module)
    {
        if (!$this->hasModule($module)) {
            return null;
        }

        return $this->_modules[$module];
    }
}