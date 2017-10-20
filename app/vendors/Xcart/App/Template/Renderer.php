<?php

namespace Xcart\App\Template;


use Xcart\App\Main\Xcart;

trait Renderer
{
    public function renderString($source, $params = [])
    {
        return Xcart::app()->template->renderString($source, self::mergeData($params));
    }

    public static function renderTemplate($template, $params = [])
    {
        return Xcart::app()->template->render($template, self::mergeData($params));
    }

    protected static function mergeData($data)
    {
        if(is_array($data) === false) {
            $data = [];
        }
        $app = Xcart::app();
        return array_merge($data, [
            'request' => $app->getComponent('request'),
            'user' => $app->getUser()
        ]);
    }


    /**
     * Renders a view file.
     * This method includes the view file as a PHP script
     * and captures the display result if required.
     * @param string $_viewFile_ view file
     * @param array $_data_ data to be extracted and made available to the view file
     * @return string the rendering result. Null if the rendering result is not required.
     */
    public function renderInternal($_viewFile_, $_data_ = null)
    {
        // we use special variable names here to avoid conflict when extracting data
        if (is_array($_data_)) {
            extract($_data_, EXTR_PREFIX_SAME, 'data');
        } else {
            $data = $_data_;
        }

        ob_start();
        ob_implicit_flush(false);
        require($_viewFile_);
        return ob_get_clean();
    }
}