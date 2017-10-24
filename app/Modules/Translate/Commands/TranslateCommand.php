<?php

namespace Modules\Translate\Commands;

use Mindy\Console\ConsoleCommand;
use Modules\Translate\Helpers\TranslateHelper;

class TranslateCommand extends ConsoleCommand
{
    public function actionCollect($module = null)
    {
        $helper = new TranslateHelper();
        $helper->collect($module);
    }
}
