<?php

namespace Semushka\Classes;

use Bitrix\Sale;

class KilbilUser
{

    /**
     * Инициализация пользователя в системе KilBil
     *
     * @return void
     */
    function initClient()
    {
        global $APPLICATION;
        $user = self::getUser();
        if (!empty($user["ID"])) {
            if (!empty($user["UF_CLIENT_ID"])) {
                // Мы уже знаем id клиента в системе
                if ($APPLICATION->GetCurPage() == '/order/') {
                    $basket = \Bitrix\Sale\Basket::loadItemsForFUser(
                        \Bitrix\Sale\Fuser::getId(),
                        \Bitrix\Main\Context::getCurrent()->getSite()
                    );
                    $goods_data = [];
                    foreach ($basket as $basketItem) {
                        $goods_data[] = [
                            "name" => $basketItem->getField('NAME'),
                            "id" => $basketItem->getProductId(),
                            "price" => $basketItem->getPrice(),
                            "quantity" => $basketItem->getQuantity(),
                            "total" => $basketItem->getFinalPrice(),
                            "discounted_price" => $basketItem->getPrice(),
                            "discounted_total" => $basketItem->getFinalPrice(),
                            "code" => KilbilOrder::getXMLId($basketItem->getProductId())
                        ];
                    }
                    $data = [
                        "search_mode"  => 0,
                        "search_value" => self::formatPhone($user["PERSONAL_PHONE"]),
                        "goods_data" => $goods_data
                    ];
                    $responce = self::request(KilbilConfig::$url_search_client, $data);
                    if (!empty($responce["client_id"]) && !empty($responce["max_bonus_out"])) {
                        if(!\CSaleUserAccount::GetByUserID($user["ID"], "RUB")) {
                            $arFields = Array("USER_ID" => $user["ID"], "CURRENCY" => "RUB", "CURRENT_BUDGET" => 0);
                            \CSaleUserAccount::Add($arFields);
                        }
                        $balance = \CSaleUserAccount::GetByUserID($user["ID"], "RUB");
                        if (intval($balance["CURRENT_BUDGET"]) != intval($responce["max_bonus_out"])) {
                            $sum = intval($responce["max_bonus_out"]) - intval($balance["CURRENT_BUDGET"]);
                            if ($sum !== 0) {
                                \CSaleUserAccount::UpdateAccount(
                                    $user["ID"],
                                    $sum,
                                    "RUB",
                                    "KILBIL"
                                );
                            }
                        }
                        $_SESSION["max_bill_bonus_out"] = $responce["max_bill_bonus_out"];
                    }
                }
            } elseif (!empty($user["PERSONAL_PHONE"])) {
                // Если у клиента еще нет id kilbil
                $responce = self::request(KilbilConfig::$url_search_client, [
                    "search_mode"  => 0,
                    "search_value" => self::formatPhone($user["PERSONAL_PHONE"])
                ]);
                if (!empty($responce["client_id"])) {
                    // Если номер найден в базе, записываем id
                    self::updateClientId($responce["client_id"]);
                } else {
                    $register = self::addclient($user);
                    if ($register["success"] == 1) {
                        self::updateClientId($register["_client_id"]);
                    }
                }
            }
        }
    }

    /**
     * Регистрация в системе KilBil
     *
     * @param $arUser
     * @return bool|string
     */
    private static function addclient($arUser)
    {
        return self::request(KilbilConfig::$url_add_client, [
            "phone"       => self::formatPhone($arUser["PERSONAL_PHONE"]),
            "first_name"  => $arUser["NAME"],
            "last_name"   => $arUser["LAST_NAME"],
            "middle_name" => $arUser["SECOND_NAME"],
            "birth_date"  => $arUser["PERSONAL_BIRTHDAY"],
            "email"       => $arUser["EMAIL"],
        ]);
    }

    /**
     * Запрос
     *
     * @param $data
     * @return bool|string
     */
    public static function request($url, $data)
    {
        $httpClient = new \Bitrix\Main\Web\HttpClient();
        return json_decode($httpClient->post(
            $url . KilbilConfig::$kilbil_key,
            json_encode($data)
        ), true);
    }

    /**
     * Форматирование номера телефона
     *
     * @param $phone
     * @return array|string|string[]
     */
    private static function formatPhone($phone)
    {
        return str_replace(['(', ')', '+', ' ', '-'], '', $phone);
    }

    /**
     * Возвращает пользователя
     *
     * @return array|false
     */
    public static function getUser()
    {
        global $USER;
        $rsUsers = \CUser::GetList(
            ($by = "id"),
            ($order = "desc"),
            [
                "ID"     => $USER->GetID(),
                "ACTIVE" => "Y",
            ],
            [
                "SELECT" => [
                    "UF_*"
                ]
            ]
        );
        return $rsUsers->Fetch();
    }

    /**
     * Обновление user_id в kilbil
     *
     * @param $client_id
     * @return void
     */
    private static function updateClientId($client_id)
    {
        global $USER;
        $user = new \CUser;
        $user->Update(
            $USER->GetID(),
            [
                "UF_CLIENT_ID" => $client_id
            ]
        );
    }

}