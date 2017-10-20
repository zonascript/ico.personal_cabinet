<?php
return [
    [
        'route' => '',
        'target' => ['\Modules\Main\Controllers\DefaultController', 'index'],
        'name' => 'index'
    ],
    [
        'route' => '/business-relations',
        'target' => ['\Modules\Main\Controllers\DefaultController', 'actionBusinness'],
        'name' => 'business'
    ],
    [
        'route' => '/receipt-confirmation',
        'target' => ['\Modules\Main\Controllers\DefaultController', 'actionORConfirm'],
        'name' => 'receipt:confirmation'
    ],
    [
        'route' => '/shop',
        'target' => ['\Modules\Main\Controllers\SearchController', 'index'],
        'name' => 'shop'
    ],
    [
        'route' => '/api/v1/search',
        'target' => ['\Modules\Main\Controllers\SearchController', 'actionApiSearch'],
        'name' => 'api:search'
    ]

];