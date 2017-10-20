<?php
namespace Xcart\App\Orm\Legacy;

use Exception;
use Xcart\App\Helpers\ClassNames;
use Xcart\App\Orm\Manager;
use Xcart\App\Orm\ModelInterface;

/**
 * Class BaseOrmLegacy
 *
 * @package Xcart\App\Orm\Legacy
 *
 * @method static \Xcart\App\Orm\Manager objects($instance = null)
 */
class BaseOrmLegacy
{
    use ClassNames;

    /**
     * @var bool
     */
    protected $isNewRecord = true;

    /**
     * @param array $attributes
     *
     * @return static
     */
    public static function create(array $attributes)
    {
        $className = static::className();

        /** @var static $record */
        $record = new $className;
        if (!empty($attributes)) {
            $record->setAttributes($attributes);
        }

        return $record;
    }

    /**
     * @param null|ModelInterface $instance
     * @return Manager
     */
    public static function objectsManager($instance = null)
    {
        if (!$instance) {
            $className = get_called_class();
            $instance = new $className;
        }

        if (class_exists($managerClass = self::getManagerClass())) {
            return new $managerClass($instance, $instance->getConnection());
        }

        return new Manager($instance, $instance->getConnection());
    }

    /**
     * Returns the bundle's container extension class.
     *
     * @return string
     */
    protected static function getManagerClass()
    {
        $reflect = new \ReflectionClass(get_called_class());
        return self::getNamespace() . '\\' . $reflect->getShortName() . 'Manager';
    }

    /**
     * Gets the Bundle namespace.
     *
     * @return string The Bundle namespace
     */
    public static function getNamespace()
    {
        $class = get_called_class();
        return substr($class, 0, strrpos($class, '\\'));
    }


    public function setAttributes($attributes)
    {
        foreach ($attributes as $name => $value)
        {
            $this->setAttribute($name, $value);
        }
    }

    //@TODO: Дописать, это тупо костыль
    public function setAttribute($name, $value)
    {
        $this->{$name} = $value;
    }

    /**
     * @param string $name
     * @param string $tablePrefix
     * @return string
     */
    public static function getRawTableName($name, $tablePrefix = '')
    {
        if (strpos($name, '{{') !== false) {
            $name = preg_replace('/\\{\\{(.*?)\\}\\}/', '\1', $name);
            return str_replace('%', $tablePrefix, $name);
        } else {
            return $name;
        }
    }

    /**
     * @return bool
     */
    public function getIsNewRecord()
    {
        return $this->isNewRecord;
    }

    /**
     * @param bool $value
     */
    public function setIsNewRecord($value)
    {
        $this->isNewRecord = $value;
//        if ($value === false) {
//            $this->attributes->resetOldAttributes();
//        }
    }

    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     * @throws Exception
     */
    public static function __callStatic($method, $args)
    {
        $manager   = $method . 'Manager';
        $className = get_called_class();

        if ($method === 'tableName') {
            $tableName = call_user_func([$className, $method]);

            return self::getRawTableName($tableName);
        }
        else {
            if (method_exists($className, $manager)) {
                return call_user_func_array([$className, $manager], $args);
            }
            else {
                if (method_exists($className, $method)) {
                    return call_user_func_array([$className, $method], $args);
                }
                else {
                    throw new Exception("Call unknown method {$method}");
                }
            }
        }
    }

    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     * @throws Exception
     */
    public function __call($method, $args)
    {
        $manager = $method . 'Manager';
        if (method_exists($this, $manager)) {
            return call_user_func_array([$this, $manager], array_merge([$this], $args));
        }
        elseif (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $args);
        }
        else {
            throw new Exception('Call unknown method ' . $method);
        }
    }
}