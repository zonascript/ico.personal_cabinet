<?php
namespace Modules\Product\Helpers;

use Mindy\QueryBuilder\Aggregation\Aggregation;
use Mindy\QueryBuilder\Aggregation\Count;
use Mindy\QueryBuilder\Aggregation\Max;
use Mindy\QueryBuilder\Aggregation\Min;
use Mindy\QueryBuilder\Expression;
use Modules\Product\Models\FilterModel;
use Xcart\App\Orm\Manager;
use Xcart\App\Orm\QuerySet;

class ProductFilterHelper
{
    /** @var \Xcart\App\Orm\Manager|\Xcart\App\Orm\QuerySet  */
    private $qs;
    private $form_data;

    /**
     * ProductSortHelper constructor.
     *
     * @param QuerySet|Manager $qs    Manager or QuerySet of ProductModel
     * @param array $form_data
     * @param array $filter_types ['price', 'brand', 'filter']
     */
    public function __construct($qs, array $form_data = [], $filter_types = ['price', 'brand', 'filter']) {
        $this->qs = clone $qs;
        $this->qs->order([]);
        $this->form_data = $form_data;
        return $this;
    }

    /**
     * @kind accessorFunction
     * @name getFilterStructure
     *
     * @param array            $types ['price', 'brand', 'filter']
     *
     * @return array
     * @throws \Exception
     */
    public function getFilterStructure(array $types = ['price', 'brand', 'filter'])
    {
        $list = [];

        if (in_array('price', $types)) {
            $tqs = clone $this->qs;
            $tqs->filter(['quick_prices__price__isnull' => false])
                ->select([new Min('xcart_pricing_1.price', 'min'),
                          new Max('xcart_pricing_1.price', 'max')]) //@TODO:FIX IT
                ->asArray();
            $prices = $tqs->get();
            $prices = [
                'min' => floor($prices['min']),
                'max' => ceil($prices['max']),
                'step' => 1,
            ];

            $selected_price = empty($this->form_data['price'])?[]:$this->form_data['price'];
            $selected_price = array_replace_recursive($prices, $selected_price);

            $list['__price__'] = [
                'type' => 'price',
                'key' => 'price',
                'name' => 'Price',
                'changed' => ($selected_price['min'] != $prices['min'] || $selected_price['max'] != $prices['max']),
                'values' => [
                    'prices' => $prices,
                    'selected' => array_replace_recursive($prices, $selected_price),
                ],
            ];
        }

        if (in_array('brand', $types)) {
            $tqs = clone $this->qs;
            $brands = $tqs->select(['name' => 'brand__brand', 'value' => 'brandid', new Count('*', 'count')])
                          ->group(['brandid'])
                          ->order(['brand__brand'])
                          ->asArray()->cache(300)->all();

            $changed = false;
            foreach ($brands as $key => $brand) {
                $brands[$key]['checked'] = (!empty($this->form_data['brand']) && in_array($brand['value'],$this->form_data['brand']));
                $changed = $changed ?: $brands[$key]['checked'];
            }

            $list['__brand__'] = [
                'type' => 'list',
                'key' => 'brand',
                'name' => 'Brand',
                'changed' => $changed,
                'values' => $brands,
            ];
        }


        if (in_array('filter', $types)) {
            $ftqs = clone $this->getFiltrateQS();
            $tqs = clone $this->qs;
            $tqs = $tqs->filter(['filter_values__fv_active' => 'Y'])->order([]);
            $f_ids = $tqs->cache(300)->group(['filter_values__f_id'])->valuesList(['filter_values__f_id'], true);

            if ($f_ids) {
                $filters = FilterModel::objects()
                                      ->filter(['f_active' => 'Y',
                                                'f_id__in' => $f_ids])
                                      ->order(['f_order_by'])
                                      ->cache(300)->valuesList([]);

                if ($filters) {
                    $fv_count = [];
                    $fvalues = $ftqs->filter(['filter_values__fv_active' => 'Y'])
                                  ->select(['filter_values__fv_id', new Count('*', 'count')])
                                  ->group(['filter_values__fv_id'])
                                  ->asArray()->cache(300)->all();

                    foreach ($fvalues as $value) {
                        $fv_count[$value['fv_id']] = $value['count'];
                    }

                    $values = $tqs->with(['filter_values'])
                                  ->select(['filter_values__fv_name', 'filter_values__fv_id', 'filter_values__f_id', new Count('*', 'count')])
                                  ->order(['filter_values__f_id','filter_values__fv_order_by','filter_values__fv_name'])
                                  ->group(['filter_values__fv_id'])
                                  ->asArray()->cache(600)->all();


                    foreach ($filters as $filter)
                    {
                        $list[$filter['f_id']] = [
                            'type' => 'list',
                            'key' => 'filter',
                            'name' =>$filter['f_name'],
                            'changed' => false,
                            'values' => []
                        ];
                    }

                    foreach ($values as $value)
                    {
                        if ($list[$value['f_id']]) {
                            $checked = (!empty($this->form_data['filter']) && in_array($value['fv_id'],$this->form_data['filter']));

                            $list[$value['f_id']]['changed'] = $list[$value['f_id']]['changed'] ?: $checked;
                            $list[$value['f_id']]['values'][] = [
                                'name' => $value['fv_name'],
                                'value' => $value['fv_id'],
                                'count' =>  !empty($fv_count[$value['fv_id']]) ? $fv_count[$value['fv_id']] : $value['count'],
                                'disabled' => empty($fv_count[$value['fv_id']]),
                                'checked' => $checked,
                            ];
                        }
                    }

                    foreach ($list as $key => $filter)
                    {
                        if (count($filter['values']) < 2) {
                            unset($list[$key]);
                        }
                    }
                }
            }


        }

        return $list;
    }

    /**
     * @param null|Manager|QuerySet $qs
     *
     * @return Manager|QuerySet
     * @internal param Manager|QuerySet $pqs ProductModel querySet or manager
     *
     */
    public function getFiltrateQS($qs = null)
    {
        $pqs = clone ($qs ?: $this->qs);

        if (!empty($this->form_data['price']))
        {
            //@TODO: from GREATEST(quick_prices__price, new_map_price)
            //@TODO: Maybe $pqs->filter([ new GREATEST(['quick_prices__price', 'new_map_price'], 'gte', $this->form_data['price']['min']) ]);
            if (!empty($this->form_data['price']['min'])) {
                $pqs->filter(['quick_prices__price__gte' => $this->form_data['price']['min']]);
            }
            if (!empty($this->form_data['price']['max'])) {
                $pqs->filter(['quick_prices__price__lte' => $this->form_data['price']['max']]);
            }
        }


        if (!empty($this->form_data['brand'])) {
            $pqs->filter(['brandid__in' => $this->form_data['brand']]);
        }


        if (!empty($this->form_data['filter'])) {
            $pqs->filter(['filter_values__fv_id__in' => $this->form_data['filter']]);
        }

        return $pqs;
    }
}