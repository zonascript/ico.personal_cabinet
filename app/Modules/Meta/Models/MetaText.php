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

use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\TextField;
use Mindy\Orm\Model;
use Mindy\Utils\RenderTrait;

class MetaText extends Model
{
    use RenderTrait;

    public $params = [];

    public static function getFields() 
    {
        return [
            'title' => [
                'class' => CharField::className(),
                'verboseName' => self::t("Title")
            ],
            'code' => [
                'class' => CharField::className(),
                'verboseName' => self::t("Code")
            ],
            'text' => [
                'class' => TextField::className(),
                'verboseName' => self::t('Text')
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