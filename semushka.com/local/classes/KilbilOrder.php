<?php

namespace Semushka\Classes;

class KilbilOrder
{

    function initOrder(\Bitrix\Main\Event $event)
    {
        $arUser = KilbilUser::getUser();
        if (!empty($arUser["UF_CLIENT_ID"])) { // Если у пользователя уже есть id системы KilBil
            $order = $event->getParameter("ENTITY");
            $isNew = $event->getParameter("IS_NEW");
            if ($isNew) {
                $paymentCollection = $order->getPaymentCollection();
                $sum = 0;
                if ($paymentCollection->isExistsInnerPayment()) {
                    foreach ($paymentCollection as $payment) {
                        if ($payment->isInner()) {
                            $sum = $payment->getSum();
                        }
                    }
                    $goods_data = [];
                    $basket = $order->getBasket();
                    foreach ($basket as $basketItem) {
                        $goods_data[] = [
                            "name" => $basketItem->getField('NAME'),
                            "id" => $basketItem->getProductId(),
                            "price" => $basketItem->getPrice(),
                            "quantity" => $basketItem->getQuantity(),
                            "total" => $basketItem->getFinalPrice(),
                            "discounted_price" => $basketItem->getPrice(),
                            "discounted_total" => $basketItem->getFinalPrice(),
                            "code" => self::getXMLId($basketItem->getProductId())
                        ];
                    }
                    $KilBilOrder = [
                        "client_id"  => $arUser["UF_CLIENT_ID"],
                        "type"       => 0,
                        "bonus_out"  => $sum,
                        "max_bonus_out"  => $sum,
                        "max_bill_bonus_out"  => $_SESSION["max_bill_bonus_out"],
                        "goods_data" => $goods_data,
                        "move_id" => str_pad($order->getId(), 6, '0', STR_PAD_LEFT)
                    ];
                } else {
                    $goods_data = [];
                    $basket = $order->getBasket();
                    foreach ($basket as $basketItem) {
                        $goods_data[] = [
                            "name" => $basketItem->getField('NAME'),
                            "id" => $basketItem->getProductId(),
                            "price" => $basketItem->getPrice(),
                            "quantity" => $basketItem->getQuantity(),
                            "total" => $basketItem->getFinalPrice(),
                            "discounted_price" => $basketItem->getPrice(),
                            "discounted_total" => $basketItem->getFinalPrice(),
                            "code" => self::getXMLId($basketItem->getProductId())
                        ];
                    }
                    $KilBilOrder = [
                        "client_id"  => $arUser["UF_CLIENT_ID"],
                        "type"       => 0,
                        "bonus_out"  => 0,
                        "max_bonus_out"  => 0,
                        "max_bill_bonus_out"  => $_SESSION["max_bill_bonus_out"],
                        "goods_data" => $goods_data,
                        "move_id" => str_pad($order->getId(), 6, '0', STR_PAD_LEFT)
                    ];
                }
                $responce = KilbilUser::request(KilbilConfig::$url_order, $KilBilOrder);
                \CEventLog::Add(array(
                    "SEVERITY"      => "SECURITY",
                    "AUDIT_TYPE_ID" => "KILBIL_ORDER",
                    "MODULE_ID"     => "main",
                    "ITEM_ID"       => 1,
                    "DESCRIPTION"   => json_encode($KilBilOrder, true),
                ));
                \CEventLog::Add(array(
                    "SEVERITY"      => "SECURITY",
                    "AUDIT_TYPE_ID" => "KILBIL_RESPONCE",
                    "MODULE_ID"     => "main",
                    "ITEM_ID"       => 1,
                    "DESCRIPTION"   => print_r($responce, true),
                ));
            }
        }
    }

    public static function getXMLId($id) {
        $arSelect = Array('ID', 'NAME', 'XML_ID');
        $arFilter = Array('IBLOCK_ID' => 111, "ID" => $id);
        $res = \CIBlockElement::GetList(Array('SORT'=>'ASC'), $arFilter, false, false, $arSelect);
        while($ob = $res->GetNext()) {
            return $ob['XML_ID'];
        }
    }

    function confirmOrder(\Bitrix\Main\Event $event) {
        $arUser = KilbilUser::getUser();
        if (!empty($arUser["UF_CLIENT_ID"])) { // Если у пользователя уже есть id системы KilBil
            $order = $event->getParameter("ENTITY");
            if ($order->isPaid()) {
                $payments[] = [
                    "type"           => 1,
                    "payment_amount" => $order->getSumPaid()
                ];
                $KilBilOrder = [
                    "move_id"  => str_pad($order->getId(), 6, '0', STR_PAD_LEFT),
                    "username" => "Кассир",
                    "useruid"  => "1",
                    "payments" => $payments
                ];
                $responce    = KilbilUser::request(KilbilConfig::$url_confirm, $KilBilOrder);
                \CEventLog::Add(array(
                    "SEVERITY"      => "SECURITY",
                    "AUDIT_TYPE_ID" => "KILBIL_CONFIRM",
                    "MODULE_ID"     => "main",
                    "ITEM_ID"       => 1,
                    "DESCRIPTION"   => json_encode($KilBilOrder, true),
                ));
                \CEventLog::Add(array(
                    "SEVERITY"      => "SECURITY",
                    "AUDIT_TYPE_ID" => "KILBIL_RESPONCE_CONFIRM",
                    "MODULE_ID"     => "main",
                    "ITEM_ID"       => 1,
                    "DESCRIPTION"   => print_r($responce, true),
                ));
            }
        }
    }

}