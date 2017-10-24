<?php

/**
 * User: max
 * Date: 27/08/15
 * Time: 17:11
 */

namespace Modules\Mail\Models;

use Exception;
use Mindy\Base\Mindy;
use Mindy\Orm\Fields\BooleanField;
use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\DateTimeField;
use Mindy\Orm\Fields\EmailField;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Fields\HasManyField;
use Mindy\Orm\Fields\TextField;
use Mindy\Orm\Model;
use Modules\Core\Components\ParamsHelper;
use Modules\Mail\MailModule;

class Mail extends Model
{
    public static function getFields()
    {
        return [
            'queue' => [
                'class' => ForeignField::className(),
                'modelClass' => Queue::className(),
                'verboseName' => MailModule::t('Queue'),
                'null' => true
            ],
            'email' => [
                'class' => EmailField::className(),
                'verboseName' => MailModule::t('Email')
            ],
            'subject' => [
                'class' => CharField::className(),
                'verboseName' => MailModule::t('Subject')
            ],
            'message_txt' => [
                'class' => TextField::className(),
                'verboseName' => MailModule::t('Message in plain text mode')
            ],
            'message_html' => [
                'class' => TextField::className(),
                'verboseName' => MailModule::t('Message in html mode')
            ],
            'error' => [
                'class' => TextField::className(),
                'verboseName' => MailModule::t('Error'),
                'null' => true
            ],
            'is_sended' => [
                'class' => BooleanField::className(),
                'verboseName' => MailModule::t('Is sended'),
                'default' => false,
            ],
            'is_read' => [
                'class' => BooleanField::className(),
                'verboseName' => MailModule::t('Is readed'),
                'default' => false,
            ],
            'created_at' => [
                'class' => DateTimeField::className(),
                'verboseName' => MailModule::t('Created at'),
                'autoNowAdd' => true
            ],
            'readed_at' => [
                'class' => DateTimeField::className(),
                'verboseName' => MailModule::t('Readed at'),
                'null' => true
            ],
            'unique_id' => [
                'class' => CharField::className(),
                'length' => 40,
                'verboseName' => MailModule::t('Unique id'),
                'editable' => false,
            ],
            'urls' => [
                'class' => HasManyField::className(),
                'modelClass' => UrlChecker::className(),
                'verboseName' => MailModule::t('Checker urls')
            ]
        ];
    }

    public function __toString()
    {
        return (string)strtr("{email} {created_at}", [
            '{email}' => $this->email,
            '{created_at}' => $this->created_at
        ]);
    }

    public function send()
    {
        $from = Mindy::app()->getModule('Mail')->from;

        $exception = null;

        try {
            $mail = Mindy::app()->mail;

            /** @var \Mindy\Mail\MessageInterface $message */
            $message = $mail->createMessage();

            $html = $this->message_html;
            $text = $this->message_txt;

            $message->setHtmlBody($html);
            if (isset($text)) {
                $message->setTextBody($text);
            } else if (isset($html)) {
                if (preg_match('|<body[^>]*>(.*?)</body>|is', $html, $match)) {
                    $html = $match[1];
                }
                $html = preg_replace('|<style[^>]*>(.*?)</style>|is', '', $html);
                $message->setTextBody(strip_tags($html));
            }

            $sended = $message
                ->setTo($this->email)
                ->setFrom($from)
                ->setSubject($this->subject)
                ->send();

            if ($sended) {
                $this->is_sended = true;
                $this->save(['is_sended']);
            }

        } catch (Exception $e) {
            $exception = $e->getMessage();
            $sended = false;
        }
        return [$sended, $exception];
    }
}
