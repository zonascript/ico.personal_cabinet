<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 11.01.2017
 * Time: 15:15
 */

namespace Xcart\App\Controller;

use Xcart\App\Orm\Model;
use Xcart\App\Traits\SmartyRenderTrait;

class PrototypeAdminController extends Controller
{
    use SmartyRenderTrait;

    /**
     * @var Model
     */
    public $model;

    public function renderInternal($view, $params)
    {
        return $this->renderSmarty("admin/home.tpl", [
            'single_mode' => true,
            'main'        => 'raw_html',
            'content'     =>  $this->render($view, $params),
        ]);
    }

    public function redirectNext($data, $form)
    {
        list($route, $params) = $this->getNextRoute($data, $form);
        if ($route && $params) {
            $this->redirect($route, $params);
        }
    }

    public function getNextRoute(array $data, $form)
    {
        $model = $form->getInstance();
        if (array_key_exists('save_continue', $data)) {
            return ['admin:update', [
                'module' => $this-> getModule()->getId(),
                'adminClass' => $this->classNameShort(),
                'id' => $model->pk
            ]];
        } else if (array_key_exists('save_create', $data)) {
            return ['admin:create', [
                'module' => $this->getModule()->getId(),
                'adminClass' => $this->classNameShort()
            ]];
        } else {
            return ['admin:list', [
                'module' => $this->getModule()->getId(),
                'adminClass' => $this->classNameShort()
            ]];
        }
    }

}