<?php
namespace Modules\Core\Components;

use ArrayAccess;
use Iterator;
use Modules\Core\Models\GlobalConfigModel;
use Xcart\App\Main\Xcart;

class GlobalConfig implements ArrayAccess, Iterator
{
    private $data = [];
    private $checked = [];
    private $old_mode = false;

    private static $instance;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
            self::$instance->init();
        }

        return self::$instance;
    }

    public function init()
    {
        if (!self::$instance) {
            self::$instance = $this;
        }

//        $data = Xcart::app()->cache->get('config_global_cache', ['data' => [], 'checked' => []]);
//
//        $this->data = $data['data'];
//        $this->checked = $data['checked'];

//        $this->getAllData();
    }

    private function prepareData($data)
    {
        $config = [];
        if ($this->old_mode)
        {
            foreach ($data as $row) {
                if (!empty($row['category'])) {

                    if (!isset($this->data[$row['category']])) {
                        $this->data[$row['category']] = new self();
                    }

                    $this->data[$row['category']][$row['name']] = $row['value'];
                    $this->checked[$row['category']] = true;
                }
                else {
                    $config[$row['name']] = $row['value'];
                    $this->checked[$row['name']] = true;
                }
            }
        }
        else {
            foreach ($data as $row) {
                $config[$row['name']] = $row['value'];

                $this->checked[$row['name']] = true;
            }
        }
    }

    private function fetchByKey($key)
    {
        $data = null;

        if (!is_null($key))
        {
            $data = GlobalConfigModel::objects()
                                     ->filter(['name' => $key])
                                     ->orFilter(['category' => $key])
                                     ->exclude(['type' => 'separator'])
                                     ->valuesList(['name', 'value', 'category']);
            if ($data) {
                $this->prepareData($data);
            }
        }
    }

    public function setOldMode($value = true)
    {
        $this->old_mode = $value;
        return $this;
    }

    public function getAllData()
    {
        $data = GlobalConfigModel::objects()->exclude(['type' => 'separator'])->valuesList(['name', 'value', 'category']);

        $this->prepareData($data);

        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        if (!isset($this->checked[$offset])) {
            $this->fetchByKey($offset);
        }

        return $this->checked[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->data[$offset];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if (!$this->offsetExists($offset)) {
            $this->data[$offset] = new self();
        }

        $this->data[$offset] = $value;
        $this->checked[$offset] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
        $this->checked[$offset] = false;
    }

    public function current()
    {
        return current($this->data);
    }

    public function next()
    {
        next($this->data);
    }

    public function key()
    {
        return key($this->data);
    }

    public function valid()
    {
        return $this->offsetExists($this->key());
    }

    public function rewind()
    {
        return rewind($this->data);
    }

    public function saveCache()
    {
        if ($this->data) {
            Xcart::app()->cache->set('config_global_cache', ['data' => $this->data , 'checked' => $this->checked], 35);
        }
    }
}