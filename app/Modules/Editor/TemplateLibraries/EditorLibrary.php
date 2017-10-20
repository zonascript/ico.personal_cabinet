<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company HashStudio
 * @site http://hashstudio.ru
 * @date 08/09/16 07:25
 */

namespace Modules\Editor\TemplateLibraries;

use Xcart\App\Template\TemplateLibrary;

class EditorLibrary extends TemplateLibrary
{
    /**
     * @name is_image
     * @kind modifier
     * @return string
     */
    public static function isImage($filename)
    {
        $lower = mb_strtolower($filename, 'UTF-8');
        $arrayPath = explode('.', $lower);
        $ext = end($arrayPath);
        return in_array($ext, ['jpg', 'jpeg', 'png']);
    }
}