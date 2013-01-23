<?php

$baseMemory = memory_get_usage();
require __DIR__ . '/vendor/autoload.php';

$loader = new \Composer\Autoload\ClassLoader();

$loader->add('Kaipack', __DIR__ . '/system');

$loader->register();
$loader->setUseIncludePath(true);

$engine = new Kaipack\Core\Engine(__DIR__ . '/config/project.config.json');
$engine->setClassLoader($loader);
$engine->run();