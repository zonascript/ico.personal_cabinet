<?php

namespace Xcart\App\Logger\Handler;

use Monolog\Handler\SwiftMailerHandler as MonoSwiftMailerHandler;
use Xcart\App\Main\Xcart;

/**
 * Class SwiftMailerHandler
 * @package Xcart\App\Logger
 */
class SwiftMailerHandler extends ProxyHandler
{
    public function getHandler()
    {
        $mail = Xcart::app()->mail;
        $mailer = $mail->getSwiftMailer();
        $message = $mail->compose()->getSwiftMessage();
        return new MonoSwiftMailerHandler($mailer, $message, $this->getLevel(), $this->bubble);
    }
}
