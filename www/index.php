<?php
error_reporting(E_ALL & ~E_NOTICE);

date_default_timezone_set('EST'); //Magic;

require_once '../app/vendors/components/autoload.php';

$config = include '../app/config/settings.php';

use Xcart\App\Main\Xcart;

Xcart::init($config);
Xcart::app()->run();