<?php

namespace Modules\User\Components;

use Mindy\Base\Mindy;
use Mindy\Helper\Password;
use Mindy\Orm\Model;
use Modules\Core\Components\ParamsHelper;
use Modules\User\Models\User;

/**
 * @DEPRECATED
 * Class UserIdentity
 * @package Modules\User
 */
class UserIdentity extends BaseUserIdentity
{
    /**
     * @var string username
     */
    public $username;
    /**
     * @var string password
     */
    public $password;

    /**
     * @var \Modules\User\Models\User
     */
    private $_model;

    /**
     * @var \Mindy\Orm\Model user model
     */
    protected $user;

    const ERROR_EMAIL_INVALID = 3;
    const ERROR_INACTIVE = 4;

    /**
     * Constructor.
     * @param string $username username
     * @param string $password password
     */
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Authenticates a user based on {@link username} and {@link password}.
     * Derived classes should override this method, or an exception will be thrown.
     * This method is required by {@link IUserIdentity}.
     * @return boolean whether authentication succeeds.
     */
    public function authenticate()
    {
        $activation = ParamsHelper::get('user.user.activation');
        /** @var \Modules\User\Components\Auth $auth */
        $auth = Mindy::app()->auth;
        if (strpos($this->username, "@")) {
            $model = User::objects()->get([
                'email' => strtolower($this->username)
            ]);
        } else {
            $model = User::objects()->get([
                'username' => $this->username
            ]);
        }

        if ($model === null) {
            if (strpos($this->username, "@")) {
                $this->errorCode = self::ERROR_EMAIL_INVALID;
            } else {
                $this->errorCode = self::ERROR_USERNAME_INVALID;
            }
        } else if ($activation && !$model->is_active) {
            $this->errorCode = self::ERROR_INACTIVE;
        } else if (!$auth->getPasswordHasher($model->hash_type)->verifyPassword($this->password, $model->password)) {
            $this->errorCode = self::ERROR_PASSWORD_INVALID;
        } else {
            $this->errorCode = self::ERROR_NONE;

            $this->setModel($model);
        }
        return !$this->errorCode;
    }

    public function getId()
    {
        return $this->getModel()->pk;
    }

    public function getModel()
    {
        return $this->_model;
    }

    public function updateUserById($id)
    {
        $user = User::objects()->filter(['pk' => $id])->get();
        if ($user) {
            $this->setModel($user);
        }
    }

    public function setModel(Model $model)
    {
        $this->_model = $model;
        return $this;
    }
}
