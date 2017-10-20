<?php

namespace Modules\Brand\Stores;


use Mindy\QueryBuilder\Expression;
use Mindy\QueryBuilder\Q\QAnd;
use Mindy\QueryBuilder\Q\QOr;
use Modules\Brand\Models\BrandModel;
use Xcart\App\Main\Xcart;
use Xcart\App\Pagination\DataSource\QuerySetDataSource;
use Xcart\App\Pagination\Pagination;
use Xcart\App\Store\BaseStore;

class BrandStore extends BaseStore
{
    protected $pager;
    protected $qs;
    protected $form_data;
    protected $model;
    public $defaultPagerPageSize = 50;

    public function __construct($data, BrandModel $model = null)
    {
        $this->form_data = $data;

        if ($model) {
            $this->model = $model;
        }

        $this->populate($data);
    }

    public function populate(array $data)
    {
        $qs = $this->getQuerySet();

        $filter = [
            'parent_brand_id__isnull' => true,
            //'brand_storefront__products_count__gt' => 0,
        ];

        if ($this->model) {
            $qs->exclude(['brandid' => $this->model->brandid]);
        } else {
            $filter['brand_storefront__sfid'] = Xcart::app()->request->session->get('current_storefront');
        }

        if (!empty($data['search'])) {
            $qs->join('left join', 'xcart_brands', ['b2.parent_brand_id' => 'brandid'], 'b2');
            $filter[] = new QAnd (new QOr(
                [
                    'brand__contains' => $data['search'],
                    'b2.brand__contains' => $data['search']
                ]
            ));
        }

        if (!empty($data['letter'])) {
            $data['letter'] != '0-9' ? $filter['brand__startswith'] = $data['letter'] : $filter['brand__raw'] = "REGEXP '^[0-9]'";
        }

        $qs->filter($filter);

        $qs->group(['brandid']);

        $qs->order(['brand']);

        $this->qs = $qs;
    }

    public function getQuerySet()
    {
        if (!$this->qs) {
            $this->qs = BrandModel::objects()->getQuerySet();
        }
        return $this->qs;
    }

    public function getModels()
    {
        return $this->prepareModels($this->getPager()->paginate());
    }

    public function prepareModels($models)
    {
        if (!$models) {
            return [];
        }
        return $models;
    }

    public function getPager()
    {
        if (!$this->pager) {
            $this->pager = new Pagination($this->getQuerySet(), ['pageSize' => $this->defaultPagerPageSize], new QuerySetDataSource());
        }

        return $this->pager;
    }
}