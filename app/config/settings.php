<?php
(defined('DS')?:define('DS', DIRECTORY_SEPARATOR));
//defined('APP_DEBUG')?:define('APP_DEBUG', true);

$local_config = __DIR__ . DS .'settings_local.php';

return array_replace_recursive([
   'name' => 'Xcart',
   'exit_on_end' => true,
   'paths' => [
       'base' => realpath(implode(DS, [__DIR__, '..'])),
       'www' => realpath(implode(DS, [__DIR__, '..', '..', 'www'])),
       'media' => realpath(implode(DS, [__DIR__, '..', '..', 'www', 'media'])),
   ],
   'modules' => include __DIR__ . DS . 'modules.php',
   'locale' => [
       'language' => 'en',
       'sourceLanguage' => 'en',
       'charset' => 'utf-8',
   ],
   'globals' => [
        'blowfish_key' => '8d5db63ada15e11643a0b1c3477c2c5c',
        'blowfish' => new \ctBlowfish(),
   ],
   'components' => [
       'db' => [
           'class' => '\\Xcart\\App\\Orm\\ConnectionManager',
           'connections' => [
               'default' => [
                   'memory' => true,
                   'autoCommit' => true,
                   'driver' => 'pdo_mysql',
                   'dbname' => 'akara_lk',
                   'host' => '127.0.0.1',
                   'user' => 'ico',
                   'password' => '',
                   'charset'  => 'utf8',
                   'mapping' => [
                       'enum' => 'string'
                   ],
                   'cache' => [
                       'class' => '\\Xcart\\App\\Orm\\Cache\\FilesystemCache',
                       'directory' => 'base.runtime.query_cache'
                   ]
               ]
           ]
       ],
       'errorHandler' => [
           'class' => '\\Xcart\\App\\Main\\ErrorHandler',
           'useTemplate' => true,
           'debug' => false,
           'errHandler' => true,
           'ignoreDeprecated' => true,
       ],
       'event' => [
           'class' => '\\Xcart\\App\\Event\\EventManager',
           'events' => include __DIR__ . DS .  'events.php'
       ],

       'finder' => ['class' => '\Xcart\App\Finder\FinderFactory'],
       'auth' => ['class' => 'Modules\User\Components\Auth'],
       'breadcrumbs' => ['class' => 'Xcart\App\Components\Breadcrumbs'],
       'flash' => ['class' => '\Xcart\App\Components\Flash'],

       'logger' => include __DIR__. DS . 'logger.php',

       'middleware' => [
           'class' => '\\Xcart\\App\\Middleware\\MiddlewareManager',
           'middleware' => [
               'RedirectConfirmationMiddleware' => [
                   'class' => '\Modules\Main\Middleware\RedirectConfirmationMiddleware',
               ],
               'BotsMiddleware' => [
                   'class' => '\\Modules\\User\\Middleware\\BotsMiddleware',
               ],
               'AjaxRedirectMiddleware' => [
                   'class' => '\\Modules\\Core\\Middleware\\AjaxRedirectMiddleware',
               ],
               'AutoCacheMiddleware' => [
                   'class' => '\\Modules\\Core\\Middleware\\CacheMiddleware',
               ],
//               'ReferrerSearch' => [
//                   'class' => '\\Modules\\User\\Middleware\\ReferrerSearchMiddleware'
//               ],
           ],
       ],
       'request' => [
           'class' => '\\Xcart\\App\\Request\\RequestManager',
           'httpRequest' => [
               'class' => '\\Xcart\\App\\Request\\HttpRequest',
               'session' => [
                   'class' => '\\Modules\\User\\Components\\XcartSession',
                   'session_key' => 's3'
               ]
           ],
           'cliRequest' => [
               'class' => '\\Xcart\\App\\Request\\CliRequest',
           ]
       ],
       'router' => [
           'class' => '\\Xcart\\App\\Router\\Router',
           'pathRoutes' => 'base.config.routes'
       ],
       'template' => [
           'class' => '\\Xcart\\App\\Template\\TemplateManager',
           'forceCompile' => false,
           'forceInclude' => true,
           'autoReload' => false,
           'autoEscape' => false,
       ],

       'storage' => [
           'class' => '\\Xcart\\App\\Storage\\Storage',
           'default' => 'local',
           'adapters' => [
               'local' => [
                   'class' => '\\Xcart\\App\\Storage\\Adapters\\LocalAdapter',
                   'root' => 'media',
               ],
           ],
       ],
       'cache' => [
           'class' => '\\Xcart\\App\\Cache\\Cache',
           'saveInMemory' => true,
           'memoryDriver' => 'memory',
           'drivers' => [
               'default' =>  [
                   'class' => '\\Xcart\\App\\Cache\\Drivers\\File'
               ],
               'memory' =>  [
                   'class' => '\\Xcart\\App\\Cache\\Drivers\\Memory',
                   'numCacheQuery' => 30,
               ]
           ]
       ],
       'mail' => [
           'class' => '\\Modules\\Mail\\Components\\Mailer',
//           'defaultFrom' => 'robot@{domain}',
           'defaultFrom' => 'robot@s3stores.com',
       ],
   ],
   'autoloadComponents' => [
       'errorHandler',
       'logger',
   ]
],  (is_file($local_config)) ? include $local_config : []);