<?php

namespace Xcart\App\Form\Fields;

/**
 * Class LicenseField
 * @package Mindy\Form
 */
class LicenseField extends CheckboxField
{
    /**
     * @var array
     */
    public $htmlLabel = [];
    /**
     * @var string
     */
    public $errorMessage = 'You must agree terms';

    public $licenseTemplate = '';

    public function init()
    {
        $this->validators[] = function ($value) {
            if (!$value) {
                return $this->errorMessage;
            }

            return true;
        };

        parent::init();
    }

    public function render()
    {
        if(!empty($this->licenseTemplate)) {
            $tpl = self::renderTemplate($this->licenseTemplate);
            return $tpl . parent::render();
        }
        else {
            return parent::render();
        }
    }
}
