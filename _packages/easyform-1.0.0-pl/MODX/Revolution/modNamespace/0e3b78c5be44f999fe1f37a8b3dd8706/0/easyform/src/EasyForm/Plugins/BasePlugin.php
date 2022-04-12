<?php

namespace Sterc\EasyForm\Plugins;

class BasePlugin
{
    protected $form;

    public function __construct($form)
    {
        $this->form = $form;
    }
}