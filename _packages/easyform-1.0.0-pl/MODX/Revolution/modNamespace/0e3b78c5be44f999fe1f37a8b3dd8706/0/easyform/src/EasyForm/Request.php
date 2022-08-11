<?php

namespace Sterc\EasyForm;

class Request
{
    protected $fields = [];

    public function __construct()
    {
        $this->fields = $_POST + $_FILES;
    }

    public function all()
    {
        return $this->fields;
    }

    public function get($key = '')
    {
        return $this->fields[$key] ?: '';
    }

    /**
     * @todo Test if this works from a hook
     */
    public function set($key, $value)
    {
        $this->fields[$key] = $value;
    }
}
