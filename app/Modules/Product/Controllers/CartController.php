<?php

namespace Modules\Product\Controllers;

use Modules\Cart\Controllers\BaseCartController;
use Modules\Product\Models\ProductModel;
use Xcart\App\Main\Xcart;

class CartController extends BaseCartController
{
    public function actionAdd($uniqueId, $quantity = 1)
    {
        $quantity = $this->getRequest()->post->get('quantity', 1);

        parent::actionAdd($uniqueId, $quantity);
    }

    protected function addInternal($uniqueId, $quantity = 1)
    {
        /** @var ProductModel $model */
        $model = ProductModel::objects()->get(['pk' => $uniqueId]);

        if (!$model->isOutOfStock) {
            Xcart::app()->cart->add($model, $quantity, null, $this->getRequest()->post->get('data', []));

            return true;
        }

        return false;
    }
}