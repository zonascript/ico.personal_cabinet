<?php
namespace Xcart\App\Orm\Exception;

class OrmExceptions extends \Exception
{

    public static function FailCreateLink()
    {
        throw new self('At the table when there is a composite key. It is impossible to build link automatically.');
    }
}