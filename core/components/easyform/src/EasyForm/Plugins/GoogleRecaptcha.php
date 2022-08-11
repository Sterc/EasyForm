<?php

namespace Sterc\EasyForm\Plugins;


class GoogleRecaptcha extends BasePlugin
{
    /**
     * @todo Add settings for site key and site secret.
     */
    public function onInitForm($properties)
    {
        $version    = $this->form->modx->getOption('version', $properties, 'v2');
        $type       = $this->form->modx->getOption('type', $properties, 'html');

        $properties['site_key']   = $this->form->modx->getOption('site_key', $properties, '');
        $properties['cultureKey'] = $this->form->modx->getOption('cultureKey', $properties, $this->form->modx->getOption('cultureKey'));
        $properties['token_key'] = $this->form->modx->getOption('token_key', $properties, 'g-recaptcha-response');
        $properties['action_key'] = $this->form->modx->getOption('action_key', $properties, 'recaptcha-action');
        $properties['form_id'] = $this->form->modx->getOption('form_id', $properties, 'easyForm');


        /**
         * @todo Check inbouwen zodat dit werkt met meerdere formulieren op 1 pagina.
         */
        if(!$this->form->modx->getPlaceholder('rcv3_initialized')) {
            $this->form->modx->regClientStartupScript('<script src="https://www.google.com/recaptcha/api.js?onload=ReCaptchaCallbackV3&render=' . $properties['site_key'] . '" async></script>');


            $tpl = $this->form->modx->getOption('tpl', $properties, '@INLINE <script>
            var ReCaptchaCallbackV3 = function() {
                grecaptcha.ready(function() {
                    grecaptcha.reset = grecaptchaExecute;
                    grecaptcha.reset();
                });
            };
            function grecaptchaExecute() {
                grecaptcha.execute("[[+site_key]]", { action: "[[+action_key]]" }).then(function(token) {
                    var fieldsToken = document.querySelectorAll("[name =\'[[+token_key]]\']");
                    Array.prototype.forEach.call(fieldsToken, function(el, i){
                        el.value = token;
                    });
                });
            };
            setInterval(function() {
                grecaptcha.reset();
            }, 60000);
        </script>');
            $this->form->modx->regClientScript($this->form->easyForm->getChunk($tpl, $properties), true);
             $this->form->modx->setPlaceholder('rcv3_initialized', 1);
        }
    
        /**
         * @todo built in switch between versions and types.
         */

        if ($version === 'v2' && $type === 'html') {
            $tpl = $this->form->modx->getOption('tpl', $properties, '@INLINE <div class="g-recaptcha" data-sitekey="[[+site_key]]"></div><script type="text/javascript" src="https://www.google.com/recaptcha/api.js?hl=[[+cultureKey]]" async defer></script>');
        } elseif ($version === 'v3' && $type === 'html') {
            $tpl = $this->form->modx->getOption('tpl', $properties, '@INLINE 
                <input type="hidden" name="[[+token_key]]">
                <input type="hidden" name="[[+action_key]]" value="[[+form_id]]">
            ');
        }
    
        $this->form->modx->setPlaceholder('form.recaptcha', $this->form->easyForm->getChunk($tpl, $properties));
    }

    /**
     * @todo Implement v2 variants & v3.
     * @todo On hold untill we know which recaptcha will be implemented.
     */
    public function onFormSubmitted($properties)
    {
        $secretKey  = $this->form->modx->getOption('secret_key', $properties, '');
        $version    = $this->form->modx->getOption('version', $properties, 'v2');

        if ($version === 'v3') {
            $properties['ip'] = $this->form->modx->getOption('HTTP_CF_CONNECTING_IP', $_SERVER, $_SERVER['REMOTE_ADDR'], true);
            $properties['display_resp_errors'] = $this->form->modx->getOption('display_resp_errors', $properties, true);
            $properties['token_key'] = $this->form->modx->getOption('token_key', $properties, 'g-recaptcha-response');
            $properties['action_key'] = $this->form->modx->getOption('action_key', $properties, 'recaptcha-action');
            $properties['threshold'] = (float) $this->form->modx->getOption('threshold', $properties, 0.5);


            $recaptcha_err_msg = $this->form->modx->lexicon('recaptchav2.recaptchav3_error_message');
            $tech_err_msg = $this->form->modx->lexicon('recaptchav2.technical_error_message');
            
            try {
                $recaptcha = new \ReCaptcha\ReCaptcha($secretKey, new \ReCaptcha\RequestMethod\CurlPost());
            } catch (\Exception $e) {
                $this->form->modx->log(\xPDO::LOG_LEVEL_ERROR, 'Failed to load Recaptcha class.');
                return false;
            }

            if (!($recaptcha instanceof \ReCaptcha\ReCaptcha)) {
                $this->form->addError('recaptchav3_error', $tech_err_msg);
                $this->form->modx->log(\modX::LOG_LEVEL_ERROR, 'Failed to load Recaptcha class.');
                return false;
            }
            
            $resp = null;
            $error = null;
            
                
            if ($this->form->getValue($properties['token_key'])) {
                $resp = $recaptcha->setExpectedHostname(parse_url($this->form->modx->getOption('site_url'), PHP_URL_HOST))
                          ->setExpectedAction($this->form->getValue($properties['action_key']))
                          ->setScoreThreshold($properties['threshold'])
                          ->verify($this->form->getValue($properties['token_key']), $properties['ip']);
            }

            if ($resp !== null && $resp->isSuccess()) {
                return true;
            } else {
                $msg = '';
                if ($resp != null && $properties['display_resp_errors']) {
                    foreach ($resp->getErrorCodes() as $error) {
                        $msg .= $error . "\n";
                    }
                }
                if (empty($msg)) { 
                $msg = $recaptcha_err_msg;
                $this->form->addError('recaptchav3_error', $msg);
                $this->form->modx->log(\modX::LOG_LEVEL_DEBUG, print_r($resp, true));
                return false;
                }
            }
            
        } else {
            $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) .  '&response=' . urlencode($captcha);
            if ($response = file_get_contents($url)) {
                $response = json_decode($response, true);
            }
            
    
            /**
             * @todo Set error.
             */
    
            return false;
            } 
        }
    }
