<?php

namespace Modules\Translate\Helpers;

use Exception;
use Mindy\Base\Mindy;
use Mindy\Helper\Text;
use Mindy\Locale\Translate;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

/**
 * Created by PhpStorm.
 * User: antonokulov
 * Date: 10/06/14
 * Time: 18:08
 */
class TranslateHelper
{
    const DEFAULT_DICT = 'main';

    const PHP_FULL = 0;
    const PHP_MODULE = 1;
    const PHP_MESSAGE = 2;
    const PHP_DICT = 3;

    const TWIG_FULL = 0;
    const TWIG_MESSAGE = 1;
    const TWIG_MODULE_DICT = 2;

    protected $_collected = [];
    protected $_modules = [];
    public $languages = [];

    protected $_dictionaries;
    protected $_appPath;
    protected $_modulesPath = 'Modules';
    protected $_messagesPath = 'messages';

    public $onlyAdditional = false;
    public $onlyModule = null;

    public function __construct()
    {
        $this->_appPath = Mindy::app()->getBasePath();
    }

    public function getModulesPath()
    {
        return join(DIRECTORY_SEPARATOR, [$this->_appPath, $this->_modulesPath]);
    }

    public function getModuleMessagesPath($module, $lang, $dict)
    {
        $module = ucfirst($module);
        $dict = $dict . '.php';
        return join(DIRECTORY_SEPARATOR, [$this->getModulesPath(), $module, $this->_messagesPath, $lang, $dict]);
    }

    public function atModulesPath($path)
    {
        $modulesPath = $this->getModulesPath() . '/';

        if (strpos($path, $modulesPath) === 0) {
            $atModulesPath = str_replace($modulesPath , '', $path);
            $parts = explode(DIRECTORY_SEPARATOR, $atModulesPath);
            return $parts;
        }
        return null;
    }

    public function getModuleByPath($path)
    {
        $atModules = $this->atModulesPath($path);
        if ($atModules) {
            if (isset($atModules[0])) {
                return $atModules[0];
            }
        }
        return null;
    }

    public function isModuleFile($path)
    {
        $atModules = $this->atModulesPath($path);
        if ($atModules) {
            if (isset($atModules[1]) && isset($atModules[0])) {
                $module = $atModules[0];
                $filename = $atModules[1];

                if ($filename == $module . 'Module.php') {
                    return true;
                }
            }
        }
        return null;
    }

    public function isModelFile($path)
    {
        $atModules = $this->atModulesPath($path);
        if ($atModules) {
            if (isset($atModules[2]) && isset($atModules[1]) && isset($atModules[0])) {
                $folder = $atModules[1];

                if ($folder == 'Models') {
                    return true;
                }
            }
        }
        return false;
    }

    public function collect($module)
    {
        if (!$this->languages) {
            $t = Translate::getInstance()->getLanguage();
            $this->languages = [$t];
        }
        if ($module) {
            $this->onlyModule = $module;
        }
        $this->collectPath(Mindy::app()->getBasePath());
        $this->processSave();
    }

    public function collectDictionaries($lang)
    {
        $this->_dictionaries = [];

        $this->collectDirectory($this->getModulesPath(), $lang);

        return $this->_dictionaries;
    }

    public function collectDirectory($directory, $lang)
    {
        $Directory = new RecursiveDirectoryIterator($directory);
        $Iterator = new RecursiveIteratorIterator($Directory);

        $regex = strtr("/^.+{ds}{module}{ds}messages{ds}{lang}{ds}{dict}\.php$/i", [
            '{ds}' => '\\' . DIRECTORY_SEPARATOR,
            '{lang}' => $lang,
            '{module}' => '(\w+)',
            '{dict}' => '(\w+)'
        ]);
        $Regex = new RegexIterator($Iterator, $regex, RecursiveRegexIterator::GET_MATCH);

        foreach ($Regex as $file) {
            $filename = $file[0];
            $module = $file[1];
            $dict = $file[2];

            $this->collectDictionary($filename, $module, $dict);
        }
    }

    protected function collectDictionary($filename, $module, $dict)
    {
        if (!isset($this->_dictionaries[$module]))
            $this->_dictionaries[$module] = [];

        $this->_dictionaries[$module][$dict] = $this->loadDictionary($filename);
    }

    protected function loadDictionary($filename)
    {
        try {
            $dict = require($filename);
        } catch (Exception $e) {
            $dict = array();
        }
        return $dict;
    }

    public function updateDict($module, $dict, $lang, $message, $translated)
    {
        $dict_path = $this->getModuleMessagesPath($module, $lang, $dict);
        $current_dict = [];
        if (is_file($dict_path)) {
            $current_dict = require($dict_path);
        }
        $dict = array_merge($current_dict, [
            $message => $translated
        ]);
        $this->saveDict($dict_path, $dict);
        return true;
    }

    public function saveDict($dict_path, $dict)
    {
        if (!is_file($dict_path)) {
            $dict_dir = dirname($dict_path);
            if (!is_dir($dict_dir)) {
                mkdir($dict_dir, 0777, true);
            }
        }
        file_put_contents($dict_path, "<?php\n\nreturn " . var_export($dict, true) . ";");
    }


    public function collectPath($path)
    {
        $Directory = new RecursiveDirectoryIterator($path);
        $Iterator = new RecursiveIteratorIterator($Directory);
        $Regex = new RegexIterator($Iterator, '/^.+\.(php|html)$/i', RecursiveRegexIterator::GET_MATCH);

        foreach ($Regex as $file) {
            $filename = isset($file[0]) ? $file[0] : null;
            if ($filename) {
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                switch ($ext) {
                    case 'php':
                        $this->collectPhpFile($filename);
                        break;
                    case 'html':
                        if (!$this->onlyAdditional) {
                            $this->collectHtmlFile($filename);
                        }
                        break;
                }
            }
        }
    }

    public function collectPhpFile($filename)
    {
        try {
            $file = file_get_contents($filename);
        } catch (Exception $e) {
            return false;
        }

        if (!$this->onlyAdditional) {
            $regex = strtr('/{module_name}Module\:\:t\({message}(?:{comma}{array}{comma}{dict})?.*?\)/', [
                '{module_name}' => '([A-Z]\w+)',
                '{message}' => '(?|\'(.*?)\'|\"(.*?)\")',
                '{comma}' => '\s?,\s?',
                '{array}' => '(?:\[.*?\]|array\(.*?\))',
                '{dict}' => '(?|\'(.*?)\'|\"(.*?)\")',
            ]);

            $finded = preg_match_all($regex, $file, $out);
            if ($finded && $out) {
                $length = count($out[0]);
                for ($i = 0; $i < $length; $i++) {
                    $module = $out[self::PHP_MODULE][$i];
                    $message = $out[self::PHP_MESSAGE][$i];
                    $dict = $out[self::PHP_DICT][$i];
                    $this->appendCollected($module, $message, $dict, $filename);
                }
            }

            $module = $this->getModuleByPath($filename);
            if ($module) {
                $regex = strtr('/{module_name}\:\:t\({message}(?:{comma}{array}{comma}{dict})?.*?\)/', [
                    '{module_name}' => '(self)',
                    '{message}' => '(?|\'(.*?)\'|\"(.*?)\")',
                    '{comma}' => '\s?,\s?',
                    '{array}' => '(?:\[.*?\]|array\(.*?\))',
                    '{dict}' => '(?|\'(.*?)\'|\"(.*?)\")',
                ]);
                $finded = preg_match_all($regex, $file, $out);
                if ($finded && $out) {
                    $length = count($out[0]);
                    for ($i = 0; $i < $length; $i++) {
                        $message = $out[self::PHP_MESSAGE][$i];
                        $dict = $out[self::PHP_DICT][$i];
                        $this->appendCollected($module, $message, $dict, $filename);
                    }
                }
            }
        }
        $this->additionalPhpProcess($filename, $file);
    }

    public function additionalPhpProcess($path, $file)
    {
        $module = $this->getModuleByPath($path);
        if ($module && $this->isModelFile($path)) {
            $model = pathinfo($path, PATHINFO_FILENAME);
            if (mb_strpos($file, "abstract class {$model}", null, 'UTF-8') === false &&
                mb_strpos($file, "class {$model} extends Manager", null, 'UTF-8') === false &&
                mb_strpos($file, "class {$model} extends TreeManager", null, 'UTF-8') === false) {
                $this->appendCollected($module, "{$model} {url} was created", self::DEFAULT_DICT, $path);
                $this->appendCollected($module, "{$model} {url} was updated", self::DEFAULT_DICT, $path);
                $this->appendCollected($module, "{$model} {url} was deleted", self::DEFAULT_DICT, $path);
            }
        }
    }

    public function collectHtmlFile($filename)
    {
        try {
            $file = file_get_contents($filename);
        } catch (Exception $e) {
            return false;
        }

        $regex = strtr('/\{\{\s*t\({message}{comma}{dict}.*?\s*\}\}/', [
            '{message}' => '(?|\'(.*?)\'|\"(.*?)\")',
            '{comma}' => '\s?,\s?',
            '{dict}' => '(?|\'(.*?)\'|\"(.*?)\")',
        ]);
        $finded = preg_match_all($regex, $file, $out);
        if ($finded && $out) {
            $length = count($out[0]);
            for ($i = 0; $i < $length; $i++) {

                $message = $out[self::TWIG_MESSAGE][$i];
                $module_dict = $out[self::TWIG_MODULE_DICT][$i];

                $module_dict_array = explode('.', $module_dict);
                $module = $module_dict_array[0];
                $dict = false;
                if (isset($module_dict_array[1]))
                    $dict = $module_dict_array[1];

                $this->appendCollected($module, $message, $dict, $filename);
            }
        }
    }

    public function has2UpperLetters($string)
    {
        return preg_match_all('/[A-Z]/', $string) > 1;
    }

    public function appendCollected($module, $message, $dict, $filename)
    {
        if (!$this->has2UpperLetters($module)) {
            $module = ucfirst(strtolower($module));
        }

        if (!$dict)
            $dict = self::DEFAULT_DICT;

        $filename = str_replace($this->_appPath, '', $filename);

        if ($module) {
            if (!isset($this->_collected[$module]))
                $this->_collected[$module] = array();

            if (!isset($this->_collected[$module][$dict]))
                $this->_collected[$module][$dict] = array();

            if (!array_key_exists($message, $this->_collected[$module][$dict]))
                $this->_collected[$module][$dict][$message] = array();

            if (!in_array($filename, $this->_collected[$module][$dict][$message]))
                $this->_collected[$module][$dict][$message][] = $filename;
        }
    }

    public function processSave()
    {
        $this->_modules = array_keys(Mindy::app()->getModules());
        foreach ($this->_collected as $module_name => $dict) {
            foreach ($dict as $dict_name => $messages) {
                $this->processDict($module_name, $dict_name, $messages);
            }
        }
    }

    public function processDict($module_name, $dict_name, $messages)
    {
        if ($this->onlyModule && $this->onlyModule != $module_name) {
            return null;
        }
        if (in_array($module_name, $this->_modules)) {
            $collected_messages = array();
            foreach ($messages as $message => $files) {
                $collected_messages[$message] = '';
            }
            foreach ($this->languages as $lang) {
                $dict_path = $this->getModuleMessagesPath($module_name, $lang, $dict_name);
                $current_dict = array();
                if (is_file($dict_path)) {
                    $current_dict = require($dict_path);
                    if (!is_array($current_dict)) {
                        $current_dict = [];
                    }
                }

                $total = array_merge($collected_messages, $current_dict);
                $this->saveDict($dict_path, $total);
            }
        }
    }
} 
