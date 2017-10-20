<?php
namespace Modules\Sites\Forms;

use Mindy\QueryBuilder\Q\QAndNot;
use Modules\Sites\Models\ListConfigModel;
use Modules\Sites\Models\SiteModel;
use Xcart\App\Form\Fields\DropDownField;
use Xcart\App\Form\Fields\ImageField;
use Xcart\App\Form\Fields\TextField;
use Xcart\App\Form\ModelForm;

class ListConfigForm extends ModelForm
{
    public function getModel()
    {
        return new ListConfigModel();
    }

    public function getFields()
    {
        return [
            'storefront' => [
                'class' => DropDownField::className(),
                'choices' => function() {
                    $result = ['' => ''];
//                    $filter = ['status' => 'Y'];
                    $filter = [];

                    $id = $this->getInstance()->sf_code;
                    if ($id) {
                        $ids = ListConfigModel::objects()->filter([new QAndNot(['sf_code' => $id])])->valuesList(['sf_code'], true);
                    }
                    else {
                        $ids = ListConfigModel::objects()->valuesList(['sf_code'], true);
                    }

                    if ($ids) {
                        $filter[] = new QAndNot(['code__in' => $ids]);
                    }

                    $models = SiteModel::objects()->filter($filter)->all();

                    foreach ($models as $model) {
                        $result[$model->code] = (string)$model;
                    }

                    return $result;
                }
            ],
            'description' => TextField::className(),
            'list_icon' => ImageField::className(),
            'list_image' => ImageField::className(),
        ];
    }
}