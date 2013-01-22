<?php

namespace Module\Main\Controller;

class IndexController
{
    public function homeAction()
    {
        return array(
            'title' => 'Kaipack hello!'
        );
    }
}