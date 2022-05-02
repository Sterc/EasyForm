<?php

namespace Sterc\EasyForm\Plugins;

class GoogleRecaptcha extends BasePlugin
{
    /**
     * @todo Add settings for site key and site secret.
     */
    public function onInitForm($properties)
    {
        $version    = $this->form->modx->getOption('version', $properties, 'v3');
        $type       = $this->form->modx->getOption('type', $properties, 'html');
        $siteKey    = $this->form->modx->getOption('site_key', $properties, '');
        $cultureKey = $this->form->modx->getOption('cultureKey', $properties, $this->form->modx->getOption('cultureKey'));

        /**
         * @todo built in switch between versions and types.
         */
        if ($version === 'v2' && $type === 'html') {
            $tpl = $this->form->modx->getOption('tpl', $properties, '@INLINE <div class="g-recaptcha" data-sitekey="[[+site_key]]"></div><script type="text/javascript" src="https://www.google.com/recaptcha/api.js?hl=[[+cultureKey]]" async defer></script>');
        }
    

        $this->form->modx->setPlaceholder('form.recaptcha', $this->form->easyForm->getChunk($tpl, [
            'site_key'    => $siteKey,
            'culture_key' => $cultureKey
        ]));
    }

    /**
     * @todo Implement v2 variants & v3.
     * @todo On hold untill we know which recaptcha will be implemented.
     */
    public function onFormSubmitted($properties)
    {
        $siteKey    = $this->form->modx->getOption('site_key', $properties, '');
        $secretKey  = $this->form->modx->getOption('secret_key', $properties, '');
        $captcha    = $this->form->getValue('g-recaptcha-response');
    
        $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) .  '&response=' . urlencode($captcha);
        if ($response = file_get_contents($url)) {
            $response = json_decode($response, true);
        }

        if (isset($response['success']) && $response['success'] === true) {
            return true;
        }

        /**
         * @todo Set error.
         */

        return false;
    }
}
