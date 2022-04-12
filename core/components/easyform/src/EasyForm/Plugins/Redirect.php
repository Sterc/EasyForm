<?php

namespace Sterc\EasyForm\Plugins;

class Redirect extends BasePlugin
{
    public function onFormSubmitted($properties)
    {
        if (isset($properties['to'])) {
            if (is_integer($properties['to'])) {
                $scheme = $properties['scheme'] ?? -1;

                $properties['to'] = $this->form->modx->makeUrl($properties['to'], '', $properties['params'], $scheme);
            } else {
                $url = parse_url($properties['to']);

                if (!empty($properties['params']) && is_array($properties['params'])) {
                    $params = http_build_query($properties['params']);
                }

                if (!empty($url['query'])) {
                    $properties['to'] .= $params;
                } else {
                    $properties['to'] = rtrim($properties['to'], '?') . '?' . $params;
                }
            }

            $this->form->modx->sendRedirect($properties['to']);
        }

        return true;
    }
}
