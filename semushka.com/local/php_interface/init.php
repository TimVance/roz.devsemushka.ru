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




if ($_REQUEST["admin"] == 'Y') {
    \Bitrix\Main\EventManager::getInstance()->addEventHandler(
        'main',
        'OnProlog',
        [
            '\Semushka\Classes\Kilbil',
            'initClient'
        ]
    );
}

if (Bitrix\Main\Loader::includeModule('artamonov.rest')) \Artamonov\Rest\Foundation\Core::getInstance()->run();