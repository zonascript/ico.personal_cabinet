<?php
namespace Modules\User\Components;


use Modules\User\Models\UserModel;
use Xcart\App\Cli\Cli;
use Xcart\App\Helpers\SmartProperties;
use Xcart\App\Interfaces\AuthInterface;
use Xcart\App\Main\Xcart;

class Auth implements AuthInterface
{
    use SmartProperties;

    /**
     * @var UserModel
     */
    protected $_user = null;

    /**
     * Login expire
     * Default: 60 days
     * @var int
     */
    public $expire = 5184000;

    /**
     * @var string
     */
    public $authCookieName = 'USER';

    /**
     * @var string
     */
//    public $authSessionName = 'USER_ID';
    public $authSessionName = 'admin_login';

    public $class = 'Modules\User\Models\UserModel';

    public function login($user, $rememberMe = true)
    {
        $this->updateSession($user);
        if ($rememberMe) {
            $this->updateCookie($user);
        }
        $this->setUser($user);
    }

    /**
     * @param bool $clearSession
     * @internal param bool $total Clear all session
     */
    public function logout($clearSession = true)
    {
        $this->removeSession($clearSession);
        $this->removeCookie();
        $this->_user = null;
    }

    public function getUser()
    {
        if (!$this->_user) {
            $this->_user = $this->fetchUser();
        }
        return $this->_user;
    }

    public function setUser($user)
    {
        $this->_user = $user;
        $this->updateCookie($user);
        $this->updateSession($user);
    }

    public function fetchUser()
    {
        $user = null;

        if (!Cli::isCli()) {
            $user = $this->getSessionUser();
            if (!$user) {
                if ($user = $this->getCookieUser()) {
                    $this->updateSession($user);
                }
            }
        }

        if (!$user) {
            $class = $this->class;
            $user = new $class();
        }

        return $user;
    }

    /**
     * Find user in database by id or login
     *
     * @param int|string $id
     * @return mixed
     */
    public function findUser($id)
    {
        $class = $this->class;
        /** @var UserModel $class */
        return $class::objects()->filter(['login' => $id])->limit(1)->get();
    }

    public function getSessionUser()
    {
        $id = $this->getSession();
        if ($id) {
            return $this->findUser($id);
        }
        return null;
    }

    public function getCookieUser()
    {
        $cookie = $this->getCookie();
        if ($cookie) {
            $data = explode(':', $cookie);
            if (count($data) == 2) {
                $id = $data[0];
                $key = $data[1];

                $user = $this->findUser($id);
                if ($user && password_verify($user->email . $user->password, $key)) {
                    return $user;
                }
            }
        }
        return null;
    }

    public function updateSession( $user)
    {
        $this->setSession($user->login);
    }

    public function updateCookie( $user)
    {
        $value = implode(':', [$user->id, password_hash($user->email . $user->password, PASSWORD_DEFAULT)]);
        $this->setCookie($value);
    }

    public function setSession($session)
    {
        Xcart::app()->request->session->add($this->authSessionName, $session);
    }

    public function getSession()
    {
        return Xcart::app()->request->session->get($this->authSessionName);
    }

    public function removeSession($clearSession = true)
    {
        if ($clearSession) {
            Xcart::app()->request->session->destroy();
        } else {
            Xcart::app()->request->session->remove($this->authSessionName);
        }
    }
    
    public function setCookie($cookie)
    {
        Xcart::app()->request->cookie->add($this->authCookieName, $cookie, time() + $this->expire, '/');
    }
    
    public function getCookie()
    {
        return Xcart::app()->request->cookie->get($this->authCookieName);
    }

    public function removeCookie()
    {
        Xcart::app()->request->cookie->remove($this->authCookieName);
    }
}