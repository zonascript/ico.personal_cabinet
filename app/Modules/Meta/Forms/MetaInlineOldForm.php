<?php

namespace Modules\Meta\Forms;

use Exception;
use Mindy\Base\Mindy;
use Mindy\Form\Fields\CharField;
use Mindy\Form\Fields\TextAreaField;
use Mindy\Form\ModelForm;
use Mindy\Helper\Meta as MetaGenerator;
use Modules\Meta\MetaModule;
use Modules\Meta\Models\Meta;

/**
 * @DEPRECATED
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 08/05/14.05.2014 19:22
 */
class MetaInlineOldForm extends ModelForm
{
    public $max = 1;

    public $exclude = ['url', 'site'];

    public function getName()
    {
        return MetaModule::t('Meta');
    }

    public function getFields()
    {
        return [
            'title' => [
                'class' => CharField::className(),
                'label' => MetaModule::t('Title')
            ],
            'description' => [
                'class' => TextAreaField::className(),
                'label' => MetaModule::t('Description')
            ],
            'keywords' => [
                'class' => TextAreaField::className(),
                'label' => MetaModule::t('Keywords')
            ],
        ];
    }

    public function getModel()
    {
        return new Meta;
    }

    public function afterOwnerSave($owner)
    {
        if (method_exists($owner, 'getAbsoluteUrl')) {
            $this->getInstance()->url = $owner->getAbsoluteUrl();
            $this->getInstance()->save(['url']);
        }
    }

    /**
     * @param \Mindy\Form\BaseForm|ModelForm $owner
     * @param array $attributes
     * @throws Exception
     * @return array
     */
    public function beforeSetAttributes($owner, array $attributes)
    {
        $model = $owner->getModel();
        $ownerModel = $owner->getParentForm()->getInstance();
        if (!isset($ownerModel->metaConfig)) {
            throw new Exception("metaConfig doesn't exists in model: " . $model->className());
        }
        $meta = $ownerModel->metaConfig;
        if (empty($attributes['is_custom'])) {
            $attributes = array_merge($attributes, [
                'title' => $ownerModel->{$meta['title']},
                'keywords' => MetaGenerator::generateKeywords($ownerModel->{$meta['keywords']}),
                'description' => MetaGenerator::generateDescription($ownerModel->{$meta['description']})
            ]);
        }
        return parent::beforeSetAttributes($owner, $attributes);
    }

    public function getLinkModels(array $attributes)
    {
        $model = array_shift($attributes);
        if (!$model->getIsNewRecord() && method_exists($model, 'getAbsoluteUrl')) {
            $qs = Meta::objects()->filter(['url' => $model->getAbsoluteUrl()]);
            if (Mindy::app()->getModule('Meta')->onSite) {
                $qs = $qs->currentSite();
            }
            return $qs;
        }
        return [];
    }
}
