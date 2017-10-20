<?php
namespace Modules\Core\TemplateLibraries;

use Modules\Core\Helpers\AdminHelper;
use Xcart\App\Template\TemplateLibrary;

class AdminLibrary extends TemplateLibrary
{
    /**
     * @kind accessorProperty
     * @name admin_menu
     * @return array
     */
    public static function getMenu()
    {
        return AdminHelper::getMenu();
    }
}