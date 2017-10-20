<?php

namespace Modules\Product\Controllers;

use Modules\Brand\Models\BrandModel;
use Modules\Product\Models\ProductModel;
use Modules\User\Helpers\SurfingHelper;
use Modules\User\Models\SurfPathModel;
use Xcart\App\Controller\Controller;
use Xcart\App\Main\Xcart;

class DefaultController extends Controller
{
    public function actionViewOld($id, $slug)
    {
        $this->view_internal(ProductModel::objects()->filter(['productid' => $id])->get());
    }
    
    public function actionView($sku)
    {
        $this->view_internal(ProductModel::objects()->filter(['productcode' => $sku])->get());
    }

    /**
     * @param ProductModel|null $model
     *
     * @throws \Xcart\App\Exceptions\HttpException
     */
    private function view_internal($model = null)
    {
        if (!$model)
        {
            $this->error();
        }

        echo $this->render('product/product.tpl', [
            'model' => $model,
            'breadcrumbs' => $model->getBreadcrumbs(),
        ]);

        if (!Xcart::app()->cart->has($model)) {
            Xcart::app()->cart->add($model);
        }

//        foreach (Xcart::app()->cart->getItems() as $cartItem) {
//            func_dump((string)"{{$cartItem->getQuantity()}}" . $cartItem->getObject() );
//        }
//
//
//        func_dump(Xcart::app()->cart->getQuantity());
//        func_dump(Xcart::app()->cart->getTotal());


//        func_dump($model);

//        SurfingHelper::logSurfPath(['resource_type' => SurfPathModel::GOAL_TYPE_PRODUCT, 'resource_id' => $model->pk]);
    }
}