<?php

namespace Modules\Pages\Controllers;

use Mindy\Base\Mindy;
use Modules\Core\Controllers\ApiBaseController;
use Modules\Pages\Models\Page;

class PageApiController extends ApiBaseController
{
    public function actionView()
    {
        $url = $this->getRequest()->get->get('url');
        if (empty($url)) {
            echo $this->json([
                'status' => false,
                'error' => 'Url is missing'
            ]);
            $this->end();
        }

        $cache = Mindy::app()->cache;
        $cacheKey = Page::CACHE_PREFIX . $url;
        $data = $cache->get($cacheKey);

        if (!$data) {
            $model = Page::objects()->asArray()->get([
                'url' => $url
            ]);

            if ($model === null) {
                echo $this->json([
                    'status' => false,
                    'error' => 'Model not found'
                ]);
                $this->end();
            }

            $data = [
                'status' => true,
                'model' => $model
            ];

            $cache->set($cacheKey, $data, 3600);
        }

        echo $this->json($data);
    }
}