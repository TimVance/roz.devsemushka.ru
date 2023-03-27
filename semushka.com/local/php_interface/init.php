<?php

$eventManager = \Bitrix\Main\EventManager::getInstance();

Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    '\Semushka\Api\Controllers\Sale' => '/local/api/controllers/sale.php',
    '\Semushka\Classes\Kilbil' => '/local/classes/Kilbil.php',
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


\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleComponentOrderCreated',
    [
        '\Semushka\Classes\KilbilOrder',
        'initOrder'
    ]
);

//if ($_REQUEST["admin"] == 'Y') {
    \Bitrix\Main\EventManager::getInstance()->addEventHandler(
        'main',
        'OnProlog',
        [
            '\Semushka\Classes\KilbilUser',
            'initClient'
        ]
    );
//}

if (Bitrix\Main\Loader::includeModule('artamonov.rest')) \Artamonov\Rest\Foundation\Core::getInstance()->run();