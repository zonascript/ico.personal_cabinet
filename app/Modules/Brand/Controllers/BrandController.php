<?php

namespace Modules\Brand\Controllers;


use Modules\Brand\BrandModule;
use Modules\Brand\Helpers\BrandHelper;
use Modules\Brand\Models\BrandModel;
use Modules\Brand\Stores\BrandStore;
use Modules\Core\Models\HistoryDataModel;
use Xcart\App\Controller\PrototypeAdminController;
use Xcart\External_Marketplaces\DisabledMarketPlace;
use Xcart\App\Orm\Model;
use Xcart\App\Orm\ModelInterface;

class BrandController extends PrototypeAdminController
{
    public $defaultAction = 'brand_list';

    public function update($id = null)
    {
        if (!is_null($id) && $model = BrandModel::objects()->get(['brandid' => $id])) {
            $this->createOrUpdate($model);
        }
    }

    public function create()
    {
        $model = new BrandModel;
        $this->createOrUpdate($model);
    }

    public function brand_group($id = null)
    {
        $model = BrandModel::objects()->get(['brandid' => $id]);
        if ($model) {
            if (!empty($_POST['brand_group'][$id])) {

                $mgr = BrandModel::objects()
                    ->filter(['brandid__in' => $_POST['brand_group'][$id]]);

                $mgr->update(['parent_brand_id' => $id]);

                foreach ($br = $mgr->all() as $brand) {
                    $brand->products->update(['brandid' => $id]);
                }

                $this->jsonResponse(true);
                return;
            }
        }
        $brandStore = new BrandStore($_GET, $model);

        echo $this->render('brands_group.tpl',
            [
                'brands' => $brandStore->getModels(),
                'pager' => $brandStore->getPager(),
                'search' => $_GET['search'],
                'parent' => $model
            ]
        );
    }

    public function brand_list($slug = null)
    {
        $letter = ['letter' => $slug];

        $brandStore = new BrandStore(array_merge($_GET, $letter));

        echo $this->renderInternal('brands_list.tpl',
            array_merge(
                [
                    'brands' => $brandStore->getModels(),
                    'pager' => $brandStore->getPager(),
                    'search' => $_GET['search'],
                ],
                $letter)
        );
    }

    /** @param Model|ModelInterface $model */
    private function createOrUpdate($model)
    {
        $errors = [];

        if (isset($_POST['delete'])) {
            if ($model->delete()) {
                $this->autoRedirect($model);
            }
        }

        $class = BrandModel::classNameShort();

        if ($_POST[$class]) {

            if (!(!empty($_POST['child_brands']) && isset($_POST[$class]['parent_brand_id']))) {

                $model->setAttributes($_POST[$class]);

                $model->parent_brand_id = isset($_POST[$class]['parent_brand_id']) ? $_POST[$class]['parent_brand_id'] : null;

                if ($model->isValid() && $model->save()) {

                    BrandModel::objects()->filter(['parent_brand_id' => $model->brandid])->update(['parent_brand_id' => null]);

                    if (!empty($_POST['child_brands']) && !$model->parent_brand_id) {
                        BrandModel::objects()
                            ->filter(['brandid__in' => $_POST['child_brands']])
                            ->update(['parent_brand_id' => $model->brandid]);
                    }

                    DisabledMarketPlace::deleteAllDisabledMarketPlace($model->brandid, 'B');

                    if (!empty($_POST['excluded_marketplaces'])) {

                        foreach ($_POST['excluded_marketplaces'] as $iExcludedMarketplace) {
                            $oMarketPlace = new DisabledMarketPlace();
                            $oMarketPlace->fill(['marketplace_id' => $iExcludedMarketplace, 'resource_id' => $model->brandid, 'resource_type' => 'B']);
                            $oMarketPlace->addDisabledMarketPlace();
                        }
                    }

                    $this->autoRedirect($model);
                }
            } else {
                $errors[] = BrandModule::t("Brand can not have parents and children's brands");
            }
        }

        echo $this->renderInternal('brand_edit.tpl',
            array_merge(
                [
                    'model' => $model,
                    'errors' => $errors,
                ],
                BrandHelper::getExternalMarketplaces($model->brandid)
            )
        );
    }

    private function autoRedirect($model)
    {
        list($url, $params) = $this->autoActions($model);
        $this->redirect($url, $params, 303);
    }

    private function autoActions($model)
    {
        if (array_key_exists('save_continue', $_POST)) {
            return ['brand:update_brand', ['id' => $model->brandid]];
        } else if (array_key_exists('save_create', $_POST)) {
            return ['brand:create_brand', []];
        } else {
            return ['brand:list', []];
        }
    }
}