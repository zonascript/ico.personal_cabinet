<?php

namespace Xcart\App\Validation;

use Xcart\App\Translate\Translate;

/**
 * Class MaxLengthValidator
 * @package Mindy\Validation
 */
class MaxLengthValidator extends Validator
{
    public $maxLength;

    public function __construct($maxLength)
    {
        $this->maxLength = (int)$maxLength;
    }

    public function validate($value)
    {
        if (is_object($value)) {
            $this->addError(Translate::getInstance()->t('validation', "{type} is not a string", ['{type}' => gettype($value)]));
        } else if (mb_strlen((string) $value, 'UTF-8') > $this->maxLength) {
            $this->addError(Translate::getInstance()->t('validation', "Maximum length is {length}", ['{length}' => $this->maxLength]));
        }

        return $this->hasErrors() === false;
    }
}
