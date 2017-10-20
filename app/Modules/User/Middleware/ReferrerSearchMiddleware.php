<?php

namespace Modules\User\Middleware;

use Modules\User\Helpers\SurfingHelper;
use Modules\User\Models\SurfPathModel;
use Xcart\App\Cli\Cli;
use Xcart\App\Middleware\Middleware;

class ReferrerSearchMiddleware extends Middleware
{
    public function processRequest($request)
    {

        if (!Cli::isCli() && $request->getReferrer()) {
            $url = SurfingHelper::getReferUrl();

            $url = parse_url($url);
            if ($url && !empty($url['query'])) {
                $query = \GuzzleHttp\Psr7\parse_query($url['query']);

                if ($query && (!empty($query['q']) || !empty($query['qpvt']))) {

                    $query = !empty($query['q']) ? $query['q'] : $query['qpvt'] ?: null;

                    $request->session->open();

                    SurfingHelper::logSurfPath(['resource_type' => SurfPathModel::GOAL_TYPE_SEARCH, 'additional_data' => $query]);
                }
            }
        }
    }
}
