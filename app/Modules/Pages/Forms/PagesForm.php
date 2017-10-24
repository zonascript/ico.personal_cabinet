<?php

namespace Modules\Pages\Forms;

use Mindy\Form\Fields\DropDownField;
use Mindy\Form\Fields\TextAreaField;
use Mindy\Form\Fields\UEditorField;
use Mindy\Form\ModelForm;
use Modules\Meta\Forms\MetaInlineForm;
use Modules\Pages\Models\Page;
use Modules\Pages\PagesModule;

/**
 * Class PagesForm
 * @package Modules\Pages
 */
class PagesForm extends ModelForm
{
    public function getFieldsets()
    {
        return [
            PagesModule::t('Main information') => [
                'name', 'url', 'parent', 'is_index', 'is_published'
            ],
            PagesModule::t('Content') => [
                'content_short', 'content'
            ],
            PagesModule::t('Additional') => [
                'published_at', 'file'
            ],
            PagesModule::t('Display settings') => [
                'view', 'view_children', 'sorting'
            ]
        ];
    }

    public function getFields()
    {
        return [
            'content_short' => [
                'class' => TextAreaField::class,
                'label' => PagesModule::t('Short content')
            ],
            'content' => [
                'class' => UEditorField::class,
                'label' => PagesModule::t('Content')
            ],
            'view' => [
                'class' => DropDownField::class,
                'choices' => Page::getViews(),
                'label' => PagesModule::t('View')
            ],
            'view_children' => [
                'class' => DropDownField::class,
                'choices' => Page::getViews(),
                'hint' => PagesModule::t('View for children pages'),
                'label' => PagesModule::t('View children')
            ],
        ];
    }

    public function getInlines()
    {
        return [
            ['meta' => MetaInlineForm::class]
        ];
    }

    public function getModel()
    {
        return new Page;
    }
}
