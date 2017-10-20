<?php

namespace Xcart\App\Logger\Handler;

use Monolog\Handler\NativeMailerHandler as MonoNativeMailerHandler;
use Xcart\App\Main\Xcart;

/**
 * Class NativeMailerHandler
 * @package Xcart\App\Logger
 */
class NativeMailerHandler extends ProxyHandler
{
    public $subject = "Logging";

    public $maxColumnWidth = 70;

    public function getHandler()
    {
        $mail = Xcart::app()->mail;
        return new MonoNativeMailerHandler($mail->admins, $this->subject, $mail->defaultFrom, $this->getLevel(), $this->bubble, $this->maxColumnWidth);
    }
}
