<?php

namespace Kaipack\Core;

use Zend\Config\Config as ZendConfig;

class Config extends ZendConfig
{
	public function __construct($filename)
	{
		if (!is_file($filename) || !is_readable($filename)) {
            throw new Exception\RuntimeException(sprintf(
                "File '%s' doesn't exist or not readable",
                $filename
            ));
        }

        $config = json_decode(file_get_contents($filename), true);

        parent::__construct($config, true);
	}
}