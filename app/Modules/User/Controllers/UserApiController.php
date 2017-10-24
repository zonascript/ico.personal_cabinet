<?php

/**
 * User: max
 * Date: 05/08/15
 * Time: 18:15
 */

namespace Modules\User\Controllers;

use Mindy\Base\Mindy;
use Mindy\Pagination\Pagination;
use Modules\Core\Controllers\ApiBaseController;
use Modules\User\Forms\ChangePasswordForm;
use Modules\User\Models\User;
use Modules\User\UserModule;

class UserApiController extends ApiBaseController
{
    protected $unsetFields = [
        'password',
        'activation_key',
        'hash_type'
    ];

    public function actionChangePassword()
    {
        $user = Mindy::app()->getUser();
        $form = new ChangePasswordForm();
        $form->setModel($user);

        $r = $this->getRequest();
        if ($r->getIsPost() && $form->populate($_POST)->isValid() && $form->save()) {
            echo $this->json([
                'status' => true,
                'message' => UserModule::t('Password changed')
            ]);
            $this->end();
        } else {
            echo $this->json([
                'errors' => $form->getJsonErrors()
            ]);
            $this->end();
        }
    }

    public function actionList()
    {
        $qs = User::objects()->asArray();

        $request = $this->getRequest();
        if ($request->get->get('for_select', false)) {
            $qs->select([
                'value' => 'id',
                'label' => 'username'
            ]);
        } else {
            $select = array_diff(User::getMeta()->getAttributes(), $this->unsetFields);
            $qs->select($select);
        }

        if ($request->get->get('pager', true) == false) {
            $objects = $qs->all();
            echo $this->json([
                'status' => true,
                'objects' => $objects
            ]);
        } else {
            $pager = new Pagination($qs, [
                'pageKey' => 'page',
                'pageSizeKey' => 'page_size'
            ]);
            $pager->paginate();
            echo $this->json(array_merge([
                'status' => true
            ], $pager->toJson()));
        }
    }

    public function actionView()
    {
        $id = (int)$this->getRequest()->get->get('id');
        if (empty($id)) {
            echo $this->json([
                'error' => true,
                'message' => 'Missing id',
            ]);
            $this->end();
        }

        $model = User::objects()->asArray()->get(['id' => $id]);
        if ($model === null) {
            echo $this->json([
                'error' => true,
                'message' => 'User not found',
            ]);
            $this->end();
        }

        foreach ($this->unsetFields as $unset) {
            unset($model[$unset]);
        }

        echo $this->json([
            'status' => true,
            'user' => $model
        ]);
        $this->end();
    }
}
