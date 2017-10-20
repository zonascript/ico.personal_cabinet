<?php
return [
    /** List brands */
    [
        'route' => 's/',
        'target' => ['\Modules\Brand\Controllers\DefaultController', 'actionList'],
        'name' => 'list'
    ],
    [
        'route' => 's',
        'target' => ['\Modules\Brand\Controllers\DefaultController', 'actionToList'],
        'name' => 'tolist0'
    ],
    [
        'route' => '',
        'target' => ['\Modules\Brand\Controllers\DefaultController', 'actionToList'],
        'name' => 'tolist1'
    ],
    [
        'route' => '/',
        'target' => ['\Modules\Brand\Controllers\DefaultController', 'actionToList'],
        'name' => 'tolist2'
    ],

    /** view once brand */
    [
        'route' => '/{slug:sku}',
        'target' => ['\Modules\Brand\Controllers\DefaultController', 'actionView'],
        'name' => 'view:new'
    ],
    [
        'route' => '/{i:id}/{slug:slug}',
        'target' => ['\Modules\Brand\Controllers\DefaultController', 'actionViewOld'],
        'name' => 'view'
    ],
];