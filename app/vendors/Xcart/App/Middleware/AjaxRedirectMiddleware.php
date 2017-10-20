<?php

namespace Xcart\App\Middleware;

use Xcart\App\Request\HttpRequest;
use Xcart\App\Request\Request;

class AjaxRedirectMiddleware extends Middleware
{
    public function processResponse(Request $request)
    {
        /** @var HttpRequest $request */
        if ($request->getIsPost() && $request->getIsAjax()) {
            header("Location: " . $request->getPath());
            header("HTTP/1.1 278 OK", true, 278);
        }
    }
}
