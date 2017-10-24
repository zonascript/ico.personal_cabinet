<?php

return [
    '/' => [
        'name' => 'index',
        'callback' => '\Modules\Translate\Controllers\TranslateController:index'
    ],
    '/process' => [
        'name' => 'process',
        'callback' => '\Modules\Translate\Controllers\TranslateController:process'
    ],
    '/lang/{lang:\w+}' => [
        'name' => 'language',
        'callback' => '\Modules\Translate\Controllers\TranslateController:language'
    ],
];
