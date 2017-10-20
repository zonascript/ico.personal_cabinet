<?php
namespace Xcart\App\Commands;

use Xcart\App\Helpers\Console;

abstract class Command
{
    public function color($string, $foreground_color = null, $background_color = null) {
        return Console::color($string, $foreground_color, $background_color);
    }

    /**
     * Description for help
     */
    public function getDescription()
    {
        return '';
    }

    abstract public function handle($arguments = []);
}