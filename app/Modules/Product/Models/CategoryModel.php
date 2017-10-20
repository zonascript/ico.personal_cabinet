<?php
namespace Modules\Product\Models;

use Mindy\QueryBuilder\Expression;
use Xcart\App\Components\Breadcrumbs;
use Xcart\App\Main\Xcart;
use Xcart\App\Orm\AutoMetaTreeModel;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\HasManyField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Fields\ManyToManyField;

/**
 * @property string categoryid_path
 * @property mixed categoryid
 */
class CategoryModel extends AutoMetaTreeModel
{
    public static function tableName()
    {
        return 'xcart_categories';
    }

    public static function getFields()
    {
        return array_merge_recursive(
            parent::getFields(),
             [
                 'products' => [
                     'class' => ManyToManyField::className(),
                     'modelClass' => ProductModel::className(),
                     'through' => ProductCategoriesModel::className()
                 ],

                 'products_link' => [
                     'class' => HasManyField::className(),
                     'modelClass' => ProductCategoriesModel::className(),
                     'link' => ['categoryid' => 'categoryid']
                 ],

                'categoryid' => [
                    'class' => AutoField::className(),
                    'primary' => true,
                    'null' => false,
                ],
                'parent' => [
                    'field' => 'parentid'
                ],

                'storefrontid' => [
                    'class' => IntField::className(),
                    'primary' => false,
                    'null' => false,
                ],
                'description' => [
                    'class' => CharField::className(),
                    'null' => false,
                    'default' => ''
                ],
                'google_product_category' => [
                    'class' => CharField::className(),
                    'null' => false,
                    'default' => ''
                ],
            ]
        );
    }

    public function getBreadcrumbs()
    {
        $bread = new Breadcrumbs();

        if ($parents = self::objects($this)->ancestors()->order(['lft'])->all())
        {
            foreach ($parents as $model) {
                $bread->add($model->category, $model->getAbsoluteUrl());
            }
        }

        $bread->add($this->category, $this->getAbsoluteUrl());

        return $bread;
    }

    public function getAbsoluteUrl()
    {
        if ($this->categoryid)
        {
            return Xcart::app()->router->url('catalog:view:old', ['id' => $this->categoryid, 'slug' => 'TEMP']);
        }

        return false;
    }

    public function getSubcategories($withProductCount = true, $level = 1, $tree = false, $cache = true)
    {
        $qs = static::objects()
                    ->descendants(false, $level)
                    ->filter(['avail' => 'Y']);

        if ($withProductCount) {
            $ta = $qs->getTableAlias();

            $pcountSql = ProductModel::objects()
                        ->with(['categories'])
                        ->filter([
                            'forsale' => 'Y',
                            'categories__lft__gte' => new Expression("{{category}}.lft"),
                            'categories__rgt__lte' => new Expression("{{category}}.rgt"),
                            'categories__root' => new Expression("{{category}}.root"),
                        ])
                        ->countSql();

            $pcountSql = str_replace($ta, 'cp', $pcountSql);
            $pcountSql = str_replace("{{category}}", $ta, $pcountSql);

            $qs->group(['categoryid']);
            $qs->select([
                'pcount' => $pcountSql,
                '*',
            ]);

            $qs->having(['pcount__gt' => 0]);
        }

        if ($tree) {
            $qs->asTree();
        }

        if ($cache) {
            $qs->cache(300);
        }

        return $qs->all();
    }
}