<?php

namespace Modules\Product\Controllers;

use Mindy\QueryBuilder\Expression;
use Modules\Product\Helpers\SearchSuggestionHelper;
use Modules\Product\Models\ProductModel;
use Modules\Sites\Models\SiteModel;
use Xcart\App\Components\Breadcrumbs;
use Xcart\App\Main\Xcart;
use Xcart\ElasticSearch;

class SearchController extends AbstractCatalogController
{
    public $view = 'catalog/search.tpl';
    public $filters = ['price', 'brand', 'filter'];
    public $excluded_indexes = [
        'www.s3stores.com',
        'www.40off.com',
        'www.readyfreddie.com',
    ];

    public $ids;
    public $count;
    private $suggestion;
    private $searched;
    private $q_original;
    private $q;


    public function actionKeywords($q)
    {
        $q = str_replace(['_', '-'], ' ', $q);
        $this->redirect('catalog:search', [], 301, ['q' => $q]);
    }

    public function actionApiSuggestion()
    {
        if ($this->getRequest()->getIsAjax()) {
            $this->jsonResponse([
                'suggests' => (new SearchSuggestionHelper($this->getRequest()->get->get('q'), $this->getSearchIndex()))->mixed_suggestion(5),
                'q' => $this->getRequest()->get->get('q'),
            ]);
        }
    }

    public function getSearchIndex()
    {
        /** @var \Modules\Sites\SitesModule $siteModule */
        $siteModule = Xcart::app()->getModule('Sites');

        if ($siteModel = $siteModule->getSite(false)) {
            return $siteModel->domain;
        }

        $sites = SiteModel::getAllEnabled();
        $indexes = [];
        foreach ($sites as $site) {
            $index = strtolower($site->domain);
            if (!in_array($index, $this->excluded_indexes)) {
                $indexes[] = $index;
            }
        }

        return implode(',', $indexes);
    }

    public function actionSearch()
    {
        $show_empty = false;

        $this->q = $this->q_original = $this->getRequest()->get->get('q', '');
        if (!$this->q) {
            $this->redirect('/');
        }

        if ($product = ProductModel::objects()->filter(['productcode' => $this->q])->get()) {
            $this->redirect($product->getAbsoluteUrl());
        }

        $this->suggestion = (new SearchSuggestionHelper($this->q, $this->getSearchIndex()))->mixed_suggestion(5);

        if (!$this->searched = $this->getProductFromElastic($this->q))
        {
            $show_empty = true;

            if ($this->suggestion)
            {
                $this->q = $this->suggestion[0];
                $this->suggestion = (new SearchSuggestionHelper($this->q, $this->getSearchIndex()))->mixed_suggestion(5);
                $show_empty = !$this->getProductFromElastic($this->q);
            }
        }

        if ($show_empty) {
            echo $this->render('catalog/search_empty.tpl', [
                'model' => $this->q,
                'breadcrumbs' => $this->getBreadcrumbsFromData($this->q),
            ]);
            die();
        }

        $this->view_internal($this->q);
    }


    public function getAdvancedData($data = null)
    {
        return [
            'suggestion' => $this->suggestion,
            'searched' => $this->searched,
            'q_original' => $this->q_original,
            'q' => $this->q,
        ];
    }

    public function getElastic($search, $min_score = null)
    {
        /** @var \Modules\Core\CoreModule $coreModule */
        $coreModule = Xcart::app()->getModule('Core');
        $config = $coreModule::getGlobalConfig();
        $config_min_scope = $config["ElasticSearch_options"]["search_results_minimum_score_value"];

        $classElastic = new ElasticSearch($config["ElasticSearch_options"], $this->getSearchIndex());
        $classElastic->setSource("*._id");
        $classElastic->setMinScore($min_score ?: $config_min_scope);
        $classElastic->setType('product');
        $classElastic->setQueryParams($search);

        return $classElastic;
    }

    public function getProductFromElastic($search, $min_score = null, $max_size = 1000, $page = 1)
    {
        $elastic = $this->getElastic($search, $min_score);
        $result = $elastic->query(['from' => ($page-1) * $max_size, 'size' => $max_size]);

        $items = empty($result["hits"]["hits"]) ? [] : $result["hits"]["hits"];
        $count = empty($result["hits"]["total"]) ? 0 : $result["hits"]["total"];

        if ($items) {
            usort($items, function($a, $b){
                if ($a['_score'] == $b['_score']) {
                    return 0;
                }
                return $a['_score'] < $b['_score'] ?  1 : -1;
            });

            $this->ids = array_map(function($item) {return $item['_id']; }, $items);
        }
        else if (!$items && is_null($min_score)) {
            $this->getProductFromElastic($search, .01, $max_size);
        }

        return $count;
    }

    public function getQS($data)
    {
        /** @var \Modules\Sites\SitesModule $siteModule */
        $siteModule = Xcart::app()->getModule('Sites');

        return parent::getQS($data)
                     ->filter(['sites__storefrontid' => $siteModule->getSite()->storefrontid, 'productid__in' => $this->ids]);
    }

    public function getSortedQS($qs, $model = null)
    {
        if ($this->sort == 'relevance') {
            $ta = $qs->getTableAlias();
            return $qs->order([
                new Expression("FIELD({$ta}.productid, " . implode(',', $this->ids) . ") ASC"),
            ]);
        }

        return parent::getSortedQS($qs, $model);
    }

    public function getBreadcrumbsFromData($data)
    {
        $bread = new Breadcrumbs();
        $bread->add('Search: '. strip_tags($data));

        return $bread;
    }
}