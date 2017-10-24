<?php

return [
    '/unsubscribe/{email}/{token}' => [
        'name' => 'unsubscribe',
        'callback' => '\Modules\Mail\Controllers\SubscribeController:unsubscribe'
    ],
    '/subscribe/' => [
        'name' => 'subscribe',
        'callback' => '\Modules\Mail\Controllers\SubscribeController:subscribe'
    ],
    '/checker/{uniqueId:c}' => [
        'name' => 'checker',
        'callback' => '\Modules\Mail\Controllers\MailCheckerController:index'
    ],
];
