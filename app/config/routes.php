<?php
return [
    [
        'route' => '',
        'path' => 'Modules.Main.routes',
        'namespace' => 'main',
        'config' => [
            'cache' => [
                'time' => 360,
            ]
        ]
    ],
    [
        'route' => '',
        'path' => 'Modules.Product.routes',
        'namespace' => 'catalog',
        'config' => [
            'cache' => [
                'time' => 360,
            ]
        ]
    ],
    [
        'route' => '/admin',
        'path' => 'Modules.Admin.routes',
        'namespace' => 'admin',
    ],
    [
        'route' => '/admin/editor',
        'path' => 'Modules.Editor.routes',
        'namespace' => 'editor'
    ],

    [
        'route' => '/admin/files',
        'path' => 'Modules.Files.routes',
        'namespace' => 'files'
    ],
    [
        'route' => '',
        'path' => 'Modules.Pages.routes',
        'namespace' => 'page',
        'config' => [
            'cache' => [
                'time' => 360,
            ]
        ]
    ],
];