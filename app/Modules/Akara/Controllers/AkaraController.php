<?php

namespace Modules\Akara\Controllers;


use Mindy\Base\Mindy;
use Modules\Core\Controllers\FrontendController;

class AkaraController extends FrontendController
{
    public function actionPurchase()
    {
        $app = Mindy::app();

        echo $this->render('akara/purchase.html',
            [
                'user' => $app->getUser()
            ]
        );
    }

    public function actionWithdraw()
    {
        $app = Mindy::app();

        echo $this->render('akara/withdraw.html',
            [
                'user' => $app->getUser()
            ]
        );
    }
}