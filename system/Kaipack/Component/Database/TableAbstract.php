<?php

namespace Kaipack\Component\Database;

use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\TableGateway;

abstract class TableAbstract extends TableGateway
{
    /**
     * Имя таблицы.
     *
     * @var string
     */
    protected $_name;

    public function __construct(Adapter $adapter)
    {
        parent::__construct($this->_name, $adapter);
    }
}