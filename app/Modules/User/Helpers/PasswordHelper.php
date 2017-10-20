<?php

namespace Modules\User\Helpers;


use Xcart\App\Helpers\ClassNames;

class PasswordHelper
{
    use ClassNames;

    public static function hash($raw, $algo = PASSWORD_DEFAULT, $options = [])
    {
//        return password_hash($raw, $algo, $options);
        return text_crypt($raw);
    }

    public static function verify($raw, $hashed)
    {
        return ($raw == text_decrypt($hashed));
    }
}