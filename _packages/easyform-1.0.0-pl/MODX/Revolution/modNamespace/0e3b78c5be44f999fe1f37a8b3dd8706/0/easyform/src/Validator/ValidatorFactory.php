<?php
namespace Sterc\Validator;

use Illuminate\Validation;
use Illuminate\Translation;

class ValidatorFactory
{
    public $factory;
    protected $messages = [];
    
    /**
     * @param array $messages
     */
    public function __construct(array $messages = [])
    {
        $this->messages = $messages;
        $this->factory  = new Validation\Factory($this->loadTranslator());
    }

    /**
     * Load translator.
     * @todo Set dynamic language
     */
    protected function loadTranslator()
    {
        $loader = new Translation\ArrayLoader();
        $loader->addMessages('en', 'validation', $this->messages);

        return new Translation\Translator($loader, 'en');
    }

    /**
     * @param mixed $method
     * @param mixed $args
     */
    public function __call($method, $args)
    {
        return call_user_func_array(
            [$this->factory, $method],
            $args
        );
    }
}