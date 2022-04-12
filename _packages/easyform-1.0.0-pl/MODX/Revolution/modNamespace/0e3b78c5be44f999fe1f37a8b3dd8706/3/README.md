# EasyForm
EasyForm is a MODX extra which provides basic form handling functionalities. This extra uses Illuminate Validation to handle the form validation which provides lots of default validation rules and you can easily provide custom error messages or create your own custom validator rules.

The Fenom parser provided by pdoTools is required in order to work with EasyForm.

More information about validation and all the, by default, built in validation rules please read the [validation section from Laravel](https://laravel.com/docs/9.x/validation). 

- [EasyForm](#easyform)
  - [Add your own plugins](#add-your-own-plugins)
    - [Add your own validation rules](#add-your-own-validation-rules)
    - [Script to convert laravel-lang validation messages to MODX lexicon file](#script-to-convert-laravel-lang-validation-messages-to-modx-lexicon-file)

## Add your own plugins
Create a plugin class that extends the BasePlugin class and make sure it's loaded by the snippet by adding the path to the scriptproperties.
```
# Snippet call
{set $form = '!EasyForm' | snippet : [
    'pluginPaths' => [
        '{base_path}/customplugins/',
        '{core_path}/customplugins/',
        '{assets_path}/customplugins/'
    ],
    'plugins' => [
        'Redirect' => [
            'to' => 2
        ],
        'SomePlugin' => []
    ],
    'submitVar'     => 'submitvar',
    'rules'         => [
        'name'  => ['required'],
        'email' => ['required', 'email']
    ]
]}

# SomePlugin example
<?php

use Sterc\EasyForm\Plugins\BasePlugin;

class SomePlugin extends BasePlugin
{
    public function onInitForm($form, $properties)
    {
        $form->request->set('name', 'some value');

        return true;
    }
}
```

### Add your own validation rules
@TODO Describe this.

### Script to convert laravel-lang validation messages to MODX lexicon file
$array contains the laravel-lang validation messages array from https://github.com/overtrue/laravel-lang.

```
$output = [];
foreach ($array as $key => $value) {
    if (!is_array($value)) {
        $output[] = '$_lang[\'' . $key . '\'] = \'' . addslashes($value) . '\';';
    } else {
        foreach ($value as $childKey => $childValue) {
            $output[] = '$_lang[\'' . $key . ':' . $childKey . '\'] = \'' . addslashes($childValue) . '\';';
        }
    }
}

var_dump(implode('<br/>', $output));
exit;
```