<?php

namespace Modules\Core\Middleware;

use Modules\Core\TemplateLibraries\HumanizeLibrary;
use Xcart\App\Cli\Cli;
use Xcart\App\Main\Xcart;
use Xcart\App\Middleware\Middleware;

class StatisticMiddleware extends Middleware
{

    public function processView($request, &$output)
    {
        /** @var \Xcart\App\Request\HttpRequest $request */
        if (!Cli::isCli() && !$request->getIsAjax()) {
            $cq = Xcart::app()->db->getConnection()->getCountQueries();
            $memory = HumanizeLibrary::humanizeSize(memory_get_usage());
            $time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];


            $output .= <<<HTML
<section class="statistic">
    <div class="row">
        <div class="column large-3">
            Memory used: {$memory}
        </div>
        <div class="column large-3">
            Query count: {$cq}
        </div>
        <div class="column large-3">
            Speed: {$time}s
        </div>
        <div class="column large-3">
            <!--Speed: {$time}s-->
        </div>
    </div>
</section>
HTML;
        }
    }
}
