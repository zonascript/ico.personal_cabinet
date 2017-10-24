<?php

/**
 * Created by max
 * Date: 27/10/15
 * Time: 13:00
 */

namespace Modules\Core\Actions;

use Mindy\Controller\Action;
use Mindy\Helper\Creator;
use Mindy\Pagination\Pagination;

class Index extends Action
{
    /**
     * @var \Mindy\Orm\Model
     */
    public $modelClass;
    /**
     * @var \Closure
     */
    public $filter;
    /**
     * @var \Closure
     */
    public $postProcess;
    /**
     * @var bool
     */
    public $pager = true;

    public function prepare()
    {
        /** @var \Modules\Core\Controllers\ApiBaseController $c */
        $c = $this->getController();

        $modelInstance = Creator::createObject($this->modelClass);
        /** @var \Mindy\Orm\QuerySet|\Mindy\Orm\Manager $qs */
        $qs = $modelInstance::objects();

        $r = $c->getRequest();
        /** @var \Closure $f */
        $f = $this->filter;
        if ($f instanceof \Closure) {
            $qs = $f($r, $qs);
        }

        $limit = (int)$r->get->get('limit');
        if (!empty($limit)) {
            $qs->limit($limit);
        }
        $id = (int)$r->get->get('id');
        if (!empty($id)) {
            $qs->filter(['id' => $id]);
        }
        $order = $r->get->get('order');
        if (!empty($limit)) {
            $qs->order([$order]);
        }

        $qs->asArray();
        if ($this->pager) {
            $pager = new Pagination($qs, [
                'pageKey' => 'page',
                'pageSizeKey' => 'page_size'
            ]);
            $pager->paginate();
            $data = array_merge([
                'status' => true
            ], $pager->toJson());
        } else {
            $data = [
                'status' => true,
                'objects' => $qs->all()
            ];
        }

        /** @var \Closure $f */
        $postProcess = $this->postProcess;
        if ($postProcess instanceof \Closure) {
            $data = $postProcess($data);
        }

        return $data;
    }

    public function run()
    {
        /** @var \Modules\Core\Controllers\ApiBaseController $c */
        $c = $this->getController();
        echo $c->json($this->prepare());
    }
}