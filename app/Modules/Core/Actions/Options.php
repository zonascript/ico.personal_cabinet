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

class Options extends Action
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
        /** @var \Mindy\Orm\QuerySet|\Mindy\Orm\Manager $qs */
        $qs = $modelInstance::objects();

        $r = $c->getRequest();
        /** @var \Closure $f */
        $f = $this->filter;
        if ($f instanceof \Closure) {
            $qs = $f($r, $qs);
        }

        $qs->asArray();
        return [
            'status' => true,
            'options' => $qs->all()
        ];
    }

    public function run()
    {
        /** @var \Modules\Core\Controllers\ApiBaseController $c */
        $c = $this->getController();
        echo $c->json($this->prepare());
    }
}