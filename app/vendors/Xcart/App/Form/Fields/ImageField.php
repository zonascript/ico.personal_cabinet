<?php

namespace Xcart\App\Form\Fields;

use League\Flysystem\File;

/**
 * Class ImageField
 * @package Mindy\Form
 */
class ImageField extends FileField
{
    public $sizeShowValue = null;

    /**
     * @var string
     */
    public $inputTemplate = 'forms/field/image/input.tpl';

    public function getSizeImage()
    {
        if(!$this->sizeShowValue){
            return $this->getCurrentFileUrl();
        }

        $value = $this->getValue();
        if ($value instanceof File) {

            $fs = $value->getFilesystem();

            if ($this->sizeShowValue) {
                $directory = pathinfo($value->getPath(), PATHINFO_DIRNAME);
                $file = pathinfo($value->getPath(), PATHINFO_BASENAME);

                $path = $directory . DIRECTORY_SEPARATOR . $this->sizeShowValue . '_' . $file;
            } else {
                $path = $value->getPath();
            }

            return $fs->getAdapter()->getUrl($path);
        }

        return null;
    }

}
