<?php


namespace Xcart\App\Validation;

use Xcart\App\Helpers\Json;
use Xcart\App\Translate\Translate;

/**
 * Class RecaptchaValidator
 * @package Mindy\Validation
 */
class RecaptchaValidator extends Validator
{
    /**
     * @var string
     */
    public $publicKey;
    /**
     * @var string
     */
    public $secretKey;
    /**
     * @var string
     */
    public $message = "Incorrect captcha. Please try again.";

    /**
     * @param $publicKey
     * @param $secretKey
     */
    public function __construct($publicKey, $secretKey)
    {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function validate($value)
    {
        if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
            $url = strtr('https://www.google.com/recaptcha/api/siteverify?secret={secret}&response={response}&remoteip={remoteip}', [
                '{secret}' => $this->secretKey,
                '{response}' => $_POST['g-recaptcha-response'],
                '{remoteip}' => $_SERVER['REMOTE_ADDR'],
            ]);
            $data = Json::decode(file_get_contents($url));
            if (!isset($data['success']) || $data['success'] === false) {
                $this->addError(Translate::getInstance()->t('validation', $this->message, [
                    '{name}' => $this->getName()
                ]));
            }
        } else {
            $this->addError(Translate::getInstance()->t('validation', $this->message, [
                '{name}' => $this->getName()
            ]));
        }

        return $this->hasErrors() === false;
    }
}
