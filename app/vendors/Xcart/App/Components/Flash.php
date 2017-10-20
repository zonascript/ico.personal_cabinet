<?php

namespace Xcart\App\Components;

use Xcart\App\Helpers\ClassNames;
use Xcart\App\Helpers\SmartProperties;
use Xcart\App\Main\Xcart;

class Flash
{
    use SmartProperties, ClassNames;

    const SESSION_KEY = 'FLASH';

    public function success($message)
    {
        $this->add($message, 'success');
    }

    public function error($message)
    {
        $this->add($message, 'error');
    }

    public function info($message)
    {
        $this->add($message, 'info');
    }
    
    /**
     * @param $message
     * @param string $type "success"|"error"|"info"
     */
    public function add($message, $type = 'success')
    {
        $messages = $this->getMessages();
        $messages[] = [
            'message' => $message,
            'type' => $type
        ];
        $this->setMessages($messages);
    }

    public function getMessages()
    {
        return array_merge(Xcart::app()->request->session->get(self::SESSION_KEY, []), []);
    }

    public function setMessages($messages = [])
    {
        Xcart::app()->request->session->add(self::SESSION_KEY, $messages);
    }

    public function clearMessages()
    {
        Xcart::app()->request->session->remove(self::SESSION_KEY);
    }

    public function read()
    {
        $messages = $this->getMessages();
        $this->clearMessages();
        return $messages;
    }
}