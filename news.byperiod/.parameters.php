<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentParameters = [
    'PARAMETERS' => [
        'IBLOCK_ID' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('NEWS_BY_PERIOD_IBLOCK_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'IBLOCK_CODE' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('NEWS_BY_PERIOD_IBLOCK_CODE'),
            'TYPE' => 'STRING',
            'DEFAULT' => 'news',
        ],
        'PAGE_SIZE' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('NEWS_BY_PERIOD_PAGE_SIZE'),
            'TYPE' => 'STRING',
            'DEFAULT' => '5',
        ],
        'CACHE_TIME' => [
            'DEFAULT' => 36000000,
        ],
    ],
];
