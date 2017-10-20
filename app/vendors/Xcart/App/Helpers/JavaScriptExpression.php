<?php

namespace Xcart\App\Helpers;

use Exception;

/**
 * CJavaScriptExpression represents a JavaScript expression that does not need escaping.
 * It can be passed to {@link CJavaScript::encode()} and the code will stay as is.
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @package Mindy\Helper
 * @since 1.1.11
 */
class JavaScriptExpression
{
    /**
     * @var string the javascript expression wrapped by this object
     */
    public $code;

    /**
     * @param string $code a javascript expression that is to be wrapped by this object
     * @throws Exception if argument is not a string
     */
    public function __construct($code)
    {
        if (!is_string($code)) {
            throw new Exception('Value passed to CJavaScriptExpression should be a string.');
        }
        if (strpos($code, 'js:') === 0) {
            $code = substr($code, 3);
        }
        $this->code = $code;
    }

    /**
     * String magic method
     * @return string the javascript expression wrapped by this object
     */
    public function __toString()
    {
        return $this->code;
    }
}
