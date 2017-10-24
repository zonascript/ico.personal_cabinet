<?php

return [
    '/login' => [
        'name' => 'login',
        'callback' => '\Modules\User\Controllers\AuthApiController:login',
        'method' => 'post'
    ],
    '/logout' => [
        'name'     => 'logout',
        'callback' => '\Modules\User\Controllers\AuthApiController:logout',
    ],
    '/registration' => [
        'name' => 'registration',
        'callback' => '\Modules\User\Controllers\AuthApiController:registration',
        'method' => 'post'
    ],
    '/registration/activate/{key}' => [
        'name' => 'registration_activation',
        'callback' => '\Modules\User\Controllers\AuthApiController:activate',
    ],
    '/recover/{key}?' => [
        'name' => 'recover',
        'callback' => '\Modules\User\Controllers\RecoverApiController:recover'
    ],
];