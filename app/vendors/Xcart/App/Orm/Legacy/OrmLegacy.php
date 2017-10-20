<?php

namespace Xcart\App\Orm\Legacy;

use Doctrine\DBAL\Driver\Connection;
use Xcart\App\Orm\AutoMetaData;
use Xcart\App\Orm\MetaData;
use Xcart\Connection as XcartConnection;
use Exception;
use Xcart\Data;

class OrmLegacy extends BaseOrmLegacy
{
    /** @var  Connection */
    protected static $connection;

    public static function setDefaultConnection(Connection $connection)
    {
        self::$connection = $connection;
    }

    public static function getDefaultConnection()
    {
        if (self::$connection === null) {
            self::$connection = XcartConnection::getInstance();
        }
        return self::$connection;
    }

    /**
     * @return Connection
     * @throws Exception
     */
    public function getConnection()
    {
        return static::getDefaultConnection();
    }



    public static function tableName()
    {
        $class = static::className();
        /** @var Data $model */
        $model = new $class;

        return $model->getTableName();
    }


    /**
     * @return MetaData
     */
    public static function getMeta()
    {
        return AutoMetaData::getInstance(get_called_class());
    }
}