<?php

namespace Modules\Editor\Fields;

use Modules\Core\TemplateLibraries\AssetsLibrary;
use Xcart\App\Form\Fields\TextAreaField;

class EditorField extends TextAreaField
{
    public $inputTemplate = 'editor/fields/editor_field_input.tpl';

    public function init()
    {
        parent::init();

        AssetsLibrary::addAsset(['type' => 'js', 'position' => 'head'], '<script src="/static_admin/dist/raw/editor/tinymce.min.js"></script>');
    }
}