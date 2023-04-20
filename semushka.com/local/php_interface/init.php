<?php

$eventManager = \Bitrix\Main\EventManager::getInstance();

\Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    '\Semushka\Api\Controllers\Sale' => '/local/api/controllers/sale.php',

    // KilBil
    '\Semushka\Classes\KilbilConfig' => '/local/classes/KilbilConfig.php',
    '\Semushka\Classes\KilbilOrder' => '/local/classes/KilbilOrder.php',
    '\Semushka\Classes\KilbilUser' => '/local/classes/KilbilUser.php',
]);

$eventManager->addEventHandlerCompatible(
    'crm',
    'OnBeforeCrmDealAdd',
    function( &$arFields )
    {
        $arFields["UF_ID_ORDER"] = $arFields["ORDER_ID"];
        return true;
    }
);

// Отправка заказа в KilBil
\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleOrderSaved',
    [
        '\Semushka\Classes\KilbilOrder',
        'initOrder'
    ]
);

// Потверждение заказа в KilBil
\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleOrderPaid',
    [
        '\Semushka\Classes\KilbilOrder',
        'confirmOrder'
    ]
);

// Синхронизация пользователей с KilBil
\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'main',
    'OnProlog',
    [
        '\Semushka\Classes\KilbilUser',
        'initClient'
    ]
);

if (Bitrix\Main\Loader::includeModule('artamonov.rest')) \Artamonov\Rest\Foundation\Core::getInstance()->run();