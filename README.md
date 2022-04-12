# EasyForm
EasyForm is a MODX extra which provides basic form handling functionalities using `Illuminate Validation` to handle the form validation which provides lots of default validation rules and you can easily provide custom error messages or create your own custom validator rules.

The Fenom parser provided by pdoTools is required in order to work with EasyForm.

More information about validation and all the, by default, built in validation rules please read the [validation section from Laravel](https://laravel.com/docs/9.x/validation). 

- [EasyForm](#easyform)
  - [Example usage](#example-usage)
  - [Add your own plugins](#add-your-own-plugins)
  - [Add your own validation rules](#add-your-own-validation-rules)
  - [Development](#development)
    - [Lang file conversion](#lang-file-conversion)

## Example usage
Please view this extensive example for all possible properties and usage:

```
{set $form = '!EasyForm' | snippet : [
    'pluginPaths' => [
        '{base_path}/customplugins/'
    ],
    'rulePaths' => [
        '{base_path}/customrules/'
    ],
    'plugins' => [
        'Email' => [
            'tpl'             => '@FILE email.tpl',
            'to'              => ['email' => 'john@domain.tld', 'name' => 'John Doe To'],
            'from'            => ['email' => 'john+from@domain.tld', 'name' => 'John Doe'],
            'reply-to'        => ['email' => 'john+reply@domain.tld', 'name' => 'John Doe Reply'],
            'cc'              => [
                ['email' => 'john+cc@domain.tld', 'name' => 'John Doe CC'],
                ['email' => 'john+cc2@domain.tld', 'name' => 'John Doe CC2'],
                ['email' => 'john+cc3@domain.tld']
            ],
            'bcc'             => ['email' => 'john+bcc@domain.tld', 'name' => 'John Doe BCC'], 
            'html'            => true,
            'convertNewlines' => true,
            'subject'         => 'My subject - [[+name]] {$name}',
            '_subjectField'   => 'topic',
            'multiWrapper'    => 'Before {$values} After',
            'multiSeparator'  => ', ',
            'selectEmailToAddresses' => [
                ['email' => 'john+receiver1@domain.tld', 'name' => 'John Doe Receiver 1'],
                ['email' => 'john+receiver2@domain.tld', 'name' => 'John Doe Receiver 2'],
            ]
            'selectEmailToField'     => 'receiver',
            'attachments' => [
                '{base_path}/uploads/fallback.png',
                '{base_path}/uploads/background.jpg'
            ],
            'attachFilesToEmail' => true
        ],
        'AutoResponder' => [
            'tpl'                 => '@FILE email.tpl',
            'to'                  => ['email' => 'john@domain.tld', 'name' => 'John Doe To'],
            'from'                => ['email' => 'john+from@domain.tld', 'name' => 'John Doe'],
            'subject'             => 'Autoresponder - [[+name]] {$name}',
            'attachFilesToEmail'  => true
        ],
        'Redirect' => [
            'to'     => 'https://www.google.com?',
            'scheme' => 'full',
            'params' => [
                'test'    => 1,
                'success' => 'value'
            ]
        ]
    ],
    'submitVar'     => 'submitvar',
    'rules'         => [
        'name'  => ['required', 'new App\Rules\Uppercase'],
        'name2'  =>['required', 'same:name'],
        'topic' => ['required'],
        'email' => ['required', 'email']
    ],
    'messages' => [
        'required'      => 'This is a mandatory message field that overrides the default message.',
        'name.required' => '":attribute" is a required field with a custom error message.'
    ]
]}

    <form action="{$_modx->resource.id | url}" method="post" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="submitvar" value="submitted"/>

        <div class="input-group mb-3">
            <input type="text" class="form-control {$form->getError('name') ? 'is-invalid' : ''}" name="name" placeholder="Name" value="{$form->getValue('name')}"/>
            
            {if $form->getError('name')}
                <div class="invalid-feedback">{$form->getError('name')}</div>
            {/if}
        </div>

            <div class="input-group mb-3">
            <input type="text" class="form-control {$form->getError('name2') ? 'is-invalid' : ''}" name="name2" placeholder="Name2" value="{$form->getValue('name2')}"/>
            
            {if $form->getError('name2')}
                <div class="invalid-feedback">{$form->getError('name2')}</div>
            {/if}
        </div>

        <div class="input-group mb-3">
            <select class="form-control {$form->getError('topic') ? 'is-invalid' : ''}"  name="topic" placeholder="Topic">
                <option value=""></option>
                <option value="General" {$form->getValue('topic') === 'General' ? 'selected' : ''}>General</option>
                <option value="Sales" {$form->getValue('topic') === 'Sales' ? 'selected' : ''}>Sales</option>
            </select>
            
            {if $form->getError('topic')}
                <div class="invalid-feedback">{$form->getError('topic')}</div>
            {/if}
        </div>

        <div class="input-group mb-3">
            <select class="form-control {$form->getError('receiver') ? 'is-invalid' : ''}"  name="receiver" placeholder="Receiver">
                <option value=""></option>
                <option value="1" {$form->getValue('receiver') === 'sander+select1@sterc.nl' ? 'selected' : ''}>sander+select1@sterc.nl</option>
                <option value="2" {$form->getValue('receiver') === 'sander+select2@sterc.nl' ? 'selected' : ''}>sander+select2@sterc.nl</option>
            </select>
            
            {if $form->getError('receiver')}
                <div class="invalid-feedback">{$form->getError('receiver')}</div>
            {/if}
        </div>

        <div class="input-group mb-3">
            <select class="form-control {$form->getError('brands') ? 'is-invalid' : ''}"  name="brands[]" placeholder="Brands" multiple>
                <option value=""></option>
                <option value="Audi" {$form->isSelected('brands', 'Audi') ? 'selected' : ''}>Audi</option>
                <option value="BMW" {$form->isSelected('brands', 'BMW') ? 'selected' : ''}>BMW</option>
            </select>
            
            {if $form->getError('topic')}
                <div class="invalid-feedback">{$form->getError('topic')}</div>
            {/if}
        </div>

        <div class="input-group mb-3">
                <div class="form-check">
                <input class="form-check-input" name="interests[]" type="checkbox" value="Cars" id="cars" {$form->isSelected('interests', 'Cars') ? 'checked' : ''}>
                <label class="form-check-label" for="cars">
                Cars
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" name="interests[]" type="checkbox" value="Games" id="games" {$form->isSelected('interests', 'Games') ? 'checked' : ''}>
                <label class="form-check-label" for="games">
                Games
                </label>
            </div>
        </div>

        <input type="file" id="testfile" name="filename">

        <div class="input-group mb-3">
            <input type="email" class="form-control {$form->getError('email') ? 'is-invalid' : ''}" name="email" placeholder="Email" value="{$form->getValue('email')}"/>
            
            {if $form->getError('email')}
                <div class="invalid-feedback">{$form->getError('email')}</div>
            {/if}
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>

    {if $form->isSubmitted() && $form->hasErrors()}
        <p>Form has errors.</p>
    {/if}
```

## Add your own plugins
Create a plugin class that extends the BasePlugin class and make sure it's loaded by the snippet by adding the path to the scriptproperties.

```
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
```

```php
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

## Add your own validation rules
Adding your own custom validation rules can be done by creating a new class which implements the `Rule` class:

```php
<?php
namespace App\Rules;
 
use Illuminate\Contracts\Validation\Rule;
 
class Uppercase implements Rule
{
      /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return strtoupper($value) === $value;
    }
 
    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be uppercase.';
    }
}
```

Then add the path where your rule class lives into the rulePaths array and the rule to the field you'd like to validate by defining the fully classified class name as a string, like so: `'new App\Rules\Uppercase'`.

```
{set $form = '!EasyForm' | snippet : [
    'rulePaths' => [
        '{base_path}/customrules/',
    ',
    'rules' => [
        'name'  => ['required', 'new App\Rules\Uppercase'],
    ]
],
```

## Development

### Lang file conversion
Script to convert laravel-lang validation messages to MODX lexicon file

$array contains the laravel-lang validation messages array from https://github.com/overtrue/laravel-lang.

```php
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