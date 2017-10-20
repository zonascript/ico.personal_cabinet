<?php

namespace Modules\Pages\TemplateLibraries;

use Modules\Text\Models\InfoBlock;
use Xcart\App\Template\Renderer;
use Xcart\App\Template\TemplateLibrary;

class TextLibrary extends TemplateLibrary
{
    use Renderer;

    /**
     * @name fetch_info_block
     * @kind accessorFunction
     * @return string
     */
    public static function fetchInfoBlock($key = null, $id = null)
    {
        $filter = [];
        if ($id) {
            $filter['id'] = $id;
        }
        if ($key) {
            $filter['key'] = $key;
        }
        return InfoBlock::objects()->filter($filter)->get();
    }
}