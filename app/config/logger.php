<?php
return [
    'class' => '\\Xcart\\App\\Logger\\LoggerManager',
    'handlers' => [
        'default' => [
            'class' => '\\Xcart\\App\\Logger\\Handler\\RotatingFileHandler' ,
            'level' => defined('APP_DEBUG') ? "DEBUG" : "ERROR",
            'alias' => 'base.runtime.log.err',
            'formatter' => 'log'
        ],
        'sql' => [
            'class' => '\\Xcart\\App\\Logger\\Handler\\RotatingFileHandler',
            'level' =>  "ERROR",
            'alias' => 'base.log.sql'
        ],
//        'error' => [
//            'class' => '\\Xcart\\App\\Logger\\Handler\\RotatingFileHandler',
//            'level' =>  "ERROR",
//            'alias' => 'base.log.err',
//            'formatter' => 'log'
//        ],
        'null' => [
            'class' => '\\Xcart\\App\\Logger\\Handler\\NullHandler',
            'level' => 'ERROR'
        ],
        'console' => [
            'class' => '\\Xcart\\App\\Logger\\Handler\\StreamHandler',
            'formatter' => 'console'
        ],
        'users' => [
            'class' => '\\Xcart\\App\\Logger\\Handler\\RotatingFileHandler',
            'alias' => 'base.logs.users',
            'level' => 'INFO',
            'formatter' => 'users'
        ],
//        'mail_admins' => [
//            'class' => '\\Xcart\\App\\Logger\\Handler\\SwiftMailerHandler',
//        ],
    ],
    'formatters' => [
        'users' => [
            'class' => '\\Xcart\\App\\Logger\\Formatters\\LineFormatter',
            'format' => "%datetime% %message%\\n"
        ],
        'log' => [
            'class' => '\\Xcart\\App\\Logger\\Formatters\\LineFormatter',
//            'allowInlineLineBreaks' => true,
            'includeStacktrace' => true
        ],
        'console' => [
            'class' => '\\Monolog\\Formatter\\LineFormatter',
        ]
    ],
    'loggers' => [
        'users' => [
            'class' => '\\Monolog\\Logger',
            'handlers' => ['users'],
        ],
        'sql' => [
            'class' => '\\Xcart\\App\\Logger\\Logger',
            'handlers' => ['sql']
        ],
//        'error' => [
//            'class' => '\\Xcart\\App\\Logger\\Logger',
//            'handlers' => ['error', 'mail_admins']
//        ],
    ]
];