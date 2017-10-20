<?php

namespace Xcart\App\Logger\Formatters;

use Monolog\Formatter\LineFormatter as MonologLineFormatter;
use Xcart\App\Helpers\Accessors;
use Xcart\App\Traits\Configurator;

/**
 * Class LineFormatter
 * @package Mindy\Logger
 */
class LineFormatter
{
    use Accessors, Configurator;

    /**
     * @var \Monolog\Formatter\LineFormatter
     */
    public $formatter;

    public $allowInlineLineBreaks = false;
    public $includeStacktrace = false;

    public $format = null;

    public $dateFormat = null;

    public function init()
    {
        $this->formatter = new MonologLineFormatter($this->format, $this->dateFormat, $this->allowInlineLineBreaks);
        $this->formatter->includeStacktraces($this->includeStacktrace);
    }
}
