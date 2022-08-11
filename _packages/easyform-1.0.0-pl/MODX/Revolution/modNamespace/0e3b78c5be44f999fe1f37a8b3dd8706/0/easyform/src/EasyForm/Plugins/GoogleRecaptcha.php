<?php

namespace Sterc\EasyForm\Plugins;

class Recaptcha extends BasePlugin
{
    /**
     * @todo Implement v2 variants & v3.
     * @todo On hold untill we know which recaptcha will be implemented.
     */
    public function onFormSubmitted($form, $properties)
    {
        return true;
    }
}
