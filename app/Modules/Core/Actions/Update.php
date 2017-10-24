<?php

/**
 * Created by max
 * Date: 27/10/15
 * Time: 13:03
 */

namespace Modules\Core\Actions;

use Mindy\Controller\Action;
use Mindy\Helper\Creator;

class Update extends Action
{
    /**
     * @var \Mindy\Form\ModelForm className
     */
    public $form;
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
        if ($f instanceof \Closure) {
            $model = $f($r, $qs);
        } else {
            $id = (int)$r->get->get('id');
            if (empty($id)) {
                return [
                    'status' => false,
                    'error' => 'Missing id'
                ];
            }
            $model = $qs->get(['id' => $id]);
        }

        if ($model === null) {
            return [
                'status' => false,
                'error' => 'Model not found'
            ];
        }

        /** @var \Mindy\Form\ModelForm $form */
        $form = new $this->form([
            'instance' => $model
        ]);

        if ($form->setAttributes($r->post->all())->isValid() && $form->save()) {
            return [
                'id' => (int)$form->getInstance()->pk,
                'status' => true,
                'errors' => []
            ];
        } else {
            return [
                'status' => false,
                'errors' => $form->getErrors()
            ];
        }
    }

    public function run()
    {
        /** @var \Modules\Core\Controllers\ApiBaseController $c */
        $c = $this->getController();
        http_response_code(200);
        echo $c->json($this->prepare());
    }
}