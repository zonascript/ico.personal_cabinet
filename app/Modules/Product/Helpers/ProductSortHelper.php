<?php
namespace Modules\Product\Helpers;

use Modules\Product\Models\CategoryModel;
use Xcart\Helpers\ViewedRelatedProducts;

class ProductSortHelper
{
    public static $default = 'relevance';

    public static $orderBy = [
        'relevance' => 'Relevance',
        'price' => 'Price low to high',
        '-price' => 'Price high to low',
        'new' => 'New',
        'brand' => 'Brand',
    ];

    /** @var \Xcart\App\Orm\Manager|\Xcart\App\Orm\QuerySet  */
    private $qs;
    private $category;

    /**
     * ProductSortHelper constructor.
     *
     * @param \Xcart\App\Orm\Manager|\Xcart\App\Orm\QuerySet $qs ProductModel QuerySet
     */
    public function __construct($qs) {
        $this->qs = $qs;
        return $this;
    }

    public function setCategory($category = null)
    {
        $this->category = $category;
        return $this;
    }

    public function getSortedQS($orderBy = 'relevance')
    {
        switch ($orderBy) {
            case 'price': {
                $pqs = $this->getOrderByPrice('');
                break;
            }
            case '-price': {
                $pqs = $this->getOrderByPrice('-');
                break;
            }
            case 'new': {
                $pqs = $this->getOrderByNew();
                break;
            }
            case 'brand': {
                $pqs = $this->getOrderByBrand();
                break;
            }
            case 'relevance':
            default: {
                $orderBy = static::$default;
                $pqs = $this->getOrderByRelevance();
            }
        }
        
        return $pqs;
    }

    /**
     * @param CategoryModel $category
     * @param int           $max_product
     *
     * @return \Xcart\App\Orm\Manager|\Xcart\App\Orm\QuerySet
     */
    public function getOrderByRelevance($max_product = 50)
    {
        $qs = clone $this->qs;
        $ta = $qs->getTableAlias();
        list($oldOrder, $orderOptions) = $qs->getQueryBuilder()->getOrder();
        

        if ($p_ids = (new ViewedRelatedProducts())->getRelated()) {
            $t_ids = [];

            if ($this->category) {
                $categories = CategoryModel::objects($this->category)->descendants(true)->valuesList(['pk'], true);
            }

            foreach ($p_ids as $n => $product)
            {
                $push = false;
                $push_el = $product['productid'];

                if (in_array($push_el, $t_ids)) { continue; }

                if ($this->category && !empty($categories) && !empty($product['categoryid']))
                {
                    $cids = array_intersect($categories, $product['categoryid']);

                    if (!empty($cids)) {
                        $push = true;
                    }
                }
                else if (!$this->category) {
                    $push = true;
                }

                if ($push) {
                    $t_ids[] = $push_el;
                }

                if (count($t_ids) == $max_product) { break; }
            }

            if (!empty($t_ids))
            {
                array_unshift($oldOrder,
                              "IF(FIELD( {$ta}.productid, " . implode(',', $t_ids) . ") = 0,1,0)",
                              "FIELD( {$ta}.productid, " . implode(',', $t_ids) . ")"
                );
            }
        }

        if ($this->category) {
            $oldOrder[] = 'categories__order_by';
            $oldOrder[] = 'categories__through__orderby';
            $qs->with(['categories_link']);
        }

        $qs->order($oldOrder);

        return $qs;
    }

    public function getOrderByPrice($direction = '-')
    {
        $qs = clone $this->qs;

        list($oldOrder, $orderOptions) = $qs->getQueryBuilder()->getOrder();

        $qs->with(['quick_prices']);
        $qs->filter(['quick_prices__price__isnull' => false]);
        array_unshift($oldOrder, $direction.'quick_prices__price');

        return $qs->order($oldOrder);
    }

    public function getOrderByNew()
    {
        $qs = clone $this->qs;
        list($oldOrder, $orderOptions) = $qs->getQueryBuilder()->getOrder();
        array_unshift($oldOrder, '-add_date');
        return $qs->order($oldOrder);
    }

    public function getOrderByBrand()
    {
        $qs = clone $this->qs;
        list($oldOrder, $orderOptions) = $qs->getQueryBuilder()->getOrder();
        array_unshift($oldOrder, '-manufacturerid');
        return $qs->order($oldOrder);
    }
}