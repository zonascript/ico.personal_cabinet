<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company HashStudio
 * @site http://hashstudio.ru
 * @date 07/07/16 08:35
 */

namespace Xcart\App\Template;


use Fenom;
use Xcart\App\Helpers\Paths;
use Xcart\App\Helpers\SmartProperties;
use Xcart\App\Main\Xcart;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class TemplateManager
{
    use SmartProperties;

    /**
     * @var Fenom
     */
    protected $_renderer;

    public $forceCompile = false;
    public $forceInclude = false;
    public $forceVerify = false;
    public $autoReload = true;
    public $autoEscape = true;

    public $templateFolder = 'templates';
    public $librariesFolder = 'TemplateLibraries';
    public $cacheFolder = 'templates_cache';

    public function init()
    {
        $paths = $this->collectTemplatesPaths();
        $provider = new FenomTemplateProvider($paths);
        $cacheFolder = Paths::get('runtime.' . $this->cacheFolder);
        if (!is_dir($cacheFolder)) {
            mkdir($cacheFolder, 0777, true);
        }
        $this->_renderer = new Fenom($provider);
        $this->_renderer->setCompileDir($cacheFolder);
        $this->_renderer->setOptions([
            'force_compile' => $this->forceCompile,
            'force_include' => $this->forceInclude,
            'force_verify' => $this->forceVerify,
            'auto_reload' => $this->autoReload,
            'auto_escape' => $this->autoEscape
        ]);
        $this->extendRenderer();
        $this->loadLibraries();
    }

    /**
     * @extension modifier
     * @name info
     * @return Fenom
     */
    public function getRenderer()
    {
        return $this->_renderer;
    }

    /**
     * @return array Paths of templates
     */
    protected function collectTemplatesPaths()
    {
        $modulesPath = Paths::get('Modules');
        $activeModules = Xcart::app()->getModulesList();
        $paths = [
            Paths::get('base') . DIRECTORY_SEPARATOR . $this->templateFolder
        ];
        foreach ($activeModules as $module) {
            $paths[] = implode(DIRECTORY_SEPARATOR, [$modulesPath, $module, $this->templateFolder]);
        }
        return $paths;
    }

    public function render($template, $params = [])
    {
        return $this->_renderer->fetch($template, $params);
    }

    public function renderString($source, $params = [])
    {
        return $this->_renderer->compileCode($source)->fetch($params);
    }

    public function extendRenderer()
    {
        $this->_renderer->addModifier('safe_element', function($variable, $param, $default = '') {
            return isset($variable[$param]) ? $variable[$param] : $default;
        });
        $this->_renderer->addModifier('not_in', function($variable, $array) {
            return !array_key_exists($variable, $array);
        });

        $this->_renderer->addAccessorSmart("app", "app", Fenom::ACCESSOR_PROPERTY);
        $this->_renderer->app = Xcart::app();

        $this->_renderer->addAccessorSmart("request", "request", Fenom::ACCESSOR_PROPERTY);
        $this->_renderer->request = Xcart::app()->request;

        //Not module class
//        $this->_renderer->addAccessorSmart("user", "user", Fenom::ACCESSOR_PROPERTY);
//        $this->_renderer->user = Xcart::app()->getUser();

        $this->_renderer->addModifier('class', function($object) {
            if (is_object($object)) {
                return get_class($object);
            }
            return null;
        });

        $this->_renderer->addFunction('url', function($params) {
            list($route, $attributes) =$this->prepareUrlTag($params);

            return Xcart::app()->router->url($route, $attributes);
        });

        $this->_renderer->addFunction('absoluteUrl', function($params) {
            list($route, $attributes) =$this->prepareUrlTag($params);

            return Xcart::app()->router->absoluteUrl($route, $attributes);
        });
    }

    public function prepareUrlTag($params)
    {
        $route = isset($params['route']) ? $params['route'] : null;
        if (!$route) {
            $route = isset($params[0]) ? $params[0] : null;
        }

        $attributes = isset($params['params']) ? $params['params'] : [];
        if (!$attributes) {
            $attributes = (isset($params[1]) && is_array($params[1])) ? $params[1] : [];

            if (!$attributes && count($params) > 1) {
                foreach ($params as $k => $v) {
                    if (!is_numeric($k)) {
                        $attributes[$k] = $v;
                    }
                }
            }
        }

        return [$route, $attributes];
    }

    public function loadLibraries()
    {
        $modulesPath = Paths::get('Modules');
        $activeModules = Xcart::app()->getModulesList();
        $classes = [];
        foreach ($activeModules as $module) {
            $path = implode(DIRECTORY_SEPARATOR, [$modulesPath, $module, $this->librariesFolder]);
            if (is_dir($path)) {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename)
                {
                    // filter out "." and ".."
                    if ($filename->isDir()) continue;
                    $name = $filename->getBasename('.php');
                    $classes[] = implode('\\', ['Modules', $module, $this->librariesFolder, $name]);
                }
            }
        }
        foreach ($classes as $class) {
            if (class_exists($class) && is_a($class, TemplateLibrary::className(), true)) {
                $class::load($this->getRenderer());
            }
        }
    }
}