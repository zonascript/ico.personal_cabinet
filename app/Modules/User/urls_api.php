<?php

return [
    '/password' => [
        'name' => 'change_password',
        'callback' => '\Modules\User\Controllers\UserApiController:changePassword'
    ],
    '/view' => [
        'name' => 'view',
        'callback' => '\Modules\User\Controllers\UserApiController:view'
    ],
    '/list' => [
        'callback' => '\Modules\User\Controllers\UserApiController:list',
        'method' => 'get'
    ],
];
