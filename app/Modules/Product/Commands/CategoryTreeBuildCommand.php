<?php
namespace Modules\Product\Commands;

use Modules\Product\Models\CategoryModel;
use Xcart\App\Commands\Command;

class CategoryTreeBuildCommand extends Command
{
    public function handle($arguments = [])
    {
        CategoryModel::objects()->rebuild();
    }
}