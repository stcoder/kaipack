<?php

namespace Kaipack\Component\Database;

use Kaipack\Core\Component\ComponentAbstract;

class DatabaseManager extends ComponentAbstract
{
	/**
	 * @var \Zend\Db\Adapter\Adapter
	 */
	protected $_adapter;

	/**
	 * @var array
	 */
	protected $_models = [];

	public function boot()
	{
		$cm = $this->getComponentManager();

		// Установить полный путь к моделям.
		$modelsRealDir = $cm->getParam('base-dir') . $cm->getParam('database.model-dir');

		if (!is_dir($modelsRealDir) && !is_readable($modelsRealDir)) {
			throw new \Exception(sprintf(
				'Каталог моделуй "%s" не найден или он не доступен для чтения',
				$modelsRealDir
			));
		}

		// Переопределяем параметр директории моделей.
		$cm->setParam('database.model-dir', $modelsRealDir);

		$adapter = $this->_adapter = $this->getComponentManager()->get('database.db-adapter');

		// Подключаемся к базе данных.
		$adapter->driver->getConnection()->connect();

		// Добавляем префикс и директорию моделей в загрузчик классов.
		$classLoader = $cm->get('class-loader');
		$classLoader->add('model', $cm->getParam('database.model-dir'));
	}

	/**
	 * @param $modelName
	 * @return TableAbstract
	 * @throws \DomainException
	 */
	public function getModel($modelName)
	{
		if (isset($this->_models[$modelName])) {
			return $this->_models[$modelName];
		}

		$modelClass	= strtr($modelName, array('/' => ' '));
		$modelClass	= ucwords($modelClass);
		$modelClass = '\\model\\' . strtr($modelClass, array(' ' => '\\'));

		$model = new $modelClass($this->_adapter);

		if (!($model instanceof TableAbstract)) {
			throw new \DomainException(sprintf(
				'Модель %s недействительна. Должна расширять Kaipack\Component\Database\TableAbstract',
				$modelName
			));
		}

		$this->_models[$modelName] = $model;
		return $model;
	}
}