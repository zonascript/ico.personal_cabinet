<?php

return [
    '/modules/' => [
        'name' => 'module_list',
        'callback' => '\Modules\Core\Controllers\ModulesController:index'
    ],
    '/modules/install/{name:\w+}' => [
        'name' => 'module_install',
        'callback' => '\Modules\Core\Controllers\ModulesController:install'
    ],
    '/modules/update/{name:\w+}/{version}' => [
        'name' => 'module_update',
        'callback' => '\Modules\Core\Controllers\ModulesController:update'
    ],
    '/modules/{name:\w+}' => [
        'name' => 'module_view',
        'callback' => '\Modules\Core\Controllers\ModulesController:view'
    ],
    '/settings' => [
        'name' => 'settings',
        'callback' => '\Modules\Core\Controllers\SettingsController:index'
    ],
    '/help/online' => [
        'name' => 'help-online',
        'callback' => '\Modules\Core\Controllers\HelpController:index'
    ],
];
