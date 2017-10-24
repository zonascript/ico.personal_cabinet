<?php

return [
    '/{slug:c}' => [
        'name' => 'get',
        'callback' => '\Modules\Menu\Controllers\ApiController:get',
    ]
];
