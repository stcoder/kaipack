<?php

namespace Kaipack\Component\Http\Router;

class Route
{
	/**
	 * @var string
	 */
	protected $_name = '';

	/**
	 * @var string
	 */
	protected $_method = '';

	/**
	 * @var string
	 */
	protected $_uri = '';

	/**
	 * Скомпилированная строка.
	 *
	 * @var string
	 */
	protected $_regex = '';

	/**
	 * @var array
	 */
	protected $_variables = [];

	/**
	 * @var string
	 */
	protected $_module = '';

	/**
	 * @var string
	 */
	protected $_controller = '';

	/**
	 * @var string
	 */
	protected $_action = '';

	/**
	 * Доступные методы.
	 *
	 * @var array
	 */
	protected $_permitedMethods = ['get', 'post', 'head', 'put', 'delete'];

	/**
	 * @param string $name
	 * @param string $options
	 */
	public function __construct($name, $options = '')
	{
		$this->setName($name);

		if (!is_string($options)) {
			throw new \InvalidArgumentException(sprintf(
				'Не доступный формат опций для маршрута %s',
				$name
			));
		}

		$this->_parseOptions($options);
		$this->compile();
	}

	/**
	 * @param $uri
	 * @return bool
	 */
	public function isMatch($uri)
	{
		if (empty($this->_regex)) {
			$this->compile();
		}

		$uri = '/' . trim($uri, '/');

		if (preg_match('/^' . $this->_regex . '$/', $uri, $matches)) {
			foreach($matches as $varName => $varValue) {
				if (is_string($varName)) {
					$this->_variables[$varName]['value'] = $varValue;
				}
			}
			return true;
		}

		return false;
	}

	/**
	 * Парсит настройки маршрута.
	 *
	 * <code>
	 * 	$r = new Route('home', 'get / module@controller:action');
	 * </code>
	 *
	 * @param $stringOption
	 * @return Route
	 */
	protected function _parseOptions($stringOption)
	{
		$options = explode(' ', $stringOption);

		if (count($options) < 3) {
			throw new \DomainException('Не верно составлены параметры маршрута');
		}

		// Определяем метод.
		$method = $options[0];
		$this->setMethod((isset($this->_permitedMethods[$method])) ? $method : 'get');

		// Определяем uri.
		$this->setUri($options[1]);
		$this->_setVariablesFoundUri($this->getUri());

		// Определяем модуль.
		if (($modulePosition = strpos($options[2], '@'))) {
			$this->setModule(substr($options[2], 0, $modulePosition));
			$options[2] = substr($options[2], $modulePosition + 1);
		}

		// Определяем контролллер и действие.
		list($controller, $action) = explode(':', $options[2]);
		$this->setController($controller);
		$this->setAction($action);

		// Если были указаны переменные.
		$variables = array_slice($options, 3);
		if (!empty($variables)) {
			foreach($variables as $variable) {
				list($varName, $varValue) = explode('=', $variable);
				$this->assert($varName, $varValue);
			}
		}
		return $this;
	}

	/**
	 * Установить переменные найденные в строке.
	 *
	 * @param string $uri
	 * @return Route
	 */
	protected function _setVariablesFoundUri($uri)
	{
		// (@var_name)
		preg_match_all('#\(\@\w+\)#', $uri, $variables);

		if (!empty($variables)) {
			foreach($variables[0] as $variable) {
				$this->_variables[substr($variable, 2, -1)] = array('regex' => '\w+');
			}
		}

		return $this;
	}

	/**
	 * Скомпилировать шаблон URI
	 *
	 * @return Route
	 */
	public function compile()
	{
		$this->_regex = $this->_uri;

		if (!empty($this->_variables)) {
			foreach($this->_variables as $name => $options) {
				$regex = sprintf('(?P<%s>%s)', $name, $options['regex']);
				$this->_regex = strtr($this->_regex, array(
					sprintf('(@%s)', $name) => $regex
				));
			}
		}

		$this->_regex = strtr($this->_regex, array(
			'/' => '\/',
			'-' => '\-',
			'.' => '\.'
		));

		return $this;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param string $assert
	 * @return Route
	 */
	public function setVariable($key, $value, $assert = '\w+')
	{
		$this->_variables[$key] = array(
			'value' => $value,
			'regex' => $assert
		);

		return $this;
	}

	/**
	 * @param array $key
	 * @return null
	 */
	public function getVariable($key)
	{
		return (isset($this->_variables[$key])) ? $this->_variables[$key] : null;
	}

	/**
	 * @return array
	 */
	public function getVariables()
	{
		return $this->_variables;
	}

	/**
	 * Утвердить значение переменной
	 *
	 * <code>
	 * 	$home = ...
	 * 	$home->assert('id', '\d+'); // Переменная ID должно быть только числом
	 * </code>
	 *
	 * @param string $key
	 * @param string $regex
	 * @return Route
	 */
	public function assert($key, $regex)
	{
		if (!isset($this->_variables[$key])) {
			throw new \Exception(sprintf('Variable %s is not found', $key));
		}

		$this->_variables[$key]['regex'] = $regex;
		return $this;
	}

	/**
	 * @param $name
	 * @return Route
	 */
	public function setName($name)
	{
		$this->_name = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @param $uri
	 * @return Route
	 */
	public function setUri($uri)
	{

		$this->_uri = '/' . trim($uri, '/');
		return $this;
	}

	/**
	 * @return string
	 */
	public function getUri()
	{
		return $this->_uri;
	}

	/**
	 * @param $method
	 * @return Route
	 */
	public function setMethod($method)
	{
		$this->_method = $method;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getMethod()
	{
		return $this->_method;
	}

	/**
	 * @param $module
	 * @return Route
	 */
	public function setModule($module)
	{
		$this->_module = $module;
		if ($module !== 'main') {
			$this->setUri($module . $this->_uri);
			$this->compile();
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getModule()
	{
		return $this->_module;
	}

	/**
	 * @param $controller
	 * @return Route
	 */
	public function setController($controller)
	{
		$this->_controller = $controller;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getController()
	{
		return $this->_controller;
	}

	/**
	 * @param $action
	 * @return Route
	 */
	public function setAction($action)
	{
		$this->_action = $action;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getAction()
	{
		return $this->_action;
	}
}