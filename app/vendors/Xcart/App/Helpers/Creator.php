<?php

namespace Xcart\App\Helpers;
use InvalidArgumentException;
use Xcart\App\Exceptions\InvalidConfigException;

/**
 * Helper class that create objects and configure it
 *
 * Class Configurator
 * @package Xcart\App\Helpers
 */
class Creator
{
    use ClassNames;

    /**
     * @var string
     */
    public static $singletonMethod = 'getInstance';
    /**
     * @var array initial property values that will be applied to objects newly created via [[createObject]].
     * The array keys are class names without leading backslashes "\", and the array values are the corresponding
     * name-value pairs for initializing the created class instances. For example,
     *
     * ~~~
     * [
     *     'Bar' => [
     *         'prop1' => 'value1',
     *         'prop2' => 'value2',
     *     ],
     *     'mycompany\foo\Car' => [
     *         'prop1' => 'value1',
     *         'prop2' => 'value2',
     *     ],
     * ]
     * ~~~
     *
     * @see createObject()
     */
    public static $objectConfig = [];

    /**
     * Creates a new object using the given configuration.
     *
     * The configuration can be either a string or an array.
     * If a string, it is treated as the *object class*; if an array,
     * it must contain a `class` element specifying the *object class*, and
     * the rest of the name-value pairs in the array will be used to initialize
     * the corresponding object properties.
     *
     * Below are some usage examples:
     *
     * ~~~
     * $object = \Mindy::createObject('app\components\GoogleMap');
     * $object = \Mindy::createObject([
     *     'class' => 'app\components\GoogleMap',
     *     'apiKey' => 'xyz',
     * ]);
     * ~~~
     *
     * This method can be used to create any object as long as the object's constructor is
     * defined like the following:
     *
     * ~~~
     * public function __construct(..., $config = []) {
     * }
     * ~~~
     *
     * The method will pass the given configuration as the last parameter of the constructor,
     * and any additional parameters to this method will be passed as the rest of the constructor parameters.
     *
     * @param string|array $config the configuration. It can be either a string representing the class name
     * or an array representing the object configuration.
     * @return mixed the created object
     * @throws InvalidArgumentException if the configuration is invalid.
     */
    public static function createObject($config)
    {
        static $reflections = [];

        if (is_string($config)) {
            $class = $config;
            $config = [];
        } elseif (isset($config['class'])) {
            $class = $config['class'];
            unset($config['class']);
        } else {
            throw new InvalidArgumentException('Object configuration must be an array containing a "class" element.');
        }

        $class = ltrim($class, '\\');
        if (isset(static::$objectConfig[$class])) {
            $config = array_merge(static::$objectConfig[$class], $config);
        }

        if (($n = func_num_args()) > 1) {
            /** @var \ReflectionClass $reflection */
            if (isset($reflections[$class])) {
                $reflection = $reflections[$class];
            } else {
                $reflection = $reflections[$class] = new \ReflectionClass($class);
            }
            $args = func_get_args();
            array_shift($args); // remove $config
            if (!empty($config)) {
                $args[] = $config;
            }
            if (method_exists($class, self::$singletonMethod)) {
                $method = $reflection->getMethod(self::$singletonMethod);
                $obj = $method->invokeArgs($class, $args);
            } else {
                $obj = $reflection->newInstanceArgs($args);
            }
        } else {
            $obj = empty($config) ? new $class : new $class($config);
        }


        if (array_key_exists(Creator::className(), self::class_uses_deep($obj))) {
            return $obj;
        } else {
            $obj = self::configure($obj, $config);
            if (method_exists($obj, 'init')) {
                $obj->init();
            }
            return $obj;
        }
    }

    public static function class_uses_deep($class, $autoload = true)
    {
        $traits = [];

        do {
            $traits = array_merge(class_uses($class, $autoload), $traits);
        } while ($class = get_parent_class($class));

        foreach ($traits as $trait => $same) {
            $traits = array_merge(class_uses($trait, $autoload), $traits);
        }

        return array_unique($traits);
    }



    /**
     *
     * @param $class string|array
     * @param array $config array
     * @return mixed
     * @throws InvalidConfigException
     */
    public static function create($class, $config = [])
    {
        if (is_array($class) && isset($class['class'])) {
            $config = $class;
            $class = $config['class'];
            unset($config['class']);
        } elseif (!is_string($class)) {
            throw new InvalidConfigException("Class name must be defined");
        }
        
        $obj = new $class;
        $obj = self::configure($obj, $config);
        if (method_exists($obj, 'init')) {
            $obj->init();
        }
        return $obj;
    }

    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }
        
        return $object;
    }

    /**
     * Returns the public member variables of an object.
     * This method is provided such that we can get the public member variables of an object.
     * It is different from "get_object_vars()" because the latter will return private
     * and protected variables if it is called within the object itself.
     * @param object $object the object to be handled
     * @return array the public member variables of the object
     */
    public static function getObjectVars($object)
    {
        return get_object_vars($object);
    }
}