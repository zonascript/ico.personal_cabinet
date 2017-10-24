<?php
/**
 * 
 *
 * All rights reserved.
 * 
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 02/09/14.09.2014 12:47
 */

namespace Modules\Mail\Helper;


use Exception;
use Mindy\Helper\Alias;

class MailHelper
{
    public static function convertToBase64($path)
    {
        $path = Alias::get('www') . '/' . ltrim($path, '/');
        if(!is_file($path)) {
            throw new Exception("File not found");
        }
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
