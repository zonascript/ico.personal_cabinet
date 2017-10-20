<?php
namespace Xcart\App\Application;

use Exception;
use Modules\User\Models\UserModel;
use Xcart\App\Cli\Cli;
use Xcart\App\Controller\Controller;
use Xcart\App\Exceptions\InvalidConfigException;
use Xcart\App\Exceptions\NotFoundHttpException;
use Xcart\App\Exceptions\UnknownPropertyException;
use Xcart\App\Helpers\Creator;
use Xcart\App\Helpers\Paths;
use Xcart\App\Interfaces\AuthInterface;
use Xcart\App\Main\ComponentsLibrary;
use Xcart\App\Request\CliRequest;
use Xcart\App\Request\HttpRequest;

/**
 * Class Application
 *
 * @property \Xcart\App\Orm\ConnectionManager $db DB connection
 * @property \Xcart\App\Middleware\MiddlewareManager $middleware Middleware
 * @property \Xcart\App\Router\Router $router Url manager, router
 * @property \Xcart\App\Request\HttpRequest|\Xcart\App\Request\CliRequest $request Request
 * @property \Xcart\App\Template\TemplateManager $template Template manager
 * @property \Xcart\App\Interfaces\AuthInterface $auth Authorization component
 * @property \Xcart\App\Cache\Cache $cache Cache component
 * @property \Xcart\App\Event\EventManager $event Event component
 * @property \Xcart\App\Storage\Storage $storage File storage component
 * @property \Xcart\App\Logger\LoggerManager $logger Logging system component
 * @property \Xcart\App\Components\Breadcrumbs $breadcrumbs
 * @property \Xcart\App\Components\Flash $flash
 * @property \Modules\Mail\Components\Mailer $mail Mailer
 *
 * @property UserModel $user
 * 
 * @package Xcart\App\Application
 */
class Application
{
    use ComponentsLibrary;

    public $name = 'Application';
    public $exit_on_end = true;
    public $globals = [];
    public $locale = [
        'language' => 'ru',
        'sourceLanguage' => 'en',
        'charset' => 'utf-8',
    ];

    protected $_modules = [];
    protected $_modulesConfig = [];
    protected $_isRun = false;

    public $autoloadComponents = [];

    public function init()
    {
        $this->registerGlobals();
        $this->_provideModuleEvent('onApplicationInit');
        $this->setUpPaths();
        $this->autoload();
    }

    public function registerGlobals()
    {
        foreach ($this->globals as $var => $val)
        {
            $GLOBALS[$var] = $val;
        }
    }

    public function setPaths($paths)
    {
        foreach ($paths as $name => $path) {
            Paths::add($name, $path);
        }
    }

    public function autoload()
    {
        foreach ($this->autoloadComponents as $name) {
            $this->getComponent($name);
        }
    }

    public function setModules($config = [])
    {
        $this->_modulesConfig = $this->prepareModulesConfigs($config);
    }

    public function prepareModulesConfigs($rawConfig)
    {
        $configs = [];
        foreach ($rawConfig as $key => $module) {
            $name = null;
            if (is_string($key)) {
                $name = $key;
            } elseif (is_string($module)) {
                $name = $module;
            }

            if (!$name) {
                throw new InvalidConfigException("Unable to configure module {$key}");
            }

            $name = ucfirst($name);
            $class = '\\Modules\\' . $name . '\\' . $name . 'Module';
            $config = [];
            if (is_array($module)) {
                $config = $module;
            }
            if ($class && $name) {
                $configs[$name] = array_merge($config, [
                    'class' => $class
                ]);
            }
        }
        return $configs;
    }

    public function getModule($name)
    {
        if (!isset($this->_modules[$name])) {
            $config = $this->getModuleConfig($name);
            if (!is_null($config)) {
                $this->_modules[$name] = Creator::createObject($config);
            } else {
                throw new UnknownPropertyException("Module with name" . $name . " not found");
            }
        }

        return $this->_modules[$name];
    }

    public function getModuleConfig($name)
    {
        if (array_key_exists($name, $this->_modulesConfig)) {
            return $this->_modulesConfig[$name];
        }
        return null;
    }

    public function getModulesList()
    {
        return array_keys($this->_modulesConfig);
    }

    public function getModulesConfig()
    {
        return $this->_modulesConfig;
    }

    protected function _provideModuleEvent($event, $args = [])
    {
        foreach ($this->_modulesConfig as $name => $config) {
            $class = $config['class'];
            forward_static_call_array([$class, $event], $args);
        }
    }

    public function setUpPaths()
    {
        $basePath = Paths::get('base');
        if (!is_dir($basePath)) {
            throw new InvalidConfigException('Base path must be a valid directory. Please, set up correct base path in "paths" section of configuration.');
        }

        $runtimePath = Paths::get('runtime');
        if (!$runtimePath) {
            $runtimePath = Paths::get('base.runtime');
            Paths::add('runtime', $runtimePath);
        }
        if (!is_dir($runtimePath) || !is_writable($runtimePath)) {
            throw new InvalidConfigException('Runtime path must be a valid and writable directory. Please, set up correct runtime path in "paths" section of configuration.');
        }

        $modulesPath = Paths::get('Modules');
        if (!$modulesPath) {
            $modulesPath = Paths::get('base.Modules');
            Paths::add('Modules', $modulesPath);
        }
        if (!is_dir($modulesPath)) {
            throw new InvalidConfigException('Modules path must be a valid. Please, set up correct modules path in "paths" section of configuration.');
        }
    }

    public function isRun()
    {
        return $this->_isRun;
    }

    public function run()
    {
        $this->beforeRun();
        $this->handleRequest();
    }

    public function beforeRun()
    {
        if ($this->getIsWebMode() && $this->hasComponent('middleware')) {
            $this->middleware->processRequest($this->request);
        }

        $this->_provideModuleEvent('onApplicationRun');
        register_shutdown_function([$this, 'end'], 0);

        $this->_isRun = true;
    }

    public function end($status = 0, $response = null, $force = false)
    {
        $this->_provideModuleEvent('onApplicationEnd', [$status, $response]);

        $this->event->trigger('app:end');

        if ($this->exit_on_end || $force) {
            exit($status);
        }
    }

    public function handleRequest()
    {
        if ($this->getIsWebMode()) {
            $this->handleWebRequest();
        } else {
            $this->handleCliRequest();
        }
    }

    /**
     * @return bool
     */
    public static function getIsCliMode()
    {
        return Cli::isCli();
    }

    /**
     * @return bool
     */
    public static function getIsWebMode()
    {
        return !self::getIsCliMode();
    }

    public function getUser()
    {
        /** @var AuthInterface $auth */
        if ($auth = $this->getComponent('auth')) {
            return $auth->getUser();
        }
        return null;
    }
    public function handleWebRequest()
    {
        /** @var HttpRequest $request */
        $request = $this->request;
        $router = $this->router;

        $match = $router->match($request->getUrl(), $request->getMethod());

        if (empty($match)) {
            throw new NotFoundHttpException("Page not found");
        }

        if (is_array($match['target']) && isset($match['target'][0])) {
            $controllerClass = $match['target'][0];
            $action = isset($match['target'][1]) ? $match['target'][1] : null;
            $params = $match['params'];

            /** @var Controller $controller */
            $controller = new $controllerClass($this->request);
            $controller->run($action, $params);
        } elseif (is_callable($match['target'])) {
            $fn = $match['target'];
            $fn($this->request, $match['params']);
        }
    }

    public function handleCliRequest()
    {
        /** @var CliRequest $request */
        $request = $this->request;
        list($module, $command, $action, $arguments) = $request->parse();
        if ($module && $command) {
            $module = ucfirst($module);
            $command = ucfirst($command);
            $class = '\\Modules\\' . $module . '\\Commands\\' . $command . 'Command';
            if (class_exists($class)) {
                $command = new $class();
                if (method_exists($command, $action)) {
                    $command->{$action}($arguments);
                } else {
                    throw new Exception("Method '{$action}' of class '{$class}' does not exist");
                }
            } else {
                throw new Exception("Class '{$class}' does not exist");
            }
        } else {
            $data = $request->getCommandsList();
            echo 'List of available commands' . PHP_EOL . PHP_EOL;
            foreach ($data as $name => $commands) {
                echo 'Module: ' . $name . PHP_EOL;
                foreach ($commands as $command => $description) {
                    echo $command . ($description ? ' - '. $description : '') . PHP_EOL;
                }
                echo PHP_EOL;
            }
            echo  'Usage example:' . PHP_EOL . 'php index.php Base Db';
        }
    }
}