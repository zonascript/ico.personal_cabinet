<?php

namespace Xcart\App\Form\Fields;


/**
 * Class RecaptchaField
 * @package Mindy\Form
 */
class RecaptchaField extends HiddenField
{
    /**
     * @var string
     */
    public $apiUrl = "<script src='https://www.google.com/recaptcha/api.js'></script>";
    /**
     * @var string
     */
    public $recaptchaTemplate = "<div class='g-recaptcha' data-sitekey='{publicKey}'></div>";
    /**
     * @var string
     */
    public $publicKey;
    /**
     * @var string
     */
    public $secretKey;

    public function init()
    {
        $this->validators[] = new RecaptchaValidator($this->publicKey, $this->secretKey);
    }

    public function render()
    {
        return implode("\n", [
            $this->apiUrl,
            strtr($this->recaptchaTemplate, ["{publicKey}" => $this->publicKey]),
            parent::renderInput(),
            $this->renderErrors()
        ]);
    }
}
