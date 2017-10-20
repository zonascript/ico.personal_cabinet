<?php

namespace Modules\Core\Middleware;

use Xcart\App\Cli\Cli;
use Xcart\App\Controller\FrontendController;
use Xcart\App\Main\Xcart;
use Xcart\App\Middleware\Middleware;

class CacheMiddleware extends Middleware
{
    public $globalCacheTime = 360;

    public function processRequest($request)
    {
        /** @var \Xcart\App\Request\HttpRequest $request */

        if (Cli::isCli() == false && !defined('APP_DEBUG')) {
            if (!headers_sent()) {

                $match = Xcart::app()->router->match($request->getUrl(), $request->getMethod());

                if ($cacheTime = $this->getCacheTime($match)) {
                    if ($a_output = Xcart::app()->cache->get($this->getCacheKey($request, $match))) {
                        list($output, $headers, $etag, $modTime) = $a_output;

                        if ($request->getHeaderValue('IF_NONE_MATCH') == "\"{$etag}\"") {
                            header("HTTP/1.1 304 Not Modified");
                            Xcart::app()->end();
                        }

                        foreach ($headers as $header) {
                            header($header);
                        }

                        $this->setCacheHeaders($modTime, $cacheTime, $etag);

                        echo $output;
                        Xcart::app()->end();
                    }
                }
            }
        }
    }

    public function processView($request, &$output)
    {
        if (Cli::isCli() == false) {

            /** @var \Xcart\App\Request\HttpRequest $request */
            $match = Xcart::app()->router->match($request->getUrl(), $request->getMethod());

            if ($cacheTime = $this->getCacheTime($match)) {

                $headers = array_filter(headers_list(), function($header) {

                    if (!preg_match('/(X-Powered-By|Set-Cookie)/', $header)) {

                        return $header;
                    }
                });

                $data = [$output, $headers];
                $etag = md5(serialize($data));
                $modTime = gmdate("D, d M Y H:i:s", time());
                $data[] = $etag;
                $data[] = $modTime;

                $this->setCacheHeaders($modTime, $cacheTime, $etag);

                Xcart::app()->cache->set($this->getCacheKey($request, $match), $data, $cacheTime?:null);
            }
        }
    }


    private function getCacheKey($request, $match)
    {
        $advanced = [];

        if (is_array($match['target']) && isset($match['target'][0])) {
            $class = $match['target'][0];

            if ( is_subclass_of($class, FrontendController::className()) ) {
                /** @var FrontendController $controller */
                $controller = new $class($request);
                $advanced = $controller->getAdvancedCacheData();
            }
        }

        /**  @var \Xcart\App\Request\HttpRequest $request*/
        return implode('-', $advanced) . $request->getServerName() .$request->getUrl().$request->getMethod().$request->getQueryString();
    }

    private function getCacheTime($match)
    {
        if (!empty($match['config']) && !empty($match['config']['cache']) && $match['config']['cache'])
        {
            if (!empty($match['config']['cache']['time'])) {
                return $match['config']['cache']['time'];
            }
            else {
                return $this->globalCacheTime;
            }
        }

        return null;
    }


    private function setCacheHeaders($modTime, $lifeTime, $etag)
    {
        if (!headers_sent()) {
            header("Last-Modified: {$modTime} GMT");
            header("Cache-Control: max-age={$lifeTime}");
            header("ETag: \"{$etag}\"");
        }
    }
}
