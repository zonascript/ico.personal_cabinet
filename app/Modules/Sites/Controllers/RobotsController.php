<?php
/**
 * 
 *
 * All rights reserved.
 * 
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 08/10/14.10.2014 14:59
 */

namespace Modules\Sites\Controllers;

use Modules\Core\Controllers\FrontendController;

class RobotsController extends FrontendController
{
    public function actionIndex()
    {
        header("Content-Type: text/plain");
        $model = $this->getModule()->getSite();
        echo empty($model->robots) ? $this->render('sites/robots.txt', [
            'model' => $model
        ]) : $model->robots;
    }
}
