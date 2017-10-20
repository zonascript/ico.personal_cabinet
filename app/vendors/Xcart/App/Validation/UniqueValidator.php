<?php


namespace Xcart\App\Validation;

use Xcart\App\Translate\Translate;

/**
 * Class UniqueValidator
 * @package Mindy\Validation
 */
class UniqueValidator extends Validator
{
    /**
     * @var string
     */
    public $message = "Must be a unique";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
    }

    public function validate($value)
    {
        $model = $this->getModel();
        $qs = $model::objects()->filter([$this->getName() => $value]);
        if (!$model->getIsNewRecord()) {
            $qs->exclude(['pk' => $model->pk]);
        }

        if ($qs->count() > 0) {
            $this->addError(Translate::getInstance()->t('validation', $this->message, [
                '{name}' => $this->name
            ]));
        }

        return $this->hasErrors() === false;
    }
}