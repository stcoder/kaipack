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
        $container = $this->getContainer();

        // Устанавливаем путь к шаблону.
        $templatesDir = $container->getParameter('base-dir') . $container->getParameter('view.template-dir');
        if (!is_readable($templatesDir)) {
            throw new \Exception(sprintf(
                'Каталог шаблонов "%s" не найден или он не доступен для чтения',
                $templatesDir
            ));
        }

        $container->setParameter('view.template-dir', $templatesDir);

        // Устанавливаем путь к кэшу шаблонов.
        $templatesCacheDir = $container->getParameter('base-dir') . $container->getParameter('view.options.cache');

        if (!is_readable($templatesCacheDir)) {
            throw new \Exception(sprintf(
                'Каталог кэша шаблонов "%s" не найден или он не доступен для чтения',
                $templatesCacheDir
            ));
        }

        // Переопределяем параметр директорию кэша шаблонов.
        $container->setParameter('view.options.cache', $templatesCacheDir);

        $this->_twig = $container->get('view.twig-environment');

        // Регистрируем слушателей.
        $e  = $container->get('event-dispatcher');
        $e->setView($this);
        $em = $container->get('event-manager');
        $em->attach(new Listener\ViewListener());
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
    public function setVariable($key, $value)
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