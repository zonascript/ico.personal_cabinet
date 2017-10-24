<?php

namespace Modules\User\Components;

use Exception;
use Mindy\Base\Mindy;
use Mindy\Helper\Creator;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Http\Cookie;
use Mindy\Orm\Model;
use Modules\User\Models\User;
use Modules\User\UserModule;

/**
 * Class Auth
 * @package Modules\User
 */
class Auth
{
    use Accessors, Configurator, UserActionsTrait;

    const AUTH_TIMEOUT_VAR = '__timeout';
    const AUTH_ABSOLUTE_TIMEOUT_VAR = '__absolute_timeout';

    /**
     * @var bool
     */
    public $autoRenewCookie = true;
    /**
     * @var bool
     */
    public $allowAutoLogin = true;
    /**
     * @var string
     */
    public $modelClass = '\Modules\User\Models\User';
    /**
     * @var integer timeout in seconds after which user is logged out if inactive.
     * If this property is not set, the user will be logged out after the current session expires
     * (c.f. {@link CHttpSession::timeout}).
     * @since 1.1.7
     */
    public $authTimeout;
    /**
     * @var integer timeout in seconds after which user is logged out regardless of activity.
     * @since 1.1.14
     */
    public $absoluteAuthTimeout;
    /**
     * Password hashers
     * @var array name => className
     */
    public $passwordHashers = [
        'mindy' => '\Modules\User\PasswordHasher\MindyPasswordHasher',
    ];
    /**
     * @var array the property values (in name-value pairs) used to initialize the identity cookie.
     * Any property of {@link CHttpCookie} may be initialized.
     * This property is effective only when {@link allowAutoLogin} is true.
     */
    public $identityCookie;
    /**
     * @var null|Model
     */
    private $_model;
    /**
     * @var
     */
    private $_keyPrefix;
    /**
     * @var array
     */
    private $_passwordHashers = [];

    /**
     * Initializes the application component.
     * This method overrides the parent implementation by starting session,
     * performing cookie-based authentication if enabled, and updating the flash variables.
     */
    public function init()
    {
        $signal = $this->getEventManager();
        $signal->handler($this, 'onAuth', [$this, 'onAuth']);

        if ($this->getIsGuest() && $this->allowAutoLogin) {
            $this->restoreFromCookie();
        } elseif ($this->autoRenewCookie && $this->allowAutoLogin) {
            $this->renewCookie();
        }

        if ($this->getIsGuest()) {
            $guest = new User();
            $guest->setAttributes([
                'username' => 'Guest',
                'is_superuser' => false,
                'is_staff' => false
            ]);
            $guest->setIsGuest(true);
            $this->setModel($guest);
        }

        $this->updateAuthStatus();
    }

    public function onAuth($user)
    {

    }

    public function getEventManager()
    {
        return Mindy::app()->signal;
    }

    public function getIsSuperUser()
    {
        return $this->getModel()->is_superuser;
    }

    /**
     * Renews the identity cookie.
     * This method will set the expiration time of the identity cookie to be the current time
     * plus the originally specified cookie duration.
     * @since 1.1.3
     */
    protected function renewCookie()
    {
        $request = Mindy::app()->getComponent('request');
        $cookies = $request->cookies;
        $cookie = $cookies->itemAt($this->getStateKeyPrefix());
        if ($cookie && !empty($cookie->value) && ($data = Mindy::app()->getSecurityManager()->validateData($cookie->value)) !== false) {
            $data = @unserialize($data);
            if (is_array($data) && isset($data[0], $data[1])) {
                list($id, $duration) = $data;
                $model = $this->loadModel($id);
                $model->setIsGuest(false);
                $this->saveToCookie($model, $duration);
            }
        }
    }

    /**
     * Updates the authentication status according to {@link authTimeout}.
     * If the user has been inactive for {@link authTimeout} seconds, or {link absoluteAuthTimeout} has passed,
     * he will be automatically logged out.
     * @since 1.1.7
     */
    protected function updateAuthStatus()
    {
        if (($this->authTimeout !== null || $this->absoluteAuthTimeout !== null) && !$this->getIsGuest()) {
            $expires = $this->getState(self::AUTH_TIMEOUT_VAR);
            $expiresAbsolute = $this->getState(self::AUTH_ABSOLUTE_TIMEOUT_VAR);

            if ($expires !== null && $expires < time() || $expiresAbsolute !== null && $expiresAbsolute < time()) {
                $this->logout(false);
            } else {
                $this->setState(self::AUTH_TIMEOUT_VAR, time() + $this->authTimeout);
            }
        }
    }

    /**
     * Logs out the current user.
     * This will remove authentication-related session data.
     * If the parameter is true, the whole session will be destroyed as well.
     * @param boolean $destroySession whether to destroy the whole session. Defaults to true. If false,
     * then {@link clearStates} will be called, which removes only the data stored via {@link setState}.
     */
    public function logout($destroySession = true)
    {
        if ($this->allowAutoLogin) {
            Mindy::app()->request->cookies->remove($this->getStateKeyPrefix());
            if ($this->identityCookie !== null) {
                $cookie = $this->createIdentityCookie($this->getStateKeyPrefix());
                $cookie->value = null;
                $cookie->expire = 0;
                Mindy::app()->request->cookies->add($cookie->name, $cookie);
            }
        }
        if ($destroySession) {
            Mindy::app()->getSession()->destroy();
        }
        $this->cleanModel();
    }

    public function cleanModel()
    {
        $this->_model = null;
        return $this;
    }

    public function loadModel($id)
    {
        $modelClass = $this->modelClass;
        return $modelClass::objects()->filter(['pk' => $id])->get();
    }

    /**
     * Populates the current user object with the information obtained from cookie.
     * This method is used when automatic login ({@link allowAutoLogin}) is enabled.
     * The user identity information is recovered from cookie.
     * Sufficient security measures are used to prevent cookie data from being tampered.
     * @see saveToCookie
     */
    protected function restoreFromCookie()
    {
        $app = Mindy::app();
        $request = $app->request;
        $cookie = $request->cookies->get($this->getStateKeyPrefix());
        if ($cookie && !empty($cookie->value) && is_string($cookie->value) && ($data = $app->getSecurityManager()->validateData($cookie->value)) !== false) {
            $data = @unserialize($data);
            if (is_array($data) && isset($data[0], $data[1])) {
                list($id, $duration) = $data;
                if ($model = $this->loadModel($id)) {
                    $model->setIsGuest(false);
                    $this->setModel($model);

                    if ($this->autoRenewCookie) {
                        $this->saveToCookie($model, $duration);
                    }
                }
            }
        }
    }

    public function getStorage()
    {
        return Mindy::app()->session;
    }

    public function login(Model $model, $duration = null)
    {
        $model->last_login = time();
        $model->save(['last_login']);

        if ($duration === null) {
            $duration = Mindy::app()->getModule('User')->loginDuration;
        }
        $this->saveToCookie($model, $duration);

        if ($this->absoluteAuthTimeout) {
            $this->getStorage()->add(self::AUTH_ABSOLUTE_TIMEOUT_VAR, time() + $this->absoluteAuthTimeout);
        }

        $model->setIsGuest(false);
        $this->setModel($model);
        $this->getEventManager()->send($this, 'onAuth', $model);

        $this->recordAction(UserModule::t('User {name} logged in', [
            '{name}' => (string)$model
        ]), $model->getModuleName());

        return !$this->getIsGuest();
    }

    /**
     * Check user is guest
     * @return bool
     */
    public function getIsGuest()
    {
        $model = $this->getModel();
        return $model === null || $model->getIsGuest();
    }

    /**
     * Creates a cookie to store identity information.
     * @param string $name the cookie name
     * @return Cookie the cookie used to store identity information
     */
    protected function createIdentityCookie($name)
    {
        $cookie = new Cookie($name, '');
        if (is_array($this->identityCookie)) {
            foreach ($this->identityCookie as $name => $value) {
                $cookie->$name = $value;
            }
        }
        return $cookie;
    }

    /**
     * @return string a prefix for the name of the session variables storing user session data.
     */
    public function getStateKeyPrefix()
    {
        if (!$this->_keyPrefix) {
            $this->_keyPrefix = md5(get_class($this) . '.' . Mindy::app()->getId());
        }
        return $this->_keyPrefix;
    }

    /**
     * Saves necessary user data into a cookie.
     * This method is used when automatic login ({@link allowAutoLogin}) is enabled.
     * This method saves user ID, username, other identity states and a validation key to cookie.
     * These information are used to do authentication next time when user visits the application.
     * @param \Mindy\Orm\Model $model
     * @param integer $duration number of seconds that the user can remain in logged-in status. Defaults to 0, meaning login till the user closes the browser.
     * @see restoreFromCookie
     */
    protected function saveToCookie(Model $model, $duration)
    {
        $app = Mindy::app();
        $cookie = $this->createIdentityCookie($this->getStateKeyPrefix());
        $cookie->expire = time() + $duration;
        $cookie->value = $app->getSecurityManager()->hashData(serialize([$model->pk, $duration]));
        $app->request->cookies->add($cookie->name, $cookie);
    }

    public function setModel(Model $model)
    {
        $this->_model = $model;
        return $this;
    }

    public function getModel()
    {
        return $this->_model;
    }

    /**
     * @param $name
     * @throws Exception
     * @return \Modules\User\PasswordHasher\IPasswordHasher
     */
    public function getPasswordHasher($name)
    {
        if (isset($this->passwordHashers[$name])) {
            if (!isset($this->_passwordHashers[$name])) {
                $this->_passwordHashers[$name] = Creator::createObject([
                    'class' => $this->passwordHashers[$name]
                ]);
            }

            return $this->_passwordHashers[$name];
        } else {
            throw new Exception("Unknown password hasher");
        }
    }
}
