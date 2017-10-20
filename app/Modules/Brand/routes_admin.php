<?php

return [
    [
        'route' => '/list',
        'target' => ['\Modules\Brand\Controllers\BrandController', 'brand_list'],
        'name' => 'brand_list'
    ],
    [
        'route' => '/list/{slug:slug}',
        'target' => ['\Modules\Brand\Controllers\BrandController', 'brand_list'],
        'name' => 'brand_list_letter'
    ],
    [
        'route' => '/group/{i:id}',
        'target' => ['\Modules\Brand\Controllers\BrandController', 'brand_group'],
        'name' => 'brand_group'
    ],
    [
        'route' => '/group',
        'target' => ['\Modules\Brand\Controllers\BrandController', 'brand_group_index'],
        'name' => 'brand_group_index'
    ],
    [
        'route' => '/create',
        'target' => ['\Modules\Brand\Controllers\BrandController', 'create'],
        'name' => 'create_brand'
    ],
    [
        'route' => '/{i:id}',
        'target' => ['\Modules\Brand\Controllers\BrandController', 'update'],
        'name' => 'update_brand'
    ],
];