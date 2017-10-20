<?php
namespace Modules\Meta\Middleware;

use Modules\Meta\Models\Meta;
use Xcart\App\Main\Xcart;
use Xcart\App\Middleware\Middleware;

class MetaMiddleware extends Middleware
{
    public function processRequest($request)
    {
        if ($meta = Meta::objects()->filter(['url' => $request->http->requestUri])->limit(1)->get()) {
            $metaInfo = [
                'title' => $meta->title,
                'keywords' => $meta->keywords,
                'description' => $meta->description,
                'canonical' => $meta->url
            ];

            $controller = Xcart::app()->controller;

            foreach($metaInfo as $key => $value) {
                $controller->set{ucfirst($key)} = $value;
            }
        }
    }
}
