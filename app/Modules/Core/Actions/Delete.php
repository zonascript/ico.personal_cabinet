<?php

/**
 * Created by max
 * Date: 27/10/15
 * Time: 13:12
 */

namespace Modules\Core\Actions;

use Mindy\Controller\Action;
use Mindy\Helper\Creator;

class Delete extends Action
{
    /**
     * @var \Mindy\Orm\Model
     */
    public $modelClass;
    /**
     * @var \Closure
     */
    public $filter;

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
        /** @var \Mindy\Orm\Model $model */
        if ($f instanceof \Closure) {
            $model = $f($r, $qs);
            $result = $model->delete();
        } else {
            $id = $r->get->get('id');
            if (is_array($id)) {
                $id = array_filter($id, function (&$item) {
                    return (int)$item;
                });
                $models = $qs->filter(['id__in' => $id])->all();
                $result = count($models);
                foreach ($models as $model) {
                    $model->delete();
                }
            } else {
                $id = (int)$id;
                if (empty($id)) {
                    return [
                        'status' => false,
                        'error' => 'Missing id'
                    ];
                }
                $model = $qs->get(['id' => $id]);
                if ($model === null) {
                    return [
                        'status' => false,
                        'error' => 'Model not found'
                    ];
                }
                $result = $model->delete();
            }
        }

        return [
            'status' => (bool)$result
        ];
    }

    public function run()
    {
        /** @var \Modules\Core\Controllers\ApiBaseController $c */
        $c = $this->getController();
        echo $c->json($this->prepare());
    }
}