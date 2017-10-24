<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 16/11/14.11.2014 18:46
 */

namespace Modules\Core\Commands;

use Exception;
use Mindy\Console\ConsoleCommand;

class TreeCommand extends ConsoleCommand
{
    public function actionRecover($className)
    {
        if (!class_exists($className)) {
            throw new Exception("Unknown class");
        }

        $i = 0;
        $skip = [];
        while ($className::objects()->filter(['lft__isnull' => true])->count() != 0) {
            $i++;
            $fixed = 0;
            echo "Iteration: " . $i . PHP_EOL;
            $models = $className::objects()
                ->exclude(['pk__in' => $skip])
                ->filter(['lft__isnull' => true])
                ->order(['parent_id'])->all();
            foreach ($models as $model) {
                $model->lft = $model->rgt = $model->level = $model->root = null;
                if ($model->saveRebuild()) {
                    $skip[] = $model->pk;
                    $fixed++;
                }
                echo '.';
            }
            echo PHP_EOL;
            echo "Fixed: " . $fixed . PHP_EOL;
        }
    }
}
