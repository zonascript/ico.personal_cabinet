<?php

/**
 * Created by max
 * Date: 27/10/15
 * Time: 13:12
 */

namespace Modules\Core\Actions;

use Mindy\Controller\Action;
use Mindy\Helper\Creator;

class View extends Action
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
     * @var string
     */
    public $key = 'object';

    public function prepare()
    {
        /** @var \Modules\Core\Controllers\ApiBaseController $c */
        $c = $this->getController();

        $modelInstance = Creator::createObject($this->modelClass);
        /** @var \Mindy\Orm\QuerySet $qs */
        $qs = $modelInstance::objects();

        $r = $c->getRequest();
        /** @var \Closure $f */
        $f = $this->filter;
        if ($f instanceof \Closure) {
            $qs = $f($r, $qs);
            $model = $qs->asArray()->get();
        } else {
            $id = (int)$r->get->get('id');
            if (empty($id)) {
                return [
                    'status' => false,
                    'error' => 'Missing id'
                ];
            }
            $model = $qs->asArray()->get(['id' => $id]);
        }

        if ($model === null) {
            return [
                'status' => false,
                'error' => 'Model not found'
            ];
        }

        $data = [
            'status' => true,
            $this->key => $model
        ];

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