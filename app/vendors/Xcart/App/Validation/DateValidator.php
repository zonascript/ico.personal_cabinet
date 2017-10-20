<?php

namespace Xcart\App\Validation;

use DateTime;
use Xcart\App\Translate\Translate;

/**
 * Class DateValidator
 * @package Mindy\Validation
 */
class DateValidator extends Validator
{
    /**
     * @var string Y-m-d or Y-m-d H:i:s as example
     */
    public $format = 'Y-m-d';

    public function __construct($format = 'Y-m-d')
    {
        $this->format = $format;
    }

    public function validate($value)
    {
        if (is_object($value) && !$value instanceof DateTime) {
            $this->addError(Translate::getInstance()->t('validation', "{type} is not a string or DateTime object", ['{type}' => gettype($value)]));
        } else {
            $dateTime = DateTime::createFromFormat($this->format, $value);
            if ($dateTime === false || $dateTime->format($this->format) != $value) {
                $this->addError(Translate::getInstance()->t('validation', 'Incorrect date format'));
            }
        }

        return $this->hasErrors() === false;
    }
}
