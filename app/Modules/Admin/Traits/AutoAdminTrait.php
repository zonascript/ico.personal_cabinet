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
 * @date 27/02/15 07:47
 */

namespace Modules\Admin\Traits;

use DirectoryIterator;

trait AutoAdminTrait {

    /**
     * @return array
     */
    public function getMenu()
    {
        /* @var $this \Mindy\Base\BaseModule */
        $adminDir = $this->getBasePath() . '/Admin';
        $items = [];
        if (is_dir($adminDir)) {
            foreach (new DirectoryIterator($adminDir) as $fileInfo) {
                /* @var $fileInfo \DirectoryIterator */
                if($fileInfo->isDot()) continue;
                $className = str_replace('.php', '', $fileInfo->getFilename());
                $class = strtr("Modules\\{module}\\Admin\\{class}", [
                    '{module}' => $this->getId(),
                    '{class}' => $className
                ]);
                /* @var $admin \Modules\Admin\Components\ModelAdmin|\Modules\Admin\Components\NestedAdmin */
                $admin = new $class;

                if ($admin->autoCollect) {
                    $items[] = [
                        'name' => $admin->getVerboseName(),
                        'adminClass' => $className
                    ];
                }
            }
        }

        return $items ? [
            'name' => $this->getName(),
            'items' => $items
        ] : [];
    }
} 