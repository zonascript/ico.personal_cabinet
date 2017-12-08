<?php

return [
    '/purchase' => [
        'name' => 'purchase',
        'callback' => '\Modules\Akara\Controllers\AkaraController:purchase',
    ],
    '/withdraw' => [
        'name' => 'withdraw',
        'callback' => '\Modules\Akara\Controllers\AkaraController:withdraw',
    ],
];
