<?php

return [
    '/{url:w}?{index:[\/]}?' => [
        'name' => 'view',
        'callback' => '\Modules\Pages\Controllers\PageController:view',
    ],
];
