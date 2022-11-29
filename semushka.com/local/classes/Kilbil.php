<?php

namespace Semushka\Classes;

class Kilbil
{

    private static $kilbil_key        = 'b76ede2e0f2177cdb85016f32fa5a936'; // ключ
    private static $url_add_client    = 'https://bonus.kilbil.ru/load/addclient?h='; // url для регистрации
    private static $url_search_client = 'https://bonus.kilbil.ru/load/searchclient?h='; // url для поиск

    /**
     * Инициализация пользователя в системе KilBil
     *
     * @return void
     */
    function initClient()
    {
        $user = self::getUser();
        if (!empty($user["ID"])) {
            if (!empty($user["UF_CLIENT_ID"])) {
                // Мы уже знаем id клиента в системе
            } elseif (!empty($user["PERSONAL_PHONE"])) {
                // Если у клиента еще нет id kilbil
                $responce = self::request(self::$url_search_client, [
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
        return self::request(self::$url_add_client, [
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
    private static function request($url, $data)
    {
        $httpClient = new \Bitrix\Main\Web\HttpClient();
        return json_decode($httpClient->post(
            $url . self::$kilbil_key,
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
    private static function getUser()
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