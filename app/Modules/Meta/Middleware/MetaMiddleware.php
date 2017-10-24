<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 12/06/14.06.2014 19:31
 */

namespace Modules\Meta\Middleware;

use Mindy\Base\Mindy;
use Mindy\Middleware\Middleware;
use Mindy\Http\Request;
use Modules\Meta\Models\Meta;

class MetaMiddleware extends Middleware
{
    public function processRequest(Request $request)
    {
        if ($meta = Meta::objects()->filter(['url' => $request->http->requestUri])->limit(1)->get()) {
            $metaInfo = [
                'title' => $meta->title,
                'keywords' => $meta->keywords,
                'description' => $meta->description,
                'canonical' => $meta->url
            ];

            $controller = Mindy::app()->controller;

            foreach($metaInfo as $key => $value) {
                $controller->set{ucfirst($key)} = $value;
            }
        }
    }
}
