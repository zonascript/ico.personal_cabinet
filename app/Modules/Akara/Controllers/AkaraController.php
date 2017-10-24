<?php

namespace Modules\Akara\Controllers;


use Modules\Core\Controllers\FrontendController;

class AkaraController extends FrontendController
{
    public function actionPurchase()
    {
        echo $this->render('akara/purchase.html');
    }
}