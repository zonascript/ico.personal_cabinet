<?php
namespace Modules\Meta\Models;


use Modules\Meta\MetaModule;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\TextField;
use Xcart\App\Orm\Model;
use Xcart\App\Traits\RenderTrait;

class MetaText extends Model
{
    use RenderTrait;

    public $params = [];

    public static function getFields() 
    {
        return [
            'title' => [
                'class' => CharField::className(),
                'verboseName' => MetaModule::t("Title")
            ],
            'code' => [
                'class' => CharField::className(),
                'verboseName' => MetaModule::t("Code")
            ],
            'text' => [
                'class' => TextField::className(),
                'verboseName' => MetaModule::t('Text')
            ]
        ];
    }
    
    public function __toString() 
    {
        return (string) $this->code;
    }

    public function renderTitle()
    {
        return $this->render('title');
    }

    public function renderText()
    {
        return $this->render('text');
    }

    public function render($name)
    {
        return self::renderString($this->{$name}, $this->params);
    }
} 