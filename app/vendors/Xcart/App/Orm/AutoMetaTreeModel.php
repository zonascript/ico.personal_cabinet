<?php
namespace Xcart\App\Orm;

class AutoMetaTreeModel extends TreeModel
{
    /**
     * @return MetaData
     */
    public static function getMeta()
    {
        return AutoMetaData::getInstance(get_called_class());
    }
}