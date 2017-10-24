<?php

namespace Modules\Menu\Controllers;

use Mindy\Base\Mindy;
use Modules\Core\Controllers\ApiBaseController;
use Modules\Menu\Models\Menu;

class ApiController extends ApiBaseController
{
    public function actionGet($slug)
    {
        $data = Mindy::app()->cache->get('api_menu_' . $slug);
        if (!$data) {
            $model = Menu::objects()->get([
                'slug' => $slug
            ]);

            if ($model === null) {
                $this->error(404);
            }
            $data = iterator_to_array($this->getItems($model));
            Mindy::app()->cache->set('api_menu_' . $slug, $data);
        }
        echo $this->json($data);
        Mindy::app()->end();
    }

    protected function getItems(Menu $model)
    {
        $items = $model->tree()->descendants()->asTree()->all();
        return $this->iterateItems($items);
    }

    protected function iterateItems(array $items)
    {
        foreach ($items as $item) {
            $item['react_route'] = $this->getReactRoute('GET', $item['url']);
            if (isset($item['items']) && !empty($item['items'])) {
                $old = $item['items'];
                $item['items'] = [];
                foreach ($this->iterateItems($old) as $t) {
                    $item['items'][] = $t;
                }
            }
            yield $item;
        }
    }
}