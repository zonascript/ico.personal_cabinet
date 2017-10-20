<?php

namespace Xcart\App\Validation;

use Xcart\App\Translate\Translate;

/**
 * Class ImageValidator
 * @package Mindy\Validation
 */
class ImageValidator extends Validator
{
    /**
     * @var null|int
     */
    public $maxWidth = null;
    /**
     * @var null|int maximum file size or null for unlimited. Default value 2 mb.
     */
    public $maxHeight = null;
    /**
     * @var null|int
     */
    public $minWidth = null;
    /**
     * @var null|int maximum file size or null for unlimited. Default value 2 mb.
     */
    public $minHeight = null;

    public function __construct($maxWidth = null, $maxHeight = null, $minWidth = null, $minHeight = null)
    {
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
        $this->minWidth = $minWidth;
        $this->minHeight = $minHeight;
    }

    public function validate($value)
    {
        $filePath = '';
        if (is_array($value) && isset($value['tmp_name'])) {
            $filePath = $value['tmp_name'];
        } else if (is_string($value)) {
            $filePath = $value;
        }

        if (!empty($filePath) && is_file($filePath)) {
            list($width, $height, $type, $attr) = getimagesize($filePath);
            if ($this->maxWidth !== null && $width > $this->maxWidth) {
                $this->addError(Translate::getInstance()->t('validation', "Maximum image width: {size}", [
                    '{size}' => $this->maxWidth,
                ]));
            }
            if ($this->maxHeight !== null && $height > $this->maxHeight) {
                $this->addError(Translate::getInstance()->t('validation', "Maximum image height: {size}", [
                    '{size}' => $this->maxHeight,
                ]));
            }
            if ($this->minWidth !== null && $width < $this->minWidth) {
                $this->addError(Translate::getInstance()->t('validation', "Minimum image width: {size}", [
                    '{size}' => $this->minWidth,
                ]));
            }
            if ($this->minHeight !== null && $height < $this->minHeight) {
                $this->addError(Translate::getInstance()->t('validation', "Maximum image height: {size}", [
                    '{size}' => $this->minHeight,
                ]));
            }
        }

        return $this->hasErrors() === false;
    }
}