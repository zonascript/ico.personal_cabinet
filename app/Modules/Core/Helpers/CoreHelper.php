<?php

namespace Modules\Core\Helpers;


class CoreHelper
{
    public static function stripTags($content)
    {
        $content =  preg_replace('/<[^>]*>/', ' ', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        return trim($content);
    }
}