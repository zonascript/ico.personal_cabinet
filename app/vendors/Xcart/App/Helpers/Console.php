<?php

namespace Xcart\App\Helpers;

use Xcart\App\Cli\Cli;

class Console
{
    const FOREGROUND_BLACK = '0;30';
    const FOREGROUND_DARK_GRAY = '1;30';
    const FOREGROUND_BLUE = '0;34';
    const FOREGROUND_LIGHT_BLUE = '1;34';
    const FOREGROUND_GREEN = '0;32';
    const FOREGROUND_LIGHT_GREEN = '1;32';
    const FOREGROUND_CYAN = '0;36';
    const FOREGROUND_LIGHT_CYAN = '1;36';
    const FOREGROUND_RED = '0;31';
    const FOREGROUND_LIGHT_RED = '1;31';
    const FOREGROUND_PURPLE = '0;35';
    const FOREGROUND_LIGHT_PURPLE = '1;35';
    const FOREGROUND_BROWN = '0;33';
    const FOREGROUND_YELLOW = '1;33';
    const FOREGROUND_LIGHT_GRAY = '0;37';
    const FOREGROUND_WHITE = '1;37';

    const BACKGROUND_BLACK = '40';
    const BACKGROUND_RED = '41';
    const BACKGROUND_GREEN = '42';
    const BACKGROUND_YELLOW = '43';
    const BACKGROUND_BLUE = '44';
    const BACKGROUND_MAGENTA = '45';
    const BACKGROUND_CYAN = '46';
    const BACKGROUND_LIGHT_GRAY = '47';

    protected static $foregroundColors = [];
    protected static $backgroundColors = [];

    public static function __init() {
        // Set up shell colors
        self::$foregroundColors['black'] = self::FOREGROUND_BLACK;
        self::$foregroundColors['blue'] = self::FOREGROUND_BLUE;
        self::$foregroundColors['green'] = self::FOREGROUND_GREEN;
        self::$foregroundColors['cyan'] = self::FOREGROUND_CYAN;
        self::$foregroundColors['red'] = self::FOREGROUND_RED;
        self::$foregroundColors['purple'] = self::FOREGROUND_PURPLE;
        self::$foregroundColors['brown'] = self::FOREGROUND_BROWN;
        self::$foregroundColors['white'] = self::FOREGROUND_WHITE;
        self::$foregroundColors['yellow'] = self::FOREGROUND_YELLOW;
        self::$foregroundColors['light_blue'] = self::FOREGROUND_LIGHT_BLUE;
        self::$foregroundColors['light_green'] = self::FOREGROUND_LIGHT_GREEN;
        self::$foregroundColors['light_cyan'] = self::FOREGROUND_LIGHT_CYAN;
        self::$foregroundColors['light_red'] = self::FOREGROUND_LIGHT_RED;
        self::$foregroundColors['light_purple'] = self::FOREGROUND_LIGHT_PURPLE;
        self::$foregroundColors['light_gray'] = self::FOREGROUND_LIGHT_GRAY;
        self::$foregroundColors['dark_gray'] = self::FOREGROUND_DARK_GRAY;

        self::$backgroundColors['black'] = self::BACKGROUND_BLACK;
        self::$backgroundColors['red'] = self::BACKGROUND_RED;
        self::$backgroundColors['green'] = self::BACKGROUND_GREEN;
        self::$backgroundColors['yellow'] = self::BACKGROUND_YELLOW;
        self::$backgroundColors['blue'] = self::BACKGROUND_BLUE;
        self::$backgroundColors['magenta'] = self::BACKGROUND_MAGENTA;
        self::$backgroundColors['cyan'] = self::BACKGROUND_CYAN;
        self::$backgroundColors['light_gray'] = self::BACKGROUND_LIGHT_GRAY;
    }

    public static function isCli()
    {
        return Cli::isCli();
    }

    public static function color($string, $foreground_color = null, $background_color = null) {
        $colored_string = "";
        
        if (empty(self::$backgroundColors) || empty(self::$foregroundColors)) {
            self::__init();
        }

        if (isset(self::$foregroundColors[$foreground_color])) {
            $colored_string .= "\033[" . self::$foregroundColors[$foreground_color] . "m";
        }
        elseif (!empty($foreground_color)) {
            $colored_string .= "\033[" . $foreground_color . "m";
        }

        if (isset(self::$backgroundColors[$background_color])) {
            $colored_string .= "\033[" . self::$backgroundColors[$background_color] . "m";
        }
        elseif (!empty($background_color)) {
            $colored_string .= "\033[" . $background_color . "m";
        }

        $colored_string .=  $string . "\033[0m";
        return $colored_string;
    }
}