<?php

$eventManager = \Bitrix\Main\EventManager::getInstance();

$eventManager->addEventHandlerCompatible(
    'crm',
    'OnBeforeCrmDealAdd',
    function( &$arFields )
    {
        $arFields["UF_ID_ORDER"] = $arFields["ORDER_ID"];
        return true;
    }
);

Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    '\Semushka\Api\Controllers\Sale' => '/local/api/controllers/sale.php',
]);

if (Bitrix\Main\Loader::includeModule('artamonov.rest')) \Artamonov\Rest\Foundation\Core::getInstance()->run();