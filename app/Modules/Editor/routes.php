<?php

return [
    [
        'route' => '/index',
        'target' => [\Modules\Editor\Controllers\EditorController::className(), 'index'],
        'name' => 'index'
    ],
    [
        'route' => '/upload',
        'target' => [\Modules\Editor\Controllers\EditorController::className(), 'upload'],
        'name' => 'upload'
    ],
    [
        'route' => '/changed',
        'target' => [\Modules\Editor\Controllers\EditorController::className(), 'changed'],
        'name' => 'changed'
    ],
    [
        'route' => '/api',
        'target' => [\Modules\Editor\Controllers\EditorController::className(), 'api'],
        'name' => 'api'
    ],
];