<?php

namespace Xcart\App\Form\Fields;

class TimeStampField extends CharField
{
    public $format = 'Y-m-d H:m:s';

    public $autoNow = false;

    public function setValue($value)
    {
        if (is_numeric($value)) {
            $value = date($this->format, $value);
        } else if (is_string($value)) {
            $value = date($this->format, strtotime($value));
        }

        return parent::setValue($value);
    }
}
