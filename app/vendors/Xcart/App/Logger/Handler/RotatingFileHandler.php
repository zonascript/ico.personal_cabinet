<?php

namespace Xcart\App\Logger\Handler;

use Monolog\Handler\RotatingFileHandler as MonoRotatingFileHandler;

/**
 * Class RotatingFileHandler
 * @package Xcart\App\Logger
 */
class RotatingFileHandler extends StreamHandler
{
    /**
     * @var int The maximal amount of files to keep (0 means unlimited)
     */
    public $maxFiles = 5;

    public function getHandler()
    {
        return new MonoRotatingFileHandler(
            $this->stream,
            $this->maxFiles,
            $this->getLevel(),
            $this->bubble,
            $this->filePermission
        );
    }
}
