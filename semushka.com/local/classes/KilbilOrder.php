<?php

namespace Semushka\Classes;

class KilbilOrder {

    function initOrder($order) {
        CEventLog::Add(array(
            "SEVERITY"      => "SECURITY",
            "AUDIT_TYPE_ID" => "MY_OWN_TYPE",
            "MODULE_ID"     => "main",
            "ITEM_ID"       => 123,
            "DESCRIPTION"   => print_r($order, true),
        ));
    }

}