<?php

namespace Modules\Mail;

use Mindy\Base\Module;

class MailModule extends Module
{
    /**
     * used for queues
     * @var string
     */
    public $domain = 'example.com';
    /**
     * @var bool
     */
    public $delayedSend = false;
    /**
     * used for queues
     * @var string
     */
    public $from = 'admin@example.com';

    public function getMenu()
    {
        return [
            'name' => $this->getName(),
            'items' => [
                [
                    'name' => self::t('Mail templates'),
                    'adminClass' => 'MailTemplateAdmin',
                ],
                [
                    'name' => self::t('Mail'),
                    'adminClass' => 'MailAdmin',
                ],
                [
                    'name' => self::t('Queue'),
                    'adminClass' => 'QueueAdmin',
                ],
                [
                    'name' => self::t('Subscribes'),
                    'adminClass' => 'SubscribeAdmin',
                ],
                [
                    'name' => self::t('Url checker'),
                    'adminClass' => 'UrlCheckerAdmin',
                ],
            ]
        ];
    }
}
