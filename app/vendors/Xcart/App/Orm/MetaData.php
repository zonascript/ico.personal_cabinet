<?php

namespace Xcart\App\Orm;

use ReflectionMethod;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\HasManyField;
use Xcart\App\Orm\Fields\ManyToManyField;
use Xcart\App\Orm\Fields\ModelFieldInterface;
use Xcart\App\Orm\Fields\OneToOneField;
use Xcart\App\Orm\Fields\RelatedField;
use ReflectionClass;

/**
 * Class MetaData
 * @package Xcart\App\Orm
 */
class MetaData
{

    /**
     * Default pk name
     */
    const DEFAULT_PRIMARY_KEY_NAME = 'id';

    /**
     * @var MetaData[]
     */
    private static $instances = [];
    /**
     * @var array
     */
    protected $fields = [];
    /**
     * @var array
     */
    protected $mapping = [];
    /**
     * @var array
     */
    protected $attributes = null;
    /**
     * @var array
     */
    protected $primaryKeys = null;

    /**
     * MetaData constructor.
     * @param string $className
     */
    final private function __construct($className)
    {
        $this->init($className);
    }

    /**
     * @param $config
     * @return ModelFieldInterface
     */
    protected function createField($config)
    {
        /** @var $field ModelFieldInterface */
        if (is_string($config)) {
            $config = ['class' => $config];
        }

        if (is_array($config)) {
            $className = $config['class'];
            unset($config['class']);
            $field = (new ReflectionClass($className))->newInstance($config);
        } else if (is_object($config)) {
            $field = $config;
        }

        return $field;
    }

    /**
     * @param string $className
     */
    protected function init($className)
    {
        $primaryFields = [];

        foreach (call_user_func([$className, 'getFields']) as $name => $config) {

            $field = $this->createField($config);
            $field->setName((!empty($config['name'])) ? $config['name'] : $name);
            $field->setModelClass($className);

            $this->fields[$name] = $field;
            $this->mapping[$field->getAttributeName()] = $name;

            if ($field->primary) {
                $primaryFields[] = $field->getAttributeName();
            }
        }

        if (empty($primaryFields)) {
            $autoField = new AutoField([
                'name' => self::DEFAULT_PRIMARY_KEY_NAME,
                'modelClass' => $className
            ]);

            $this->fields[self::DEFAULT_PRIMARY_KEY_NAME] = $autoField;
            $primaryFields[] = self::DEFAULT_PRIMARY_KEY_NAME;
        }

        $this->primaryKeys = $primaryFields;
    }

    /**
     * @param $subClass
     * @return array|[]ModelFieldInterface
     */
    private function fetchFields($subClass)
    {
        $fields = [];
        foreach ($this->fields as $name => $field) {
            if ($field instanceof $subClass) {
                $fields[$name] = $field;
            }
        }
        return $fields;
    }

    /**
     * @return array|[]ModelFieldInterface
     */
    public function getOneToOneFields()
    {
        return $this->fetchFields(OneToOneField::className());
    }

    /**
     * @return array|[]ModelFieldInterface
     */
    public function getHasManyFields()
    {
        return $this->fetchFields(HasManyField::className());
    }

    /**
     * @return array|[]ModelFieldInterface
     */
    public function getManyToManyFields()
    {
        return $this->fetchFields(ManyToManyField::className());
    }

    /**
     * @return array|[]ModelFieldInterface
     */
    public function getForeignFields()
    {
        return $this->fetchFields(ForeignField::className());
    }

    /**
     * @param bool $asArray
     * @return array|string
     */
    public function getPrimaryKeyName($asArray = false)
    {
        return $asArray ? $this->primaryKeys : implode('_', $this->primaryKeys);
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasRelatedField($name)
    {
        return $this->getField($name) instanceof RelatedField;
    }

    /**
     * @param $name
     * @return bool
     */
    public function getRelatedField($name)
    {
        $field = $this->getField($name);
        return $field instanceof RelatedField ? $field : null;
    }

    /**
     * @return array|[]ModelFieldInterface
     */
    public function getRelatedFields()
    {
        return $this->fetchFields(RelatedField::className());
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasHasManyField($name)
    {
        return array_key_exists($name, $this->getHasManyFields());
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasManyToManyField($name)
    {
        return array_key_exists($name, $this->getManyToManyFields());
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasOneToOneField($name)
    {
        return array_key_exists($name, $this->getOneToOneFields());
    }

    /**
     * @param $className
     * @return MetaData
     */
    public static function getInstance($className)
    {
        if (!isset(self::$instances[$className])) {
            self::$instances[$className] = new static($className);
        }
        return self::$instances[$className];
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        if ($this->attributes === null) {
            /** @var \Xcart\App\Orm\Model $className */
            $attributes = [];
            foreach ($this->getFields() as $name => $field) {
                $attributeName = $field->getAttributeName();
                if ($attributeName) {
                    $attributes[] = $attributeName;
                }
            }
            $this->attributes = $attributes;
        }
        return $this->attributes;
    }

    /**
     * @return array|\Xcart\App\Orm\Fields\ModelFieldInterface[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getMappingName($name)
    {
        return isset($this->mapping[$name]) ? $this->mapping[$name] : $name;
    }

    /**
     * @param $name
     * @return \Xcart\App\Orm\Fields\Field
     */
    public function getField($name)
    {
        if ($name === 'pk') {
            $name = $this->getPrimaryKeyName();
        }

        $name = $this->getMappingName($name);

        if (isset($this->fields[$name])) {
            $field = $this->fields[$name];
            $field->cleanValue();
            return $field;
        }

        return null;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasField($name)
    {
        if ($name === 'pk') {
            $name = $this->getPrimaryKeyName();
        }
        return array_key_exists($name, $this->fields) || array_key_exists($name, $this->mapping);
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasForeignField($name)
    {
        return $this->getField($name) instanceof ForeignField;
    }

    /**
     * @param $name
     * @return ModelFieldInterface
     */
    public function getForeignField($name)
    {
        $field = $this->getField($name);
        return $field instanceof ForeignField ? $field : null;
    }

    /**
     * @param $name
     * @return ModelFieldInterface
     */
    public function getOneToOneField($name)
    {
        $field = $this->getField($name);
        return $field instanceof OneToOneField ? $field : null;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getManyToManyField($name)
    {
        $field = $this->getField($name);
        return $field instanceof ManyToManyField ? $field : null;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getHasManyField($name)
    {
        $field = $this->getField($name);
        return $field instanceof HasManyField ? $field : null;
    }

    /**
     * @param $keys
     * @return bool
     */
    public static function isPrimaryKey($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        $pks = static::getPrimaryKeyName(true);
        if (count($keys) === count($pks)) {
            return count(array_intersect($keys, $pks)) === count($pks);
        } else {
            return false;
        }
    }
}
