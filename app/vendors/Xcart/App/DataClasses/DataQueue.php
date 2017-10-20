<?php
namespace Xcart\App\DataClasses;

class DataQueue extends ArrayClass
{
    public function insert($data)
    {
        $this->data[] = $data;
    }

    public function extract()
    {
        $result = $this->current();
        unset($this->data[$this->key()]);

        return $result;
    }
}