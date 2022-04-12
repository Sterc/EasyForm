<?php

$modelPath = $modx->getOption(
    'easyform.core_path',
    null,
    $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/easyform/'
) . 'model/easyform/';
$modx->loadClass('EasyForm', $modelPath, true, true);

$form = new EasyForm($modx, $scriptProperties);

return $form->processForm();
