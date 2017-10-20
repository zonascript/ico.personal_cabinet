<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 19/12/15 10:56
 */
namespace Modules\Meta\Models;

use Modules\Meta\MetaModule;
use Xcart\App\Orm\Fields\TextField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Model;
use Xcart\App\Traits\RenderTrait;

class MetaTemplate extends Model
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
            'description' => [
                'class' => TextField::className(),
                'verboseName' => MetaModule::t('Description'),
                'null' => true
            ],
            'keywords' => [
                'class' => TextField::className(),
                'verboseName' => MetaModule::t('Keywords'),
                'null' => true
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

    public function renderKeywords()
    {
        return $this->render('keywords');
    }

    public function renderDescription()
    {
        return $this->render('description');
    }

    public function render($name)
    {
        return self::renderString($this->{$name}, $this->params);
    }
} 