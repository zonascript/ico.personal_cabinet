<?php

namespace Modules\User;

use Mindy\Base\Mindy;
use Mindy\Base\Module;
use Mindy\Helper\Console;
use Mindy\Helper\Params;
use Modules\Core\CoreModule;
use Modules\User\Helpers\UserHelper;

/**
 * Class UserModule
 * @package Modules\User
 */
class UserModule extends Module
{
    /**
     * @var int Remember Me Time (seconds), defalt = 2592000 (30 days)
     */
    public $rememberMeTime = 2592000; // 30 days
    /**
     * @var string
     */
    public $loginUrl = 'user:login';
    /**
     * @var string
     */
    public $loginRedirectUrl = '/';
    /**
     * @var string
     */
    public $registrationRedirectUrl = 'user:registration_success';
    /**
     * @var int 3600 * 24 * $days
     */
    public $loginDuration = 2592000;
    /**
     * @var bool
     */
    public $sendUserCreateMail = true;
    /**
     * @var bool
     */
    public $enableRecaptcha = false;
    /**
     * @var string
     */
    public $recaptchaPublicKey;
    /**
     * @var string
     */
    public $recaptchaSecretKey;

    /**
     * @var bool
     */
    public $destroySessionAfterLogout = true;


    public static function preConfigure()
    {
        $app = Mindy::app();

        $tpl = $app->template;
        $tpl->addHelper('gravatar', function ($user, $size = 80) {
            $email = $user->email;
            $default = "http://placehold.it/" . $size . "x" . $size;
            return "http://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?d=" . urlencode($default) . "&s=" . $size;
        });
        $tpl->addHelper('login_form', ['\Modules\User\Helpers\UserHelper', 'render']);

        $signal = $app->signal;

        $signal->handler('\Modules\User\Models\UserBase', 'createUser', function ($user) use ($app) {
            $module = $app->getModule('User');
            if ($module->sendUserCreateMail) {
                $data = [
                    'current_user' => $user,
                    'sitename' => Params::get('core.core.sitename'),
                    'activation_url' => $app->request->http->absoluteUrl($app->urlManager->reverse('user:registration_activation', [
                        'key' => $user->activation_key
                    ]))
                ];
                $subject = $module::t('Successful registration');
                $message = UserHelper::renderTemplate('user/mail/registration.html', $data);

                $app->mail->fromCodeOrRaw('user.registration', $subject, $message, $user->email, $data);
            }
        });
    }

    public function getVersion()
    {
        return '1.0';
    }

    public function getName()
    {
        return self::t('Users');
    }

    public function getMenu()
    {
        return [
            'name' => $this->getName(),
            'items' => [
                [
                    'name' => self::t('Users'),
                    'adminClass' => 'UserAdmin',
                ],
                [
                    'name' => self::t('Groups'),
                    'adminClass' => 'GroupAdmin',
                ],
                [
                    'name' => self::t('Permissions'),
                    'adminClass' => 'PermissionAdmin',
                ]
            ]
        ];
    }

    /**
     * Return array of mail templates and his variables
     * @return array
     */
    public function getMailTemplates()
    {
        return [
            'registration' => [
                'user' => UserModule::t('User object'),
                'activation_url' => UserModule::t('Url with activation key'),
                'sitename' => CoreModule::t('Site name')
            ],
            'recovery' => [
                'recover_url' => UserModule::t('Url with link to recover password'),
            ],
            'changepassword' => [
                'changepassword_url' => UserModule::t('Url with link to change password'),
            ],
            'activation' => [],
        ];
    }

    public function getLoginUrl()
    {
        return Mindy::app()->urlManager->reverse($this->loginUrl);
    }
}
