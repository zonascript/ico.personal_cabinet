<?php
/**
 * Created by max
 * Date: 27/10/15
 * Time: 13:00
 */

namespace Modules\Core\Actions;

use Mindy\Controller\Action;

class Create extends Action
{
    /**
     * @var \Mindy\Form\ModelForm className
     */
    public $form;

    public function prepare()
    {
        /** @var \Mindy\Form\ModelForm $form */
        $form = new $this->form;
        /** @var \Modules\Core\Controllers\ApiBaseController $c */
        $c = $this->getController();
        $r = $c->getRequest();
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
        $data = $this->prepare();
        if ($data['status']) {
            http_response_code(201);
        } else {
            http_response_code(200);
        }
        echo $c->json($data);
    }
}