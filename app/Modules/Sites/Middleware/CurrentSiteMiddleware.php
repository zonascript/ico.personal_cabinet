<?php
namespace Modules\Sites\Middleware;

use Modules\Sites\Helpers\CurrentSiteHelper;
use Xcart\App\Cli\Cli;
use Xcart\App\Main\Xcart;
use Xcart\App\Middleware\Middleware;

class CurrentSiteMiddleware extends Middleware
{
    public function processRequest($request)
    {
        if (!Cli::isCli()) {
            if ($model = CurrentSiteHelper::check($request)) {
                Xcart::app()->getModule('Sites')->setSite($model);
            }
        }
    }
}
