<?php

namespace Module\Admin\Controller;
use Kaipack\Component\Module\Controller\ControllerAbstract;

class IndexController extends ControllerAbstract
{
    public function homeAction()
    {
        return array(
            'message' => 'hello wworld'
        );
    }
}