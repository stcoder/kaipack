<?php

namespace Kaipack\Component\View;

use Kaipack\Core\Component\ComponentAbstract;

class ViewManager extends ComponentAbstract
{
    /**
     * @var \Twig_Environment
     */
    protected $_twig;

    /**
     * @var string
     */
    protected $_template;

    /**
     * @var array
     */
    protected $_variables = [];

    /**
     * Загрузчик менеджера вида.
     *
     * @throws \Exception
     */
    public function boot()
    {
        $cm = $this->getComponentManager();

        // Установить полный путь к шаблонам проекта.
        $templatesRealDir = $cm->getParam('base-dir') . $cm->getParam('view.template-dir');

        if (!is_dir($templatesRealDir) && !is_readable($templatesRealDir)) {
            throw new \Exception(sprintf(
                'Каталог шаблонов "%s" не найден или он не доступен для чтения',
                $templatesRealDir
            ));
        }

        // Переопределяем параметр директории шаблонов.
        $cm->setParam('view.template-dir', $templatesRealDir);

        // Устанавливаем полный путь к кэшу шаблонов.
        $templatesCacheDir = $cm->getParam('base-dir') . $cm->getParam('view.options.cache');

        if (!is_dir($templatesCacheDir) && !is_readable($templatesCacheDir)) {
            throw new \Exception(sprintf(
                'Каталог кэша шаблонов "%s" не найден или он не доступен для чтения',
                $templatesCacheDir
            ));
        }

        // Переопределяем параметр директорию кэша шаблонов.
        $cm->setParam('view.options.cache', $templatesCacheDir);

        $this->_twig = $cm->get('view.twig-environment');

        // Регистрируем слушателей.
        $em = $cm->get('event-manager');
        $em->attach(new Listener\View());
    }


    /**
     * @param string $templateName
     * @return ViewManager
     */
    public function setTemplate($templateName)
    {
        $this->_template = $templateName . '.twig';
        return $this;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->_template;
    }

    /**
     * @param array $variables
     * @return ViewManager
     */
    public function setVariables(array $variables)
    {
        $this->_variables = $variables;
        return $this;
    }

    /**
     * @return array
     */
    public function getVariables()
    {
        return $this->_variables;
    }

    /**
     * @param $key
     * @param $value
     * @return ViewManager
     */
    public function assign($key, $value)
    {
        $this->_variables[$key] = $value;
        return $this;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function getVar($key)
    {
        return (isset($this->_variables[$key])) ? $this->_variables[$key] : null;
    }

    public function render()
    {
        $template = $this->_twig->loadTemplate($this->getTemplate());
        return $template->render($this->getVariables());
    }
}