<?php
/**
 * Карта роутов для работы с заказом
 */

return [
    'POST' => [
        'sale/availability' => [
            'controller'  => '\Semushka\Api\Controllers\Sale@availability',
            'security'    => [
                'auth' => [
                    'required' => true,
                    'type'     => 'token'
                ],
            ],
            'contentType' => 'application/json',
            'description' => 'Наличие товаров на складах',
        ],
        'sale/delivery' => [
            'controller'  => '\Semushka\Api\Controllers\Sale@delivery',
            'security'    => [
                'auth' => [
                    'required' => true,
                    'type'     => 'token'
                ],
            ],
            'contentType' => 'application/json',
            'description' => 'Доставка по России почтой',
        ],
        'sale/local_delivery' => [
            'controller'  => '\Semushka\Api\Controllers\Sale@local_delivery',
            'security'    => [
                'auth' => [
                    'required' => true,
                    'type'     => 'token'
                ],
            ],
            'description' => 'Доставка внутри МКАД',
        ],
        'sale/order' => [
            'controller'  => '\Semushka\Api\Controllers\Sale@order',
            'security'    => [
                'auth' => [
                    'required' => true,
                    'type'     => 'token'
                ],
            ],
            'contentType' => 'application/json',
            'description' => 'Оформление заказа',
        ],
        'sale/update' => [
            'controller'  => '\Semushka\Api\Controllers\Sale@update',
            'security'    => [
                'auth' => [
                    'required' => false,
                ],
            ],
            'description' => 'Изменение статуса заказа',
        ]
    ],
];