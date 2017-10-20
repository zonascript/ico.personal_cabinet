<?php

namespace Xcart\App\Logger\Handler;

use Monolog\Handler\StreamHandler as MonoStreamHandler;
use Xcart\App\Helpers\Paths;

/**
 * Class StreamHandler
 * @package Xcart\App\Logger
 */
class StreamHandler extends ProxyHandler
{
    /**
     * @var string path to file or proxy to stdout: php://stdout
     */
    public $stream = 'php://stdout';

    public $alias = 'base.log.stdout';

    public $filePermission;

    public function init()
    {
        if ($this->alias) {
            $this->stream = Paths::get($this->alias) . '.log';
        }

        parent::init();
    }

    public function getHandler()
    {
        return new MonoStreamHandler($this->stream, $this->getLevel(), $this->bubble, $this->filePermission);
    }
}

