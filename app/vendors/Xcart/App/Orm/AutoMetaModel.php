<?php
namespace Xcart\App\Orm;

class AutoMetaModel extends Model
{
    /**
     * @return MetaData
     */
    public static function getMeta()
    {
        return AutoMetaData::getInstance(get_called_class());
    }
}