<?php

namespace Xcart\App\Router;

use Exception;
use InvalidArgumentException;
use Xcart\App\Cli\Cli;
use Xcart\App\Helpers\Paths;
use Xcart\App\Helpers\SmartProperties;
use Traversable;
use Xcart\App\Main\Xcart;

class Router
{
    use SmartProperties;

    /**
     * @var array Default HTTP-methods
     */
    public $defaultMethods = ['GET', 'POST'];

    public $pathGet = false;

    /**
     * @var array Array of all routes (incl. named routes).
     */
    protected $_routes = array();

    /**
     * @var array Array of all named routes.
     */
    protected $_namedRoutes = array();

    /**
     * @var string Can be used to ignore leading part of the Request URL (if main file lives in subdirectory of host)
     */
    protected $_basePath = '';

    /**
     * @var string|null Path to routes file (ex: base.config.routes)
     */
    public $pathRoutes;

    public $mode = 'path'; // path | get
    public $get_param = 'path';

    /**
     * @var array Array of default match types (regex helpers)
     */
    protected $_matchTypes = array(
        'i' => '[0-9]++',
        'a' => '[0-9A-Za-z]++',
        'slug' => '[0-9A-Za-z_\-]+',
        'h' => '[0-9A-Fa-f]++',
        '*' => '.+?',
        '**' => '.++',
        '' => '[^/\.]++'
    );

    public function init()
    {
        if ($this->pathRoutes) {
            $this->collectFromFile($this->pathRoutes);
        }
    }

    /**
     * Retrieves all routes.
     * Useful if you want to process or display routes.
     * @return array All routes.
     */
    public function getRoutes()
    {
        return $this->_routes;
    }

    /**
     * Add multiple routes at once from array in the following format:
     *
     *   $routes = array(
     *      array($method, $route, $target, $name)
     *   );
     *
     * @param array $routes
     * @return void
     * @author Koen Punt
     * @throws Exception
     */
    public function addRoutes($routes)
    {
        if (!is_array($routes) && !$routes instanceof Traversable) {
            throw new Exception('Routes should be an array or an instance of Traversable');
        }
        foreach ($routes as $route) {
            call_user_func_array(array($this, 'map'), $route);
        }
    }

    /**
     * Set the base path.
     * Useful if you are running your application from a subdirectory.
     */
    public function setBasePath($basePath)
    {
        $this->_basePath = $basePath;
    }

    /**
     * Add named match types. It uses array_merge so keys can be overwritten.
     *
     * @param array $matchTypes The key is the name and the value is the regex.
     */
    public function addMatchTypes($matchTypes)
    {
        $this->_matchTypes = array_merge($this->_matchTypes, $matchTypes);
    }

    /**
     * Map a route to a target
     *
     * @param string $method One of 5 HTTP Methods, or a pipe-separated list of multiple HTTP Methods (GET|POST|PATCH|PUT|DELETE)
     * @param string $route The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
     * @param mixed $target The target where this route should point to. Can be anything.
     * @param string $name Optional name of this route. Supply if you want to reverse route this url in your application.
     * @param string $config Optional data of this route. For custom data save.
     * @throws Exception
     */
    public function map($method, $route, $target, $name = null, $config = null)
    {

        if ($route == '') {
            $route = '/';
        }

        $this->_routes[] = array($method, $route, $target, $name, $config);

        if ($name) {
            if (isset($this->_namedRoutes[$name])) {
                throw new \Exception("Can not redeclare route '{$name}'");
            } else {
                $this->_namedRoutes[$name] = $route;
            }
        }

        return;
    }

    public function absoluteUrl($routeName, array $params = [], array $query = [])
    {
        $host = '';

        if (!Cli::isCli()) {
            $host = Xcart::app()->request->getHostInfo();
        }

        return $host . $this->url($routeName, $params, $query);
    }

    /**
     * Reversed routing
     *
     * Generate the URL for a named route. Replace regexes with supplied parameters
     *
     * @param string $routeName The name of the route.
     * @param array @params Associative array of parameters to replace placeholders with.
     * @param array @query Associative array of parameters to insert in query string.
     * @return string The URL of the route with named parameters in place.
     * @throws Exception
     */
    public function url($routeName, array $params = [], array $query = [])
    {
        // Check if named route exists
        if (!isset($this->_namedRoutes[$routeName])) {
            throw new \Exception("Route '{$routeName}' does not exist.");
        }

        // Replace named parameters
        $route = $this->_namedRoutes[$routeName];
        $url = $route;

        if (preg_match_all('`(\/|\.|)\{([^:\}]*+)(?::([^:\}]*+))?\}(\?|)`', $route, $matches, PREG_SET_ORDER)) {
            $counter = 0;
            foreach ($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if ($pre) {
                    $block = substr($block, 1);
                }

                if (isset($params[$param])) {
                    $url = str_replace($block, $params[$param], $url);
                } elseif (isset($params[$counter])) {
                    $url = str_replace($block, $params[$counter], $url);
                } elseif ($optional) {
                    $url = str_replace($pre . $block, '', $url);
                } else {
                    throw new InvalidArgumentException('Incorrect params of route');
                }
                $counter++;
            }
        }

        if (strtolower($this->mode) == 'get' && !empty($this->get_param))
        {
            $url = http_build_query([$this->get_param => $url]);

            if (strpos($this->_basePath, '?') === false) {
                $url = '?' . $url;
            }
        }

        if (!empty($query)){
            if (strtolower($this->mode) == 'get' && $this->get_param) {
                unset($query[$this->get_param]);
            }

            if ($query){
                $delimiter = '?';
                if (strpos($url, '?') !== false || strpos($this->_basePath, '?') !== false) {
                    $delimiter = '&';
                }

                if ($query = http_build_query($query)) {
                    $url .= $delimiter . $query;
                }
            }
        }

        // prepend base path to route url again
        $url = $this->_basePath . $url;

        return $url;
    }

    /**
     * Match a given Request Url against stored routes
     * @param string $requestUrl
     * @param string $requestMethod
     * @return array|boolean Array with route information on success, false on failure (no match).
     */
    public function match($requestUrl = null, $requestMethod = null)
    {
        $params = array();
        $match = false;

        // set Request Url if it isn't passed as parameter
        if ($requestUrl === null) {
            $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        } elseif ($requestUrl === '') {
            $requestUrl = '/';
        }

        // strip base path from request url
        if (!empty($this->_basePath) && strpos($requestUrl, $this->_basePath)) {
            $requestUrl = substr($requestUrl, strlen($this->_basePath));
        }

        // Strip query string (?a=b) from Request Url
        if (($strpos = strpos($requestUrl, '?')) !== false && $this->pathGet == false) {
            $requestUrl = substr($requestUrl, 0, $strpos);
        }

        // set Request Method if it isn't passed as a parameter
        if ($requestMethod === null) {
            $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        }

        foreach ($this->_routes as $handler) {
            list($method, $_route, $target, $name, $config) = $handler;

            $methods = explode('|', $method);
            $method_match = false;

            // Check if request method matches. If not, abandon early. (CHEAP)
            foreach ($methods as $method) {
                if (strcasecmp($requestMethod, $method) === 0) {
                    $method_match = true;
                    break;
                }
            }

            // Method did not match, continue to next route.
            if (!$method_match) continue;

            // Check for a wildcard (matches all)
            if ($_route === '*') {
                $match = true;
            } elseif (isset($_route[0]) && $_route[0] === '@') {
                $pattern = '`' . substr($_route, 1) . '`u';
                $match = preg_match($pattern, $requestUrl, $params);
            } else {
                $route = null;
                $regex = false;
                $j = 0;
                $n = isset($_route[0]) ? $_route[0] : null;
                $i = 0;

                // Find the longest non-regex substring and match it against the URI
                while (true) {
                    if (!isset($_route[$i])) {
                        break;
                    } elseif (false === $regex) {
                        $c = $n;
                        $regex = $c === '[' || $c === '(' || $c === '.';
                        if (false === $regex && false !== isset($_route[$i + 1])) {
                            $n = $_route[$i + 1];
                            $regex = $n === '?' || $n === '+' || $n === '*' || $n === '{';
                        }
                        if (false === $regex && $c !== '/' && (!isset($requestUrl[$j]) || $c !== $requestUrl[$j])) {
                            continue 2;
                        }
                        $j++;
                    }
                    $route .= $_route[$i++];
                }

                $regex = $this->compileRoute($route);
                $match = preg_match($regex, $requestUrl, $params);
            }

            if (($match == true || $match > 0)) {

                if ($params) {
                    foreach ($params as $key => $value) {
                        if (is_numeric($key)) unset($params[$key]);
                    }
                }

                return array(
                    'target' => $target,
                    'params' => $params,
                    'name' => $name,
                    'config' => $config
                );
            }
        }
        return false;
    }

    /**
     * Compile the regex for a given route (EXPENSIVE)
     * @param $route
     * @return string
     */
    protected function compileRoute($route)
    {
        if (preg_match_all('`(\/|\.|)\{([^:\}]*+)(?::([^:\}]*+))?\}(\?|)`', $route, $matches, PREG_SET_ORDER)) {

            $matchTypes = $this->_matchTypes;
            foreach ($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if (isset($matchTypes[$type])) {
                    $type = $matchTypes[$type];
                }
                if ($pre === '.') {
                    $pre = '\.';
                }

                //Older versions of PCRE require the 'P' in (?P<named>)
                $pattern = '(?:'
                    . ($pre !== '' ? $pre : null)
                    . '('
                    . ($param !== '' ? "?P<$param>" : null)
                    . $type
                    . '))'
                    . ($optional !== '' ? '?' : null);

                $route = str_replace($block, $pattern, $route);
            }

        }
        return "`^$route$`u";
    }

    /**
     * Append routes from file
     *
     * @param $path
     *
     * @throws \Exception
     */
    public function collectFromFile($path)
    {
        $routesPath = Paths::file($path, 'php');
        $routes = include $routesPath;
        $this->collect($routes);
    }

    /**
     * Append routes from array
     *
     * @param array  $configuration
     * @param string $namespace
     * @param string $route
     * @param array  $config
     *
     * @throws \Exception
     */
    public function collect($configuration = [], $namespace = '', $route = '', $config = [])
    {
        foreach ($configuration as $item) {

            if (isset($item['route']) && isset($item['path'])) {
                $this->appendRoutes($item, $namespace, $route, $config);
            } elseif (isset($item['route']) && isset($item['target'])) {
                $this->appendRoute($item, $namespace, $route, $config);
            }
        }
    }

    /**
     * Append routes
     *
     * @param        $item
     * @param string $namespace
     * @param string $route
     * @param array  $config
     *
     * @throws \Exception
     */
    public function appendRoutes($item, $namespace = '', $route = '', $config = [])
    {
        if (isset($item['path'])) {
            $itemNamespace = isset($item['namespace']) ? $item['namespace'] : '';
            $config = (empty($item['config']) ? $config : array_replace_recursive($config, $item['config']));

            if ($itemNamespace && $namespace) {
                $itemNamespace = $namespace . ':' . $itemNamespace;
            }

            $path = isset($item['route']) ? $item['route'] : '';

            if ($path && $route) {
                $path = $route . $path;
            }

            $routesFile = Paths::file($item['path'], 'php');

            if (!$routesFile) {
                return;
            }

            $routes = include $routesFile;
            $this->collect($routes, $itemNamespace, $path, $config);
        }
    }

    /**
     * Append single route
     * 
     * @param $item
     * @param string $namespace
     * @param string $route
     * @param array $config
     * 
     * @throws Exception
     */
    public function appendRoute($item, $namespace = '', $route = '/', $config = [])
    {
        $methods = isset($item['methods']) ? $item['methods'] : ["GET", "POST"];
        $method = implode('|', $methods);
        $name = isset($item['name']) ? $item['name'] : '';

        if ($name && $namespace) {
            $name = $namespace . ':' . $name;
        }

        $path = isset($item['route']) ? $item['route'] : '';

        if ($route || $path) {
            $path = $route . $path;
        }

        $target = isset($item['target']) ? $item['target'] : null;
        $this->map($method, $path, $target, $name, (empty($item['config']) ? $config : array_replace_recursive($config, $item['config'])));
    }
}