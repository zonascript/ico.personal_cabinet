<?php

namespace Xcart\App\Orm\Exception;

use Exception;

/**
 * Class ObjectDoesNotExist
 * @package Xcart\App\Orm
 */
class ObjectDoesNotExist extends Exception
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        if (empty($message)) {
            $message = "The requested object does not exist";
        }
        parent::__construct($message, $code, $previous);
    }
}
