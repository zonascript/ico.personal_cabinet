<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 08/09/14.09.2014 18:27
 */

namespace Modules\User\Permissions;

use Mindy\Helper\Traits\Accessors;

/**
 * CAccessRule represents an access rule that is managed by {@link CAccessControlFilter}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.web.auth
 * @since 1.0
 */
class Rule
{
    use Accessors;

    /**
     * @var boolean whether this is an 'allow' rule or 'deny' rule.
     */
    public $allow;
    /**
     * @var array list of action IDs that this rule applies to. The comparison is case-insensitive.
     * If no actions are specified, rule applies to all actions.
     */
    public $actions;
    /**
     * @var array list of controller IDs that this rule applies to. The comparison is case-insensitive.
     */
    public $controllers;
    /**
     * @var array list of user names that this rule applies to. The comparison is case-insensitive.
     * If no user names are specified, rule applies to all users.
     */
    public $users;
    /**
     * @var array list of roles this rule applies to. For each role, the current user's
     * {@link CWebUser::checkAccess} method will be invoked. If one of the invocations
     * returns true, the rule will be applied.
     * Note, you should mainly use roles in an "allow" rule because by definition,
     * a role represents a permission collection.
     * @see CAuthManager
     */
    public $groups;
    /**
     * @var array IP patterns.
     */
    public $ips;
    /**
     * @var array list of request types (e.g. GET, POST) that this rule applies to.
     */
    public $verbs;
    /**
     * @var \Closure
     */
    public $expression;
    /**
     * @var string the error message to be displayed when authorization is denied by this rule.
     * If not set, a default error message will be displayed.
     * @since 1.1.1
     */
    public $message;
    /**
     * @var mixed the denied method callback that will be called once the
     * access is denied. It replaces the behavior that shows an error message.
     * It can be a valid PHP callback including class method name (array(ClassName/Object, MethodName)),
     * or anonymous function (PHP 5.3.0+). For more information, on different options, check
     * @link http://www.php.net/manual/en/language.pseudo-types.php#language.types.callback
     * The function/method signature should be as follows:
     * <pre>
     * function foo($rule) { ... }
     * </pre>
     * where $rule is this access rule.
     *
     * @since 1.1.11
     */
    public $deniedCallback;


    /**
     * Checks whether the Web user is allowed to perform the specified action.
     * @param \Modules\User\Models\User $user the user object
     * @param \Mindy\Controller\BaseController $controller the controller currently being executed
     * @param \Mindy\Controller\Action $action the action to be performed
     * @param string $ip the request IP address
     * @param string $verb the request verb (GET, POST, etc.)
     * @return integer 1 if the user is allowed, -1 if the user is denied, 0 if the rule does not apply to the user
     */
    public function isUserAllowed($user, $controller, $action, $ip, $verb)
    {
        if (
            $this->isActionMatched($action) &&
            $this->isUserMatched($user) &&
            $this->isGroupMatched($user) &&
            $this->isIpMatched($ip) &&
            $this->isVerbMatched($verb) &&
            $this->isControllerMatched($controller) &&
            $this->isExpressionMatched($user)
        ) {
            return $this->allow ? 1 : -1;
        } else {
            return 0;
        }
    }

    /**
     * @param \Mindy\Controller\Action $action the action
     * @return boolean whether the rule applies to the action
     */
    protected function isActionMatched($action)
    {
        return empty($this->actions) || in_array(strtolower($action->getId()), $this->actions);
    }

    /**
     * @param \Mindy\Controller\BaseController $controller the controller
     * @return boolean whether the rule applies to the controller
     */
    protected function isControllerMatched($controller)
    {
        return empty($this->controllers) || in_array(strtolower($controller->getUniqueId()), $this->controllers);
    }

    /**
     * @param \Modules\User\Models\User $user the user
     * @return boolean whether the rule applies to the user
     */
    protected function isUserMatched($user)
    {
        if (empty($this->users)) {
            return true;
        }

        foreach ($this->users as $u) {
            if ($u === '*') {
                return true;
            } elseif ($u === '?' && $user->getIsGuest()) {
                return true;
            } elseif ($u === '@' && !$user->getIsGuest()) {
                return true;
            } elseif (!strcasecmp($u, $user->username)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param \Modules\User\Models\User $user the user object
     * @return boolean whether the rule applies to the role
     */
    protected function isGroupMatched($user)
    {
        if (empty($this->groups)) {
            return true;
        }

        foreach ($this->groups as $key => $role) {
            if (is_numeric($key) && $user->can($role)) {
                return true;
            } else if ($user->can($key, $role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $ip the IP address
     * @return boolean whether the rule applies to the IP address
     */
    protected function isIpMatched($ip)
    {
        if (empty($this->ips)) {
            return true;
        }
        foreach ($this->ips as $rule) {
            if ($rule === '*' || $rule === $ip || (($pos = strpos($rule, '*')) !== false && !strncmp($ip, $rule, $pos))) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $verb the request method
     * @return boolean whether the rule applies to the request
     */
    protected function isVerbMatched($verb)
    {
        return empty($this->verbs) || in_array(strtolower($verb), $this->verbs);
    }

    /**
     * @param \Modules\User\Models\User $user the user
     * @return boolean the expression value. True if the expression is not specified.
     */
    protected function isExpressionMatched($user)
    {
        $exp = $this->expression;
        if ($exp instanceof \Closure) {
            return $exp($user);
        }
        return true;
    }
}