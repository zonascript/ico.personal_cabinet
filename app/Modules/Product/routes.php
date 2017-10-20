<?php
return [
    /** PRODUCTS ROUTES */
    [
        'route' => '/product/{slug:sku}',
        'target' => ['\Modules\Product\Controllers\DefaultController', 'actionView'],
//        'name' => 'product:view'
    ],
    [
        'route' => '/product/{i:id}/{slug:slug}',
        'target' => ['\Modules\Product\Controllers\DefaultController', 'actionViewOld'],
        'name' => 'product:view',
    ],


    /** CATEGORY ROUTES */

    [
        'route' => '/category/{i:id}/{slug:slug}/',
        'target' => ['\Modules\Product\Controllers\CategoryController', 'actionViewOld'],
        'name' => 'view:old',
//        'meta' => [
//            'cache' => true,
//            'cache_time' => 60
//        ]
    ],


    /** SEARCH ROUTES */
//    [
//        'route' => '/search',
//        'target' => ['\Modules\Product\Controllers\SearchController', 'actionSearch'],
//        'name' => 'search',
//    ],
    [
        'route' => '/api/v1/suggestion/search',
        'target' => ['\Modules\Product\Controllers\SearchController', 'actionApiSuggestion'],
        'name' => 'api:search:suggestion',
    ],
//
//    [
//        'route' => '/keyword/{slug:q}',
//        'target' => ['\Modules\Product\Controllers\SearchController', 'actionKeywords'],
//    ],
//    [
//        'route' => '/keyword/{slug:q}/',
//        'target' => ['\Modules\Product\Controllers\SearchController', 'actionKeywords'],
//    ],

//    /** PRODUCT CART ADD */
//    [
//        'route' => '/cart/add/product-{slug:key}',
//        'target' => ['\Modules\Product\Controllers\SearchController', 'actionAdd'],
//        'name' => 'cart:add',
//        'config' => [
//            'cache' => false,
//        ]
//    ],
];