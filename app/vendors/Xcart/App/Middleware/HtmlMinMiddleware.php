<?php

namespace Xcart\App\Middleware;

use Xcart\App\Request\Request;

class HtmlMinMiddleware extends Middleware
{
    public $spaceless = true;

    public function processView(Request $request, &$output)
    {
        if ($this->spaceless) {
            $output = trim(preg_replace('/>\\s+</', '><', $output));
        } else {
            $output = preg_replace('~>\s+<~', '><', $output);
            $output = preg_replace('/\s\s+/', ' ', $output);
            $i = 0;
            while ($i < 5) {
                $output = str_replace('  ', ' ', $output);
                $i++;
            }
        }
    }
}
