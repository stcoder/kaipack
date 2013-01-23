<?php

/**
 * Kaipack
 * 
 * @package kaipack/core
 */
namespace Kaipack\Core;

use Zend\Config\Config as ZendConfig;

/**
 * Конфиг, расширяет зендовский конфиг.
 * 
 * @author Sergey Tihonov
 * @package kaipack/core
 * @version 1.1-a2
 */
class Config extends ZendConfig
{
    /**
     * Передаем файл конфига в формате json.
     * 
     * @param string $filename
     */
    public function __construct($filename)
    {
        if (!is_readable($filename)) {
            throw new Exception\RuntimeException(sprintf(
                "Файл '%s' не существует или недоступен для чтения",
                $filename
            ));
        }

        // Получаем содержимое json файла и декодируем в массив.
        $config = json_decode(file_get_contents($filename), true);

        parent::__construct($config, true);
    }
}