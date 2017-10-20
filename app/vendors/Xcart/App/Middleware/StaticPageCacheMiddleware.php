<?php

namespace Xcart\App\Middleware;

use Xcart\App\Helpers\Paths;
use Xcart\App\Main\Xcart;
use Xcart\App\Request\Request;

class StaticPageCacheMiddleware extends Middleware
{
    /**
     * @var int default cache timeout 5 min
     */
    public $timeout = 300000;

    public function processRequest(Request $request)
    {
        $fileName = ($request->path == '/' ? 'index' : basename($request->path)) . '.html';
        $dir = str_replace('/', '.', ltrim(dirname($request->path), '/'));
        $cachePath = Paths::get('base.runtime.static.' . $request->host . '.' . $dir);
        $path = $cachePath . DIRECTORY_SEPARATOR . $fileName;
        if (file_exists($path)) {
            echo file_get_contents($path);
            Xcart::app()->end();
        }
    }

    public function processView(Request $request, &$output)
    {
        $fileName = ($request->path == '/' ? 'index' : basename($request->path)) . '.html';
        $dir = str_replace('/', '.', ltrim(dirname($request->path), '/'));
        $cachePath = Paths::get('base.runtime.static.' . $request->host . '.' . $dir);
        if (!is_dir($cachePath)) {
            @mkdir($cachePath, 0777, true);
        }
        $path = $cachePath . DIRECTORY_SEPARATOR . $fileName;
        if (!file_exists($path) || (time() - fileatime($path)) > $this->timeout) {
            file_put_contents($cachePath . DIRECTORY_SEPARATOR . $fileName, $output);
        }
    }
}
