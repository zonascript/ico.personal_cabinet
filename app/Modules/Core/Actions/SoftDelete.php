<?php

/**
 * Created by max
 * Date: 27/10/15
 * Time: 13:12
 */

namespace Modules\Core\Actions;

use Mindy\Controller\Action;
use Mindy\Helper\Creator;

class SoftDelete extends Action
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

        $id = (int)$r->get->get('id');
        if (empty($id)) {
            return [
                'status' => false,
                'error' => 'Missing id'
            ];
        }

        $reason = $r->delete->get('reason');
        if (empty($reason)) {
            echo $c->json([
                'status' => false,
                'error' => 'Missing reason',
            ]);
            $c->end();
        }

        /** @var \Closure $f */
        $f = $this->filter;
        /** @var \Mindy\Orm\Model $model */
        if ($f instanceof \Closure) {
            $qs = $f($r, $qs);
        }
        $model = $qs->get(['id' => $id]);

        if ($model === null) {
            return [
                'status' => false,
                'error' => 'Model not found'
            ];
        }

        $model->is_deleted = true;
        $model->delete_reason = $reason;
        $qb = $model->getDb()->getQueryBuilder();
        $model->deleted_at = $qb->convertToDateTime(time());

        return [
            'status' => (bool)$model->save(['is_deleted', 'delete_reason'])
        ];
    }

    public function run()
    {
        /** @var \Modules\Core\Controllers\ApiBaseController $c */
        $c = $this->getController();
        echo $c->json($this->prepare());
    }
}