<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 27.01.2017
 * Time: 10:38
 */

namespace Xcart\App\Exceptions;

use Exception;

class MultipleObjectsReturned extends Exception
{
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        if (empty($message)) {
            $message = "The query returned multiple objects when only one was expected.";
        }
        parent::__construct($message, $code, $previous);
    }
}