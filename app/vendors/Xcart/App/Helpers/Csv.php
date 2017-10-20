<?php

namespace Mindy\Helper;

use Closure;
use Goodby\CSV\Import\Standard\Interpreter;
use Goodby\CSV\Import\Standard\Lexer;
use Goodby\CSV\Import\Standard\LexerConfig;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;

/**
 * Class Csv
 * @package Mindy\Helper
 */
class Csv
{
    use Configurator, Accessors;

    /**
     * @var string csv delimeter
     */
    public $delimeter = ";";
    /**
     * @var string convert from charset
     */
    public $fromCharset = "cp1251";
    /**
     * @var string convert to charset
     */
    public $toCharset = "UTF-8";

    /**
     * @param $path string absolute path to file
     * @param $closure \Closure
     * @void
     */
    public function parse($path, Closure $closure)
    {
        $config = new LexerConfig();
        $config->setDelimiter($this->delimeter);
        $config->setFromCharset($this->fromCharset);
        $config->setToCharset($this->toCharset);

        $lexer = new Lexer($config);
        $interpreter = new Interpreter();
        $interpreter->addObserver($closure);
        $lexer->parse($path, $interpreter);
    }
}
