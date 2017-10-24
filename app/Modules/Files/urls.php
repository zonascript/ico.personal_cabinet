<?php

return [
    '/' => [
        'name' => 'index',
        'callback' => '\Modules\Files\Controllers\FilesController:index'
    ],
    '/api/' => [
        'name' => 'api',
        'callback' => '\Modules\Files\Controllers\FilesController:api'
    ],
    '/upload/' => [
        'name' => 'upload',
        'callback' => '\Modules\Files\Controllers\FilesController:upload'
    ],
    /**
     * Actions for FilesField
     */
    '/files_upload/' => [
        'name' => 'files_upload',
        'callback' => '\Modules\Files\Controllers\UploadController:upload'
    ],
    '/files_sort/' => [
        'name' => 'files_sort',
        'callback' => '\Modules\Files\Controllers\UploadController:sort'
    ],
    '/files_delete/' => [
        'name' => 'files_delete',
        'callback' => '\Modules\Files\Controllers\UploadController:delete'
    ],

    '/editor/' => [
        'name' => 'editor',
        'callback' => '\Modules\Files\Controllers\EditorController:index'
    ],
];
