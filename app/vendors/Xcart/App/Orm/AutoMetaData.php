<?php
namespace Xcart\App\Orm;

use Doctrine\DBAL\Schema\Column;
use ReflectionMethod;
use Xcart\App\Main\Xcart;
use Xcart\App\Orm\Fields\BigIntField;
use Xcart\App\Orm\Fields\BlobField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\DateField;
use Xcart\App\Orm\Fields\DateTimeField;
use Xcart\App\Orm\Fields\DecimalField;
use Xcart\App\Orm\Fields\FloatField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Fields\TextField;
use Xcart\App\Orm\Fields\TimeField;

class AutoMetaData extends MetaData
{
    private static $_tables;
    private static $_configs;

    protected function init($className)
    {
        $this->initTableData();

        if ((new ReflectionMethod($className, 'getFields'))->isStatic()
//            || (new ReflectionMethod($className, 'getColumns'))->isStatic()
        ) {
            parent::init($className);
        }

        $primaryFields = [];


        foreach ($this->getTableConfig($className) as $name => $config)
        {
            if (!isset($this->fields[$name])) {
                $field = $this->createField($config);
                $field->setName($name);
                $field->setModelClass($className);

                $this->fields[$name] = $field;
                $this->mapping[$field->getAttributeName()] = $name;

                if ($field->primary) {
                    $primaryFields[] = $field->getAttributeName();
                }
            }
        }

        if (empty($primaryFields) && empty($this->primaryKeys)) {
            $this->primaryKeys = call_user_func([$className, 'getPrimaryKeyName']);
        }
        elseif (!empty($primaryFields)) {

            $this->primaryKeys = $primaryFields;
        }
    }

    /**
     * @param string $className
     *
     * @return \Doctrine\DBAL\Schema\Column[]
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getTableColumns($className)
    {
        if (!isset(self::$_tables[$className]))
        {
            self::$_tables[$className] = Xcart::app()->db
                ->getConnection()
                ->getSchemaManager()
                ->listTableColumns(call_user_func([$className, 'tableName']));
        }

        return self::$_tables[$className];
    }

    /**
     * @param string $className
     *
     * @return array Config fields as $name => $config
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getTableConfig($className)
    {
        if (!isset(self::$_configs[$className]))
        {
            foreach ($this->getTableColumns($className) as $column) {
                $name = $column->getName();

                if (!isset($this->fields[$name])) {
                    if ($config = $this->getConfigFromDBAL($column)) {
                        self::$_configs[$className][$name] = $config;
                    }
                }
            }
        }

        return self::$_configs[$className];
    }

    private function getConfigFromDBAL(Column $column)
    {
        if ($type = $column->getType())
        {
            $config = [
                'null'    => !$column->getNotnull(),
                'default' => $column->getDefault(),
            ];

            if ($column->getLength()) {
                $config['length'] = $column->getLength();
            }

            switch ($type->getName()) {
                case 'smallint' :
                case 'integer' : {
                    $config['class'] = IntField::className();
                    break;
                }
                case 'bigint' : {
                    $config['class'] = BigIntField::className();
                    break;
                }
                case 'decimal' : {
                    $config['class'] = DecimalField::className();
                    $config['precision'] = $column->getPrecision();
                    $config['scale'] = $column->getScale();
                    break;
                }
                case 'float' : {
                    $config['class'] = FloatField::className();
                    break;
                }

                case 'blob' : {
                    $config['class'] = BlobField::className();
                    unset($config['length']);
                    break;
                }
                case 'date' : {
                    $config['class'] = DateField::className();
                    break;
                }
                case 'datetime' : {
                    $config['class'] = DateTimeField::className();
                    break;
                }
                case 'time' : {
                    $config['class'] = TimeField::className();
                    break;
                }
//            case 'timeshtamp' : {
//                $config['class'] = TimestampField::className();
//                break;
//            }

                case 'string' : {
                    $config['class'] = CharField::className();
                    break;
                }
                case 'longtext' :
                case 'text' : {
                    unset($config['length']);
                }
                default: {
                    $config['class'] = TextField::className();
                }
            }

            return $config;
        }

        return null;
    }

    public function initTableData()
    {

        self::$_tables = [];

        if (is_null(self::$_configs))
        {
            if (Xcart::app()->hasComponent('event') && Xcart::app()->hasComponent('cache'))
            {
                self::$_configs = Xcart::app()->cache->get('auto_meta_data_configs', []);
                Xcart::app()->event->on('app:end', [$this, 'saveCache']);
            }
            else {
                self::$_configs = [];
            }
        }
    }

    public static function saveCache($owner)
    {
        if (self::$_configs) {
            Xcart::app()->cache->set('auto_meta_data_configs', self::$_configs, 360);
        }
    }
}