<?php

namespace Semushka\Api\Controllers;

use Bitrix\Main\Context,
    Bitrix\Currency\CurrencyManager,
    Bitrix\Sale\Order,
    Bitrix\Sale\Basket,
    Bitrix\Sale\Delivery,
    Bitrix\Sale\PaySystem;
use Bitrix\Main\Type\DateTime;

class Sale
{

    private $iblock_offers = 111; // Инфоблок товарных предложений
    private $url_post = 'https://widget.pochta.ru/api/pvz/index_public'; // Адрес апи почты
    private $url_status_update = 'https://semushka.fittin.ru/order/update/'; // Адрес апи для смены статуса
    private $account_id = '5613b7e4-d5ad-478a-af65-cb1f19e3c157'; // Токен для почты россии
    private $price_delivery = 250; // Стоимость доставки внутри МКАД
    private $pay_system = 12; // Стоимость доставки внутри МКАД
    private $crm_token = 'mz6p4vwzf6lvljgh6568iyq6bo27s0zk';
    private $crm_event = 'ONCRMDEALUPDATE';
    private $list_stages = ["FINAL_INVOICE", "UC_3UTDJ9", "UC_2RKAZ1", "LOSE"];

    public function __construct()
    {
    }

    private function getOffers($ids)
    {
        $offers_list = [];
        $offers_ids  = [];
        if (!empty($ids)) {
            $arSelect = array("ID", "NAME", "IBLOCK_ID", "XML_ID");
            $arFilter = array("IBLOCK_ID" => $this->iblock_offers, "ACTIVE" => "Y", "XML_ID" => $ids);
            $res      = \CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
            while ($ob = $res->GetNextElement()) {
                $arFields                     = $ob->GetFields();
                $offers_list[$arFields["ID"]] = $arFields;
                $offers_ids[$arFields["ID"]]  = $arFields["ID"];
            }
        }
        return [
            'offers_list' => $offers_list,
            'offers_ids'  => $offers_ids
        ];
    }

    private function getOffersAvailability($offers)
    {
        $result         = [];
        $rsStoreProduct = \Bitrix\Catalog\StoreProductTable::getList(array(
            'filter' => array('=PRODUCT_ID' => $offers["offers_ids"], '=STORE.ACTIVE' => 'Y'),
            'select' => array('*'),
        ));
        while ($arStoreProduct = $rsStoreProduct->fetch()) {
            $result[] = [
                "offer_id"     => $offers["offers_list"][$arStoreProduct["PRODUCT_ID"]]["XML_ID"],
                "warehouse_id" => $arStoreProduct["STORE_ID"],
                "quantity"     => $arStoreProduct["AMOUNT"]
            ];
        }
        return $result;
    }

    private function getStores($availability)
    {
        $ids = [];
        foreach ($availability as $item) {
            $ids[] = $item["warehouse_id"];
        }
        $rsStore = \Bitrix\Catalog\StoreTable::getList(array(
            'filter' => array("ID" => $ids),
        ));
        $stores  = [];
        while ($arStore = $rsStore->fetch()) {
            $stores[] = [
                "id"      => $arStore["ID"],
                "name"    => $arStore["TITLE"],
                "address" => $arStore["ADDRESS"],
                "lat"     => $arStore["GPS_N"],
                "lon"     => $arStore["GPS_S"],
            ];
        }
        return $stores;
    }

    public function availability()
    {
        $ids_responce = request()->get("offer_ids");
        \CModule::IncludeModule('catalog');
        $offers       = $this->getOffers($ids_responce);
        $availability = $this->getOffersAvailability($offers);
        $stores       = $this->getStores($availability);
        response()->json([
            "warehouses"   => $stores,
            "availability" => $availability
        ]);

    }

    public function delivery()
    {
        $zip                = request()->get("zip");
        $httpClient         = new \Bitrix\Main\Web\HttpClient();
        $sendArray["order"] = [
            'account_id'       => $this->account_id,
            'account_type'     => "bitrix_cms",
            'shipping_address' => [
                "full_locality_name" => $zip,
                "location"           => [
                    "zip" => $zip
                ]
            ]
        ];
        $response           = $httpClient->post($this->url_post, $sendArray);
        $arResponse         = json_decode($response);
        response()->json($arResponse);
    }

    public function local_delivery()
    {
        $result = [
            'local_delivery' => $this->price_delivery
        ];
        response()->json($result);
    }

    private static function genPassword($length = 8)
    {
        $password = '';
        $arr      = array(
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
            'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
            'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'
        );

        for ($i = 0; $i < $length; $i++) {
            $password .= $arr[random_int(0, count($arr) - 1)];
        }
        return $password;
    }

    private static function reg($name = '', $email = '', $phone = '')
    {
        $user     = new \CUser;
        $password = self::genPassword();
        $arFields = [
            "NAME"             => $name,
            "LAST_NAME"        => $name,
            "EMAIL"            => $email,
            "LOGIN"            => $email,
            "PERSONAL_PHONE"   => $phone,
            "LID"              => "ru",
            "ACTIVE"           => "Y",
            "GROUP_ID"         => array(16),
            "PASSWORD"         => $password,
            "CONFIRM_PASSWORD" => $password,
        ];
        $ID       = $user->Add($arFields);
        if (intval($ID) > 0) {
            return [
                "success" => true,
                "id"      => $ID,
            ];
        } else {
            return [
                "success" => false,
                "message" => $user->LAST_ERROR,
            ];
        }
    }

    private static function getUserByPhone($phone)
    {
        $result  = [];
        $filter  = [
            "ACTIVE"         => "Y",
            "PERSONAL_PHONE" => $phone,
        ];
        $rsUsers = \CUser::GetList(($by = "ID"), ($order = "desc"), $filter);
        while ($arUser = $rsUsers->Fetch()) {
            $result = $arUser;
        };
        return $result;
    }

    public function order()
    {
        \Bitrix\Main\Loader::includeModule("sale");
        \Bitrix\Main\Loader::includeModule("catalog");

        $result["order"] = [
            "success" => false
        ];

        $name        = request()->get("name");
        $email       = request()->get("email");
        $phone       = request()->get("phone");
        $items       = request()->get("items");
        $delivery_id = intval(request()->get("delivery_id"));
        $address     = request()->get("address");
        $zip         = request()->get("zip");

        if (empty($delivery_id)) {
            $result["order"]["error"]      = 1;
            $result["order"]["error_text"] = 'Не указан способ доставки!';
            response()->json($result);
        }

        if (empty($address)) {
            $result["order"]["error"]      = 1;
            $result["order"]["error_text"] = 'Не указан адрес!';
            response()->json($result);
        }

        if (empty($zip)) {
            $result["order"]["error"]      = 1;
            $result["order"]["error_text"] = 'Не указан индекс!';
            response()->json($result);
        }

        $user = [];
        if (!empty($phone)) {
            $user = self::getUserByPhone($phone);
            if (empty($user["ID"])) {
                $reg = self::reg($name, $email, $phone);
                if ($reg["success"]) {
                    $user = self::getUserByPhone($phone);
                } else {
                    $result["order"]["error"]      = 1;
                    $result["order"]["error_text"] = $reg["message"];
                    response()->json($result);
                }
            }
            global $USER;
            if (!is_object($USER)) {
                $USER = new \CUser;
            }
            $USER->Authorize($user["ID"]);
        } else {
            $result["order"]["error"]      = 1;
            $result["order"]["error_text"] = 'Не указан номер телефона!';
            response()->json($result);
        }

        $siteId       = Context::getCurrent()->getSite();
        $currencyCode = CurrencyManager::getBaseCurrency();

        // Создаём новый заказ
        $order = Order::create($siteId, $user["ID"]);
        $order->setPersonTypeId(7);
        $order->setField('CURRENCY', $currencyCode);

        // Создаём корзину
        $basket = Basket::create($siteId);
        if (!empty($items)) {
            $xml_ids = [];
            foreach ($items as $item) {
                $xml_ids[] = $item["offer_id"];
            }
            $offers = self::getOffers($xml_ids);
            if (empty($offers["offers_ids"])) {
                $result["order"]["error"]      = 1;
                $result["order"]["error_text"] = 'Данные товары отсутствуют!';
                response()->json($result);
            }
            foreach ($offers["offers_list"] as $offer) {
                foreach ($items as $item) {
                    if ($item["offer_id"] == $offer["XML_ID"]) {
                        $item_basket = $basket->createItem('catalog', $offer["ID"]);
                        $item_basket->setFields(array(
                            'QUANTITY'               => $item["quantity"],
                            'CURRENCY'               => $currencyCode,
                            'LID'                    => $siteId,
                            'PRODUCT_PROVIDER_CLASS' => '\CCatalogProductProvider',
                        ));
                    }
                }
            }
            $order->setBasket($basket);
        } else {
            $result["order"]["error"]      = 1;
            $result["order"]["error_text"] = 'Отсутствуют товары!';
            response()->json($result);
        }

        // Создаём одну отгрузку и устанавливаем способ доставки
        $shipmentCollection = $order->getShipmentCollection();
        $shipment           = $shipmentCollection->createItem();
        $service            = Delivery\Services\Manager::getById($delivery_id);
        $shipment->setFields(array(
            'DELIVERY_ID'   => $service['ID'],
            'DELIVERY_NAME' => $service['NAME'],
        ));
        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        foreach ($basket as $item) {
            $shipmentItem = $shipmentItemCollection->createItem($item);
            $shipmentItem->setQuantity($item->getQuantity());
        }

        // Создаём оплату
        $paymentCollection = $order->getPaymentCollection();
        $payment           = $paymentCollection->createItem();
        $paySystemService  = PaySystem\Manager::getObjectById($this->pay_system);
        $payment->setFields(array(
            'PAY_SYSTEM_ID'   => $paySystemService->getField("PAY_SYSTEM_ID"),
            'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
        ));

        // Устанавливаем свойства
        $propertyCollection = $order->getPropertyCollection();
        $phoneProp          = $propertyCollection->getPhone();
        $phoneProp->setValue($phone);
        $nameProp = $propertyCollection->getPayerName();
        $nameProp->setValue($name);
        $ZipProp = $propertyCollection->getDeliveryLocationZip();
        $ZipProp->setValue($zip);
        $LocateProp = $propertyCollection->getAddress();
        $LocateProp->setValue($address);

        // Сохраняем
        $order->doFinalAction(true);
        $save    = $order->save();
        $orderId = $order->getId();

        // Отправляем заказ в ответ
        $order_info = Order::load($orderId);
        if (!empty($order_info->getId())) {
            $result["items"] = [];
            $basket          = Basket::loadItemsForOrder($order_info);
            $product_price   = 0;
            foreach ($basket as $basketItem) {
                $product_price     += floatval($basketItem->getFinalPrice());
                $result["items"][] = [
                    "offer_id" => self::getXmlIdByProductId($basketItem->getProductId()),
                    "price"    => self::getPrice($basketItem->getPrice()),
                    "quantity" => $basketItem->getQuantity(),
                    "subtotal" => self::getPrice($basketItem->getFinalPrice()),
                ];
            }
            $result["order"] = [
                "success"        => true,
                "external_id"    => $order_info->getId(),
                "error"          => "",
                "error_text"     => null,
                "price"          => self::getPrice($product_price),
                "full_price"     => self::getPrice($order_info->getPrice()),
                "delivery_price" => self::getPrice($order_info->getDeliveryPrice()),
                "discount"       => self::getPrice($order_info->getDiscountPrice()),
            ];
        } else {
            $result["order"]["error"]      = 1;
            $result["order"]["error_text"] = 'Заказ не создан. Неизвестная ошибка!';
        }
        response()->json($result);
    }

    public function order_list()
    {
        \Bitrix\Main\Loader::includeModule("sale");
        \Bitrix\Main\Loader::includeModule("catalog");
        \Bitrix\Main\Loader::includeModule('crm');

        $external_id = request()->get("external_id");
        $create_from = request()->get("date_create_from");
        $create_to   = request()->get("date_create_to");

        $deals_params = [];
        $deals_params['order'] = ['ID' => 'DESC'];
        $deals_params['select'] = ["*", "UF_*"];

        if (!empty($external_id)) {
            $deals_params['filter']['UF_ID_ORDER'] = intval($external_id);
        }
        if (!empty($create_from) && !empty($create_to)) {
            $from_date = DateTime::createFromTimestamp(strtotime($create_from." 00:00:00"));
            $to_date = DateTime::createFromTimestamp(strtotime($create_to." 23:59:59"));
            $deals_params['filter'] = [
                "LOGIC" => "AND",
                [
                    '>=DATE_CREATE' => $from_date,
                    '<=DATE_CREATE' => $to_date
                ]
            ];
        }

        $arDeals = \Bitrix\Crm\DealTable::getList($deals_params)->fetchAll();

        $orders = [];
        foreach ($arDeals as $deal) {
            if (empty($deal["UF_ID_ORDER"])) continue;
            if (!in_array($deal["STAGE_ID"], $this->list_stages)) continue;
            $order_info = Order::load($deal["UF_ID_ORDER"]);
            if (!empty($order_info->getId())) {
                $items         = [];
                $arProductRows = \CCrmDeal::LoadProductRows($deal["ID"]);
                foreach ($arProductRows as $row) {
                    $items[] = [
                        "offer_id" => $this->getXmlIdByProductId($row["PRODUCT_ID"]),
                        "external_product_id" => $row["PRODUCT_ID"],
                        "price"    => self::getPrice($row["PRICE"]),
                        "quantity" => $row["QUANTITY"],
                        "subtotal" => self::getPrice(intval($row["QUANTITY"]) * intval($row["PRICE"])),
                    ];
                }
                $orders[] = [
                    "external_id" => $deal["UF_ID_ORDER"],
                    "date_create" => $deal["DATE_CREATE"]->format("d.m.Y"),
                    "price"       => self::getPrice($deal["OPPORTUNITY"]),
                    "status"      => $deal["STAGE_ID"],
                    "items"       => $items,
                ];
            }
        }

        $result = [
            "success" => true,
            "orders"  => $orders,
        ];

        response()->json($result);
    }

    private static function getPrice($price)
    {
        return intval($price);
    }

    public function update()
    {
        $auth  = request()->get("auth");
        $event  = request()->get("event");
        $fields = request()->get("data");
        $id     = $fields["FIELDS"]["ID"];

        if ($auth["application_token"] == $this->crm_token && $event == $this->crm_event && !empty($id)) {
            \Bitrix\Main\Loader::includeModule('crm');
            $arDeals = \Bitrix\Crm\DealTable::getList([
                'order'  => ['ID' => 'DESC'],
                'filter' => ["ID" => $id],
                'select' => ["*", "UF_*"],
            ])->fetchAll();

            foreach ($arDeals as $deal) {
                if ($id == $deal["ID"]) {
                    $order_info      = Order::load($deal["UF_ID_ORDER"]);
                    $result          = [];
                    $result["order"] = [
                        "success" => false
                    ];
                    if (!empty($order_info->getId())) {
                        $result["items"] = [];
                        $arProductRows = \CCrmDeal::LoadProductRows($id);
                        foreach ($arProductRows as $row) {
                            $result["items"][] = [
                                "offer_id" => $this->getXmlIdByProductId($row["PRODUCT_ID"]),
                                "price"    => self::getPrice($row["PRICE"]),
                                "quantity" => $row["QUANTITY"],
                                "subtotal" => self::getPrice(intval($row["QUANTITY"]) * intval($row["PRICE"])),
                            ];
                        }
                        $result["order"] = [
                            "success"        => true,
                            "external_id"    => $deal["UF_ID_ORDER"],
                            "price"          => self::getPrice($deal["OPPORTUNITY"]),
                            "status"          => $deal["STAGE_ID"],
                        ];
                        $httpClient         = new \Bitrix\Main\Web\HttpClient();
                        $response           = $httpClient->post($this->url_status_update, json_encode($result));
                        \CEventLog::Add(array(
                            "SEVERITY"      => "MAIN",
                            "AUDIT_TYPE_ID" => "update_order",
                            "MODULE_ID"     => "main",
                            "ITEM_ID"       => $deal["UF_ID_ORDER"],
                            "DESCRIPTION"   => json_encode($result).json_encode($response),
                        ));
                    }
                }
            }
        }
    }

    public function getXmlIdByProductId($id) {
        $result = '';
        if (!empty($id)) {
            $arSelect = array("ID", "NAME", "IBLOCK_ID", "XML_ID");
            $arFilter = array("IBLOCK_ID" => $this->iblock_offers, "ACTIVE" => "Y", "ID" => $id);
            $res      = \CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
            while ($ob = $res->GetNext()) {
                return $ob["XML_ID"];
            }
        }
        return $result;
    }
}