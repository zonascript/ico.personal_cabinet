<?php

namespace Modules\Main\Middleware;

use Xcart\App\Cli\Cli;
use Xcart\App\Middleware\Middleware;

class RedirectConfirmationMiddleware extends Middleware
{
    public function processRequest($request)
    {
        //index.php?pageid=42&s=B-59517742e001197a6d251590c73ffce5&o=97036&m=36
        /** @var \Xcart\App\Request\HttpRequest $request */
        if (!Cli::isCli()) {

            $patch = $request->getPath();
            $patch = strtolower($patch);

            if (in_array($patch, ['/', '', '/index.php'])
                &&  $request->get->has('pageid') && $request->get->get('pageid') == 42
                && $request->get->has('s')
                && $request->get->has('o')
                && $request->get->has('m')
            ) {
                $request->redirect('main:receipt:confirmation', [], 302, $request->getQueryArray());
            }
        }
    }
}
