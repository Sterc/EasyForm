<?php

namespace Sterc\EasyForm;

use Sterc\Validator\ValidatorFactory;
use Sterc\EasyForm;

class Form
{
    public $easyForm;

    public $modx;

    protected $request;

    protected $plugins = [];

    protected $properties = [];

    protected $validationMessages = [];

    public $validator;

    public function __construct(EasyForm $easyForm, array $validationMessages = [])
    {
        $this->easyForm           = $easyForm;
        $this->modx               = $easyForm->modx;
        $this->request            = new Request();
        $this->validationMessages = $validationMessages;
        
        $this->validator = (new ValidatorFactory($this->validationMessages))->make([], []);
    }

    public function getProperty($key, $default = '')
    {
        if (!empty($this->easyForm->properties[$key])) {
            return $this->easyForm->properties[$key];
        }

        return $default;
    }

    protected function getRules()
    {
        $rules = !empty($this->easyForm->properties['rules']) ? $this->easyForm->properties['rules'] : [];

        if (!empty($this->easyForm->getProperty('rulePaths'))) {
            $classes = $this->collectClasses($this->getProperty('rulePaths'), 'Illuminate\Contracts\Validation\Rule');

            /* Loop through rules and replace strings which should actually be classes. For example: ['rules' => ['name' => 'new Sterc\Rules\TestRule']]. */
            foreach ($rules as $field => $fieldRules) {
                if (is_array($fieldRules)) {
                    foreach ($fieldRules as $key => $rule) {
                        if (substr($rule, 0, 4) === 'new ') {
                            $fullClassName = str_replace('new ', '', $rule);
                        
                            $index = array_search($fullClassName, $classes, true);
                            if ($index !== false) {
                                $rules[$field][$key] = new $classes[$index]();
                            }
                        }
                    }
                } else {
                    if (substr($fieldRules, 0, 4) === 'new ') {
                        $fullClassName = str_replace('new ', '', $fieldRules);

                        $index = array_search($fullClassName, $classes, true);
                        if ($index !== false) {
                            $rules[$field] = new $classes[$index]();
                        }
                    }
                }
            }
        }

        return $rules;
    }

    public function getValues()
    {
        return $this->request->all();
    }

    /**
     * Get specific field value.
     */
    public function getValue($key = '')
    {
        return $this->request->get($key);
    }

    
    public function isSelected($key, $value)
    {
        $postedValue = $this->getValue($key);
        
        if (is_array($postedValue)) {
            return in_array($value, $postedValue, false);
        }
        
        return $postedValue == $value;
    }

    public function isChecked($key, $value)
    {
        $postedValue = $this->getValue($key);
        
        if (is_array($postedValue)) {
            return in_array($value, $postedValue, false);
        }
        
        return $postedValue == $value;
    }

    /**
     * Set specific field value.
     */
    public function setValue($key, $value = '')
    {
        $this->request->set($key, $value);
    }

    /**
     * @todo Add option for setting submit var.
     */
    public function isSubmitted()
    {
        return empty($this->getProperty('submitVar')) ? !empty($_POST) : !empty($_POST[$this->getProperty('submitVar')]);
    }

    public function validate()
    {
        $this->validator = (new ValidatorFactory($this->validationMessages))->make(
            $this->request->all(),
            $this->getRules(),
            $this->getProperty('messages', [])
        );
        
        if (!$this->hasErrors()) {
            $this->invokeEvent('onFormSubmitted');
        }
    }

    public function invokeEvent($event)
    {
        foreach ($this->getPlugins() as $plugin) {
            $shortPluginName  = (new \ReflectionClass($plugin))->getShortName();
            $pluginProperties = [];

            if (!empty($this->getProperty('plugins')[$shortPluginName])) {
                $pluginProperties = $this->getProperty('plugins')[$shortPluginName];
            }

            if (method_exists($plugin, $event)) {
                /* If event returns false, stop looping through all plugins. */
                if (!$plugin->$event($pluginProperties)) {
                    break;
                }
            }
        }
    }

    /**
     * @return bool Return if form submission contains errors.
     */
    public function hasErrors()
    {
        return $this->validator->errors()->any() ?? false;
    }

    /**
     * @param string $key
     * 
     * @return string|array Returns error message(s).
     */
    public function getErrors($key = '')
    {
        if (empty($key)) {
            return $this->validator->errors()->toArray();
        }
        
        if ($this->validator->errors()->get($key)) {
            return $this->validator->errors()->get($key);
        }

        return '';
    }

    public function addError($key, $value = '')
    {
        $this->validator->errors()->add($key, $value);
    }

    public function getError($key = '')
    {
        if ($errors = $this->getErrors($key)) {
            return implode(' ', $errors);
        }

        return '';
    }

    /**
     * Return plugins.
     */
    protected function getPlugins()
    {
        if (count($this->plugins) === 0 && !empty($this->getProperty('plugins'))) {
            $this->setPlugins();
        }

        return $this->plugins;
    }

    /**
     * Set plugins.
     */
    protected function setPlugins()
    {
        if (count($this->getProperty('plugins', [])) > 0) {
            $paths = [__DIR__ . '/Plugins/'];

            if (!empty($this->getProperty('pluginPaths'))) {
                if (is_array($this->getProperty('pluginPaths'))) {
                    foreach ($this->getProperty('pluginPaths') as $pluginPath) {
                        $paths[] = $this->easyForm->preparePath($pluginPath);
                    }
                } else {
                    $paths[] = $this->easyForm->preparePath($this->getProperty('pluginPaths'));
                }
            }

            $pluginClasses = $this->collectClasses($paths, 'Sterc\EasyForm\Plugins\BasePlugin', $this->getProperty('plugins'));
            foreach ($pluginClasses as $pluginClass) {
                $this->plugins[] = new $pluginClass($this);
            }
        }
    }

    /**
     * Return list of classes.
     * @param array $searchPaths List of paths to search in.
     * @param string $baseClass Base call the returned classes must extend.
     * @param array $classesToFind Specific list of classes to return.
     * 
     * @return [type]
     */
    protected function collectClasses(array $searchPaths = [], string $baseClass = '', array $classesToFind = [])
    {
        $paths = [];

        foreach ($searchPaths as $searchPath) {
            $paths[] = $this->easyForm->preparePath($searchPath);
        }
       
        /* Loop through found classes and include them. */
        foreach ($paths as $path) {
            foreach (glob(rtrim($path, '/') . '/*.php') as $filename) {
                include_once $filename; 
            }
        }
        
        $classes = [];
        foreach (get_declared_classes() as $class) {
            $className = end(explode('\\', $class));

            if (is_subclass_of($class, $baseClass)) {
                if (count($classesToFind) > 0) {
                    if (array_key_exists($className, $classesToFind))  {
                        $classes[] = $class;
                    }
                } else {
                    $classes[] = $class;
                }
            }
        }

        return $classes;
    }
}