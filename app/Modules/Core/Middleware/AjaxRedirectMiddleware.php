<?php

namespace Modules\Core\Middleware;

use Xcart\App\Cli\Cli;
use Xcart\App\Middleware\Middleware;

class AjaxRedirectMiddleware extends Middleware
{
    public function processResponse($request)
    {
        /** @var \Xcart\App\Request\HttpRequest $request */
        if (!Cli::isCli() && $request->getIsAjax()) {

            if (in_array(http_response_code(), [301, 302, 303])) {
                $headers = headers_list();
                $headers_arr = [];
                foreach($headers as $header){
                    list($key, $value) = explode(':', $header, 2);
                    $headers_arr[trim($key)] = trim($value);
                }

                $path = $headers_arr['Location'];


                header("Location: " . $path, http_response_code());
                header("HTTP/1.1 278 OK", true, 278);
            }
        }
    }
}
