<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 29/01/15 15:04
 */

namespace Modules\Translate\Tables;

use Mindy\Base\Mindy;
use Mindy\Table\Columns\LinkColumn;
use Mindy\Table\Table;
use Modules\Translate\TranslateModule;

class LanguageTable extends Table
{
    public function getColumns()
    {
        $url = Mindy::app()->urlManager;
        return [
            [
                'class' => LinkColumn::className(),
                'route' => function($record) use ($url) {
                    return $url->reverse('translate:language', ['language' => $record]);
                },
                'title' => TranslateModule::t('Language')
            ]
        ];
    }
}
