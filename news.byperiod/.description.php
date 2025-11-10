<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentDescription = [
    'NAME' => Loc::getMessage('NEWS_BY_PERIOD_COMPONENT_NAME'),
    'DESCRIPTION' => Loc::getMessage('NEWS_BY_PERIOD_COMPONENT_DESCRIPTION'),
    'PATH' => [
        'ID' => 'app',
        'NAME' => Loc::getMessage('NEWS_BY_PERIOD_COMPONENT_PATH_NAME'),
    ],
    'CACHE_PATH' => 'Y',
    'COMPLEX' => 'N',
];
