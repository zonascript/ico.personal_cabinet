<?php
namespace Modules\Core\TemplateLibraries;


use Xcart\App\Template\TemplateLibrary;

class HumanizeLibrary extends TemplateLibrary
{
    /**
     * @name humanize_size
     * @kind modifier
     * @return string
     */
    public static function humanizeSize($size)
    {
        if ($size < 1024) {
            $converted = $size;
            $message = ' B';
        } elseif ($size < pow(1024, 2)) {
            $converted = round($size / 1024);
            $message = ' Kb';
        } elseif ($size < pow(1024, 3)) {
            $converted = round($size / pow(1024, 2));
            $message = ' Mb';
        } elseif ($size < pow(1024, 4)) {
            $converted = round($size / pow(1024, 3));
            $message = ' Gb';
        } else {
            $converted = round($size / pow(1024, 4));
            $message = ' Tb';
        }
        return $converted . $message;
    }
}