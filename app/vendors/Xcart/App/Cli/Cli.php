<?php
namespace Xcart\App\Cli;

class Cli
{
    /**
     * @return bool
     */
    public static function isCli()
    {
        return php_sapi_name() === 'cli';
    }
}