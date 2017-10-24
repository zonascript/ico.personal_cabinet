<?php

namespace Modules\User\Permissions;

use HttpException;
use Mindy\Base\Mindy;
use Mindy\Controller\Filter;
use Mindy\Helper\Creator;
use Mindy\Http\Traits\HttpErrors;
use Mindy\Locale\Translate;
use ReflectionClass;

/**
 * CAccessControlFilter performs authorization checks for the specified actions.
 *
 * By enabling this filter, controller actions can be checked for access permissions.
 * When the user is not denied by one of the security rules or allowed by a rule explicitly,
 * he will be able to access the action.
 *
 * For maximum security consider adding
 * <pre>array('deny')</pre>
 * as a last rule in a list so all actions will be denied by default.
 *
 * To specify the access rules, set the {@link setRules rules} property, which should
 * be an array of the rules. Each rule is specified as an array of the following structure:
 * <pre>
 * array(
 *   'allow',  // or 'deny'
 *
 *   // optional, list of action IDs (case insensitive) that this rule applies to
 *   // if not specified or empty, rule applies to all actions
 *   'actions'=>array('edit', 'delete'),
 *
 *   // optional, list of controller IDs (case insensitive) that this rule applies to
 *   'controllers'=>array('post', 'admin/user'),
 *
 *   // optional, list of usernames (case insensitive) that this rule applies to
 *   // Use * to represent all users, ? guest users, and @ authenticated users
 *   'users'=>array('thomas', 'kevin'),
 *
 *   // optional, list of roles (case sensitive!) that this rule applies to.
 *   'roles'=>array('admin', 'editor'),
 *
 *   // since version 1.1.11 you can pass parameters for RBAC bizRules
 *   'roles'=>array('updateTopic'=>array('topic'=>$topic))
 *
 *   // optional, list of IP address/patterns that this rule applies to
 *   // e.g. 127.0.0.1, 127.0.0.*
 *   'ips'=>array('127.0.0.1'),
 *
 *   // optional, list of request types (case insensitive) that this rule applies to
 *   'verbs'=>array('GET', 'POST'),
 *
 *   // optional, a PHP expression whose value indicates whether this rule applies
 *   // The PHP expression will be evaluated using {@link evaluateExpression}.
 *   // A PHP expression can be any PHP code that has a value. To learn more about what an expression is,
 *   // please refer to the {@link http://www.php.net/manual/en/language.expressions.php php manual}.
 *   'expression'=>'!$user->isGuest && $user->level==2',
 *
 *   // optional, the customized error message to be displayed
 *   // This option is available since version 1.1.1.
 *   'message'=>'Access Denied.',
 *
 *   // optional, the denied method callback name, that will be called once the
 *   // access is denied, instead of showing the customized error message. It can also be
 *   // a valid PHP callback, including class method name (array(ClassName/Object, MethodName)),
 *   // or anonymous function (PHP 5.3.0+). The function/method signature should be as follows:
 *   // function foo($user, $rule) { ... }
 *   // where $user is the current application user object and $rule is this access rule.
 *   // This option is available since version 1.1.11.
 *   'deniedCallback'=>'redirectToDeniedMethod',
 * )
 * </pre>
 *
 * @property array $rules List of access rules.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.web.auth
 * @since 1.0
 */
class PermissionControlFilter extends Filter
{
    public $enablePermissions = false;
    /**
     * @var string the error message to be displayed when authorization fails.
     * This property can be overridden by individual access rule via {@link CAccessRule::message}.
     * If this property is not set, a default error message will be displayed.
     * @since 1.1.1
     */
    public $message;
    /**
     * @var array
     */
    public $deniedCallback = [];
    /**
     * @var Rule
     */
    public $deniedRule = null;
    /**
     * @var array
     */
    private $_rules = [];
    /**
     * @var array
     */
    protected $_allowedActions = [];

    /**
     * Проверка разрешенеия текущего действия (action) на выполнение
     * @param $action
     * @return bool
     */
    public function isAllowedAction($action)
    {
        if ($this->_allowedActions === ['*'] || in_array($action, $this->_allowedActions)) {
            return true;
        }

        return false;
    }

    protected function defaultPreFilter($filterChain)
    {
        $app = Mindy::app();
        /** @var \Modules\User\Models\User $user */
        $user = $app->user;

        $http = $app->request->http;
        $verb = $http->getRequestType();
        $ip = $http->getUserHostAddress();

        foreach ($this->getRules() as $rule) {
            $allow = $rule->isUserAllowed($user, $filterChain->controller, $filterChain->action, $ip, $verb);
            if ($allow > 0) {
                // allowed
                return true;
            } elseif ($allow < 0) {
                // denied
                if (isset($rule->deniedCallback)) {
                    $this->deniedCallback = $rule->deniedCallback;
                    $this->deniedRule = $rule;
                }
                return false;
            }
        }

        return false;
    }

    protected function permissionPreFilter($filterChain)
    {
        $user = Mindy::app()->getUser();
        /** @var \Mindy\Controller\BaseController|\Modules\Core\Controllers\Controller $controller */
        $controller = $filterChain->controller;
        $action = strtolower($filterChain->action->getId());

        // Проверка на разрешенные actions
        if ($this->isAllowedAction($action) === true) {
            return true;
        }

        $reflect = new ReflectionClass($controller);
        $code = strtolower(str_replace('\\', '.', $reflect->getNamespaceName()) . '.' . $reflect->getShortName());

        // Проверяем права доступа на все actions контроллера или проверяем имя текущего action
        if ($user->can($code . '.' . $action) || $user->can($code . '.*')) {
            return true;
        }

        return false;
    }

    /**
     * Performs the pre-action filtering.
     * @param \Mindy\Controller\FilterChain $filterChain the filter chain that the filter is on.
     * @return bool whether the filtering process should continue and the action
     * should be executed.
     * @throws HttpException
     */
    protected function preFilter($filterChain)
    {
        $accessRulesState = $this->defaultPreFilter($filterChain);
        if ($accessRulesState === false) {
            return call_user_func($this->deniedCallback, $this->deniedRule);
        }

        if ($this->enablePermissions) {
            $permissionRulesState = $this->permissionPreFilter($filterChain);
            if ($permissionRulesState === false) {
                return call_user_func($this->deniedCallback, $this->deniedRule);
            }
        }

        return true;
    }

    /**
     * Устанавливаем разрешенные actions. array('*') означает разрешение на выполнение
     * всех actions выполняемого контроллера
     * @param $allowedActions
     */
    public function setAllowedActions($allowedActions)
    {
        if (is_array($allowedActions)) {
            $this->_allowedActions = $allowedActions;
        }
    }

    /**
     * @return Rule[] list of access rules.
     */
    public function getRules()
    {
        return $this->_rules;
    }

    /**
     * @param array $rules list of access rules.
     */
    public function setRules($rules)
    {
        foreach ($rules as $rule) {
            if (is_array($rule)) {
                $this->_rules[] = Creator::createObject(array_merge([
                    'class' => Rule::class
                ], $rule));
            }
        }
    }

    /**
     * Resolves the error message to be displayed.
     * This method will check {@link message} and {@link CAccessRule::message} to see
     * what error message should be displayed.
     * @param Rule $rule the access rule
     * @return string the error message
     * @since 1.1.1
     */
    protected function resolveErrorMessage($rule)
    {
        if ($rule->message !== null) {
            return $rule->message;
        } elseif ($this->message !== null) {
            return $this->message;
        } else {
            return Translate::getInstance()->t('base', 'You are not authorized to perform this action.');
        }
    }
}