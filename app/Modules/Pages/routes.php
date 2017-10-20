<?php
return [
    [
        'route' => '/{slug:url}',
        'target' => ['\Modules\Pages\Controllers\PageController', 'actionView'],
        'name' => 'view'
    ],

];