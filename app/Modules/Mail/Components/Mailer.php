<?php

namespace Modules\Mail\Components;

use Swift_Mailer;
use Swift_MailTransport;
use Swift_Message;
use Swift_SendmailTransport;
use Swift_SmtpTransport;
use Xcart\App\Cli\Cli;
use Xcart\App\Main\Xcart;
use Xcart\App\Template\Renderer;

class Mailer
{
    use Renderer;

    // Swift_SendmailTransport
    const MODE_SENDMAIL = 'sendmail';

    // Swift_MailTransport
    const MODE_MAIL = 'mail';

    // Swift_SmtpTransport
    const MODE_SMTP = 'smtp';

    public $mode = 'mail';

    public $config = [];

    public $defaultFrom;

    /**
     * Example: "http://example.com", "https://10.12.231.43:8000"
     * @var string
     */
    public $hostInfo;

    protected $_transport;

    protected $_mailer;

    public function getTransport()
    {
        if (!$this->_transport) {
            $this->_transport = $this->initTransport();
        }
        return $this->_transport;
    }

    protected function initTransport()
    {
        $config = $this->config;
        if ($this->mode == self::MODE_SENDMAIL) {
            $command = isset($config['command']) ? $config['command'] : '/usr/sbin/sendmail -bs';
            return new Swift_SendmailTransport($command);
        }
        elseif ($this->mode == self::MODE_SMTP) {
            $security = isset($config['security']) ? $config['security'] : null;
            $transport = new Swift_SmtpTransport($config['host'], $config['port'], $security);
            $transport->setUsername($config['username']);
            $transport->setPassword($config['password']);
            return $transport;
        }
        elseif ($this->mode == self::MODE_MAIL) {
            $extraParams = isset($config['extraParams']) ? $config['extraParams'] : '-f%s';
            return new Swift_MailTransport($extraParams);
        }
        return null;
    }


    protected function getMailer()
    {
        if (!$this->_mailer) {
            $this->_mailer = Swift_Mailer::newInstance($this->getTransport());
        }
        return $this->_mailer;
    }

    public function raw($to, $subject, $body, $additional = [], $attachments = [])
    {
        $message = new Swift_Message();
        $message->setTo($to);
        $message->setSubject($subject);
        $message->setBody($body, 'text/html');

        if (isset($additional['from'])) {
            $message->setFrom($additional['from']);
            $message->setSender($additional['from']);
        }
        elseif ($this->defaultFrom) {
            $default = $this->defaultFrom;

            if (!Cli::isCli()) {
                $default = strtr($this->defaultFrom, ['{domain}' => $this->getDomain()]);
            }

            $message->setFrom($default);
            $message->setSender($default);
        }

        return $this->getMailer()->send($message);
    }

    public function template($to, $subject, $template, $data = [], $additional = [], $attachments = [])
    {
        $data = array_merge($data, [
            'hostInfo' => $this->hostInfo
        ]);
        $body = self::renderTemplate($template, $data);
        return $this->raw($to, $subject, $body, $additional, $attachments);
    }

    public function getDomain()
    {
        $domain = Xcart::app()->request->getHost();
        if ( strpos($domain, ':') !== false ){
            $domain = substr($domain, 0, strpos($domain, ':'));
        }
        return $domain;
    }
    
    public function getHostInfo()
    {
        if (!$this->hostInfo) {
            $this->hostInfo = Xcart::app()->request->getHostInfo();
        }
        return $this->hostInfo;
    }
}