<?php

namespace Sterc;

use MODx;
use Sterc\EasyForm\Form;

class EasyForm
{
    /**
     * @access public.
     * @var modX.
     */
    public $modx;

    /**
     * Holds the pdoFetch object.
     *
     * @var pdoFetch
     */
    protected $pdoFetch;

    /**
     * @access public.
     * @var Array.
     */
    public $properties = [];

    /**
     * @access public.
     * @param modX $modx.
     * @param Array $properties.
     */
    public function __construct(modX &$modx, array $properties = [])
    {
        $this->modx =& $modx;

        $corePath   = $this->modx->getOption('easyform.core_path', null, $this->modx->getOption('core_path') . 'components/easyform/');
        $assetsUrl  = $this->modx->getOption('easyform.assets_url', null, $this->modx->getOption('assets_url') . 'components/easyform/');
        $assetsPath = $this->modx->getOption('easyform.assets_path', null, $this->modx->getOption('assets_path') . 'components/easyform/');

        $this->properties = $properties;
        $this->config = [
            'namespace'                  => 'easyform',
            'lexicons'                   => ['easyform:mgr', 'easyform:default', 'easyform:validation'],
            'base_path'                  => $corePath,
            'core_path'                  => $corePath,
            'model_path'                 => $corePath . 'model/',
            'processors_path'            => $corePath . 'processors/',
            'elements_path'              => $corePath . 'elements/',
            'chunks_path'                => $corePath . 'elements/chunks/',
            'plugins_path'               => $corePath . 'elements/plugins/',
            'snippets_path'              => $corePath . 'elements/snippets/',
            'templates_path'             => $corePath . 'templates/',
            'assets_path'                => $assetsPath,
            'js_url'                     => $assetsUrl . 'js/',
            'css_url'                    => $assetsUrl . 'css/',
            'assets_url'                 => $assetsUrl,
            'connector_url'              => $assetsUrl . 'connector.php',
            'version'                    => '1.0.0'
        ];

        $this->modx->addPackage('easyform', $this->config['model_path']);

        if (is_array($this->config['lexicons'])) {
            foreach ($this->config['lexicons'] as $lexicon) {
                $this->modx->lexicon->load($lexicon);
            }
        } else {
            $this->modx->lexicon->load($this->config['lexicons']);
        }
    }

    
    /**
     * Get property.
     * @param mixed $key
     * @param string $default
     * 
     * @return mixed
     */
    public function getProperty($key, $default = '')
    {
        if (empty($this->properties[$key])) {
            return $default;
        }

        return $this->properties[$key];
    }

    /**
     * @access public.
     * @param String $key.
     * @param Array $options.
     * @param Mixed $default.
     * @return Mixed.
     */
    public function getOption($key, array $options = [], $default = null)
    {
        if (isset($options[$key])) {
            return $options[$key];
        }

        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        return $this->modx->getOption($this->config['namespace'] . '.' . $key, $options, $default);
    }

    /**
     * @todo Add custom messages.
     * @todo Add hooks
     */
    public function processForm()
    {
        $form = new Form($this, $this->getValidationLexicons());

        if ($form->isSubmitted()) {
            $form->validate();
        } else {
            $form->invokeEvent('onInitForm');
        }

        return $form;
    }

    /**
     * @return array Return list of validation lexicon entries.
     */
    protected function getValidationLexicons()
    {
        /* Setup query for db based lexicons */
        $query = $this->modx->newQuery('modLexiconEntry');
        $query->where([
            'namespace' => $this->config['namespace'],
            'topic'     => 'validation',
            'language'  => $this->modx->getOption('cultureKey', 'en')
        ]);

        $dbEntries = [];
        foreach ($this->modx->getIterator('modLexiconEntry', $query) as $result) {
            $dbEntries[$result->get('name')] = $result->toArray();
        }

        /* First get file-based lexicon */
        $entries = $this->modx->lexicon->getFileTopic($this->modx->getOption('cultureKey', 'en'), $this->config['namespace'], 'validation');
        $entries = is_array($entries) ? $entries : [];

        $list = [];
        foreach ($entries as $name => $value) {
            if (strpos($name, ':') !== false) {
                /**
                 * Convert nested strings to array. To support lexicon management from database and implement default illuminate validation error messages. 
                 * 
                 * This:
                 * $_lang['min:string'] = 'The :attribute must be at least :min characters.'
                 * 
                 * Becomes:
                 * 'min' => [
                 *      'string' => 'The :attribute must be at least :min characters.',
                 * ]
                 */
                $keys = explode(':', $name);

                $array = $value;
                foreach (array_reverse($keys) as $valueAsKey) {
                    $array = [$valueAsKey => $array];
                }
    
                $list = array_merge_recursive($list, $array);
            } else {
                $list[$name] = $value;   
            }
        }
    
        return $list;
    }

    public function getPdoFetch()
    {
        if (!$this->pdoFetch) {
            $this->setPdoFetch();
        }

        return $this->pdoFetch;
    }

    protected function setPdoFetch()
    {
        $this->pdoFetch = $this->modx->getService('pdofetch', 'pdoFetch', MODX_CORE_PATH . 'components/pdotools/model/pdotools/');
    }

    /**
     * Get chunk method that uses pdoFetch.
     *
     * @param $chunk
     * @param array $properties
     * @return mixed
     */
    public function getChunk($chunk, array $properties = [])
    {
        return $this->getPdoFetch()->getChunk($chunk, $properties);
    }

    /**
     * Return prepared path.
     * 
     * @param mixed $path
     * @return string
     */
    public function preparePath($path)
    {
        $path = preg_replace('/[\/]?{base_path}[\/]?(.*)/', '/' . trim(MODX_BASE_PATH, '/') . '/$1', $path);
        $path = preg_replace('/[\/]?{core_path}[\/]?(.*)/', '/' . trim(MODX_CORE_PATH, '/') . '/$1', $path);
        $path = preg_replace('/[\/]?{assets_path}[\/]?(.*)/', '/' . trim(MODX_ASSETS_PATH, '/') . '/$1', $path);

        return $path;
    }
}
