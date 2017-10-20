<?php

return [
    [
        'route' => '/upload',
        'target' => [\Modules\Files\Controllers\UploadController::className(), 'upload'],
        'name' => 'upload'
    ],
    [
        'route' => '/sort',
        'target' => [\Modules\Files\Controllers\UploadController::className(), 'sort'],
        'name' => 'sort'
    ],
    [
        'route' => '/delete',
        'target' => [\Modules\Files\Controllers\UploadController::className(), 'delete'],
        'name' => 'delete'
    ]
];