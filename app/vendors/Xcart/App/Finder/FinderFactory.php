<?php

namespace Xcart\App\Finder;

/**
 * TODO склеить TemplateFinder и ThemeTemplateFinder так как оба выполняют одинаковую функцию за исключением переменной $theme
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 02/04/14.04.2014 15:23
 */

use Closure;
use Xcart\App\Helpers\Paths;
use Xcart\App\Traits\Configurator;

class FinderFactory
{
    use Configurator;

    /**
     * @var string
     */
    public $theme;
    /**
     * @var array
     */
    public $finders = [];

    public $templateFinder = true;

    public $appTemplateFinder = true;

    private $_finder;

    public function init()
    {
        $appPath = Paths::get('App');

        $paths = [Paths::get('App.Modules')];

        $finders = [];

        if ($this->theme instanceof Closure) {
            $this->theme = $this->theme->__invoke();
        }

        if (!empty($this->theme)) {
            $finders[] = new ThemeTemplateFinder($appPath, $this->theme);
        }
        if ($this->templateFinder) {
            $finders[] = new TemplateFinder($appPath);
        }
        if ($this->appTemplateFinder) {
            $finders[] = new AppTemplateFinder($appPath, $paths);
        }
        $this->_finder = new Finder($finders);
    }

    public function __set($key, $value)
    {
        $this->_finder->__set($key, $value);
    }

    public function __get($name)
    {
        return $this->_finder->__get($name);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->_finder, $name], $arguments);
    }
}
