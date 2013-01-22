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
		$cm = $this->getComponentManager();

		// Установить полный путь к модулям проекта.
		$modulesRealDir = $cm->getParam('base-dir') . $cm->getParam('module.module-dir');

		if (!is_readable($modulesRealDir)) {
			throw new \Exception(sprintf(
				'Каталог модулей "%s" не найден или он не доступен для чтения',
				$modulesRealDir
			));
		}

		// Переопределяем параметр директории модулей.
		$cm->setParam('module.module-dir', $modulesRealDir);

		// Добавляем префикс и директорию модулей в загрузчик классов.
		$classLoader = $cm->get('class-loader');
		$classLoader->add('module', $cm->getParam('module.module-dir'));

		$cache  = $cm->get('cache.filesystem');
		$config = $cm->get('config');

		if (!$cache->hasItem('modules-loaded')) {
			$model   = $cm->get('database.database-manager')->getModel('kaipack/module');
			$modules = $model->getActivatedModules();

			foreach($modules as $module) {
				$moduleName = ucfirst($module->name);
				$moduleClass = sprintf(
					'\\module\\%s\\%sModule',
					$moduleName,
					$moduleName
				);

				$moduleDir = sprintf('%s/module/%s', $modulesRealDir, $moduleName);

				// Файл конфигурации.
				$configFile = $moduleDir . '/config.json';
				if (is_file($configFile)) {
					$configDefinition = $cm->getDefinitionComponent('config.factory');
					$configDefinition->setArguments(array(
						$configFile
					));

					$moduleConfig = $cm->getInstance('config.factory');

					// Установка маршрутов.
					if (isset($moduleConfig->router->routes) && !empty($moduleConfig->router->routes)) {
						$router = $cm->get('http.router');
						foreach($moduleConfig->router->routes as $routeName => $routeOptions) {
							$route = new \Kaipack\Component\Http\Router\Route($routeName, $routeOptions);
							$route->setModule($module->name);
							$router->addRoute($route);
						}
					}
				}

				$this->_modules[$module->name] = array(
					'class' => $moduleClass,
					'dir'   => $moduleDir
				);
			}

			if (isset($config->application->debug) && $config->application->debug === false) {
				$cache->setItem('modules-loaded', serialize($this->_modules));
			}
		} else {
			$this->_modules = unserialize($cache->getItem('modules-loaded'));
		}

		// Регистрируем слушателей.
		$em = $cm->get('event-manager');
		$em->attach(new Listener\ModuleDispatch());
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