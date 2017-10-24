<?php

namespace Modules\Mail\Commands;

use Exception;
use Mindy\Base\Mindy;
use Mindy\Console\ConsoleCommand;
use Mindy\Utils\RenderTrait;
use Modules\Mail\Models\Mail;
use Modules\Mail\Models\Queue;

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 28/04/14.04.2014 17:53
 */
class MailCommand extends ConsoleCommand
{
    use RenderTrait;

    public function actionSend($to, $from = 'admin@admin.com', $template = "mail/test")
    {
        echo "Sending test mail to " . $to . PHP_EOL;

        $data = [];
        $status = Mindy::app()->mail->compose([
            'text' => $template . ".txt",
            'html' => $template . ".html"
        ], $data)->setTo($to)->setFrom($from)->setSubject("Test mail")->send();
        echo ($status ? "Success" : "Failed") . PHP_EOL;
    }

    public function actionSendDB($to, $from = 'admin@admin.com', $code = "test")
    {
        echo "Sending test mail to " . $to . PHP_EOL;

        $status = Mindy::app()->mail->fromCode($code, $to);
        echo ($status ? "Success" : "Failed") . PHP_EOL;
    }

    public function actionStartQueue($count = 100)
    {
        $domain = Mindy::app()->getModule('Mail')->domain;
        /** @var \Mindy\Query\QueryBuilder $qb */
        $qb = Mindy::app()->db->getDb()->getQueryBuilder();
        $urlManager = Mindy::app()->urlManager;

        $queues = Queue::objects()->filter(['is_running' => false])->incomplete()->all();
        foreach ($queues as $q) {
            $q->started_at = date($qb->dateTimeFormat);
            $q->save(['started_at']);

            foreach ($q->subscribers->batch(100) as $subscribers) {
                foreach ($subscribers as $subscriber) {
                    $data = $subscriber->toArray();

                    $uniqueId = md5($subscriber->email . $q->created_at);
                    $url = $domain . $urlManager->reverse('mail:checker', ['id' => $uniqueId]);
                    $checker = strtr("<img style='width: 1px !important; height: 1px !important;' src='{url}'>", [
                        '{url}' => $url
                    ]);
                    $attributes = [
                        'queue' => $q,
                        'subject' => $this->renderString($q->subject, $data),
                        'message_txt' => $this->renderTemplate($q->template . ".txt", [
                            'content' => $this->renderString($q->message_txt, $data),
                            'uniqueId' => $uniqueId
                        ]),
                        'message_html' => $this->renderTemplate($q->template . ".html", [
                            'content' => $this->renderString($q->message_html, $data),
                            'checker' => $checker,
                            'uniqueId' => $uniqueId
                        ]),
                        'email' => $subscriber->email,
                        'unique_id' => $uniqueId
                    ];

                    $item = new Mail($attributes);
                    if ($item->save() === false) {
                        throw new Exception("Failed to save QueueItem model");
                    }
                }
            }
            $q->is_running = true;
            $q->save(['is_running']);
        }

        $qb = Mindy::app()->db->getDb()->getQueryBuilder();
        $queueItems = Mail::objects()->filter([
            'queue_id__isnull' => false,
            'is_sended' => false
        ])->limit($count)->offset(0)->order(['-id'])->all();

        foreach ($queueItems as $item) {
            list($sended, $error) = $item->send();
            if ($sended == false) {
                $item->error = $error;
                $item->save(['error']);
            }
        }

        $queues = Queue::objects()->filter(['is_running' => true])->incomplete()->all();
        foreach ($queues as $queue) {
            if ($queue->getCount() == 0) {
                $queue->is_complete = true;
                $queue->stopped_at = date($qb->dateTimeFormat);
                $queue->save(['is_complete', 'stopped_at']);
            }
        }
    }
}
