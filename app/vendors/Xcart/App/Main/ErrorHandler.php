<?php

namespace Xcart\App\Main;

use ErrorException;
use Exception;
use Xcart\App\Application\Application;
use Xcart\App\Cli\Cli;
use Xcart\App\Exceptions\CompileErrorException;
use Xcart\App\Exceptions\CoreErrorException;
use Xcart\App\Exceptions\CoreWarningException;
use Xcart\App\Exceptions\DeprecatedException;
use Xcart\App\Exceptions\NoticeException;
use Xcart\App\Exceptions\ParseException;
use Xcart\App\Exceptions\RecoverableErrorException;
use Xcart\App\Exceptions\StrictException;
use Xcart\App\Exceptions\UserDeprecatedException;
use Xcart\App\Exceptions\UserErrorException;
use Xcart\App\Exceptions\UserNoticeException;
use Xcart\App\Exceptions\UserWarningException;
use Xcart\App\Exceptions\WarningException;
use Xcart\App\Exceptions\HttpException;
use Xcart\App\Helpers\Console;
use Xcart\App\Helpers\SmartProperties;
use Xcart\App\Template\Renderer;

class ErrorHandler
{
    use SmartProperties, Renderer;

    public $errorTemplate = 'error.tpl';
    public $exceptionTemplate = 'exception.tpl';

    public $debug = false;
    public $errHandler = true;
    public $excHandler = true;
    public $ignoreDeprecated = false;


    public $handlers = [];

    /**
     * @var integer maximum number of source code lines to be displayed. Defaults to 25.
     */
    public $maxSourceLines = 35;

    /**
     * @var integer maximum number of trace source code lines to be displayed. Defaults to 10.
     * @since 1.1.6
     */
    public $maxTraceSourceLines = 10;

    /**
     * @var string the application administrator information (could be a name or email link). It is displayed in error pages to end users. Defaults to 'the webmaster'.
     */
    public $adminInfo = 'the webmaster';
    /**
     * @var boolean whether to discard any existing page output before error display. Defaults to true.
     */
    public $discardOutput = true;
    /**
     * @var boolean whether to discard any existing page output before error display. Defaults to true.
     */
    public $shortOutput = false;
    /**
     * @var bool use external template system for render error and exception page
     */
    public $useTemplate = false;
    /**
     * @var string the route (eg 'site/error') to the controller action that will be used to display external errors.
     * Inside the action, it can retrieve the error information by Yii::app()->errorHandler->error.
     * This property defaults to null, meaning CErrorHandler will handle the error display.
     */
    public $errorAction;

    protected $_error;
    protected $_exception;

    public function init()
    {
        $this->setHandlers();
    }

    public function setHandlers()
    {
//        ini_set('display_errors', false);

        if ($this->errHandler) {
            set_error_handler([$this, 'handleError']);
        }
        if ($this->excHandler) {
            set_exception_handler([$this, 'handleException']);
        }
    }

    public function unsetHandlers()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * Returns the details about the error that is currently being handled.
     * The error is returned in terms of an array, with the following information:
     * <ul>
     * <li>code - the HTTP status code (e.g. 403, 500)</li>
     * <li>type - the error type (e.g. 'CHttpException', 'PHP Error')</li>
     * <li>message - the error message</li>
     * <li>file - the name of the PHP script file where the error occurs</li>
     * <li>line - the line number of the code where the error occurs</li>
     * <li>trace - the call stack of the error</li>
     * <li>source - the context source code where the error occurs</li>
     * </ul>
     * @return array the error details. Null if there is no error.
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Returns the instance of the exception that is currently being handled.
     * @return Exception|null exception instance. Null if there is no exception.
     */
    public function getException()
    {
        return $this->_exception;
    }

    /**
     * Handles the exception.
     * @param Exception $exception the exception captured
     */
    public function handleException($exception)
    {
        $app = Xcart::app();

        if ($app instanceof Application) {
            if (($trace = $this->getExactTrace($exception)) === null) {
                $fileName = $exception->getFile();
                $errorLine = $exception->getLine();
            } else {
                $fileName = $trace['file'];
                $errorLine = $trace['line'];
            }

            $trace = $exception->getTrace();

            foreach ($trace as $i => $t) {
                if (!isset($t['file'])) {
                    $trace[$i]['file'] = 'unknown';
                }

                if (!isset($t['line'])) {
                    $trace[$i]['line'] = 0;
                }

                if (!isset($t['function'])) {
                    $trace[$i]['function'] = 'unknown';
                }

                unset($trace[$i]['object']);
            }

            $code = ($exception instanceof HttpException) ? $exception->status : 500;

            $this->_exception = $exception;

            $this->_error = [
                'code' => $code,
                'type' => get_class($exception),
                'errorCode' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'file' => $fileName,
                'line' => $errorLine,
                'trace' => $exception->getTraceAsString(),
                'traces' => $trace,
            ];


            $err = $this->_error;
            unset($err['trace']);

            $app->logger->critical($exception->getMessage(), $err, 'error');

            if (!headers_sent()) {
                header("HTTP/1.0 {$code} " . $this->getHttpHeader($code, get_class($exception)));
            }

            $this->renderException();
        } else {
            $this->displayException($exception);
        }
    }


    /**
     * Displays the captured PHP error.
     * This method displays the error in HTML when there is
     * no active error handler.
     * @param integer $code error code
     * @param string $message error message
     * @param string $file error file
     * @param string $line error line
     */
    public function displayError($code, $message, $file, $line)
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            if (Cli::isCli()) {
                echo "PHP Error [$code]" . PHP_EOL;
                echo "$message ($file:$line)" . PHP_EOL;
            } else {
                echo "<h1>PHP Error [$code]</h1>\n";
                echo "<p>$message ($file:$line)</p>\n";
                echo '<pre>';
            }

            $trace = debug_backtrace();
            // skip the first 3 stacks as they do not tell the error position
            if (count($trace) > 3)
                $trace = array_slice($trace, 3);
            foreach ($trace as $i => $t) {
                if (!isset($t['file']))
                    $t['file'] = 'unknown';
                if (!isset($t['line']))
                    $t['line'] = 0;
                if (!isset($t['function']))
                    $t['function'] = 'unknown';
                echo "#$i {$t['file']}({$t['line']}): ";
                if (isset($t['object']) && is_object($t['object']))
                    echo get_class($t['object']) . '->';
                echo "{$t['function']}()\n";
            }

            if (!Cli::isCli()) {
                echo '</pre>';
            }
        } else {
            if (Cli::isCli()) {
                echo "PHP Error [$code]\n" . PHP_EOL;
                echo "$message\n" . PHP_EOL;
            } else {
                echo "<h1>PHP Error [$code]</h1>\n";
                echo "<p>$message</p>\n";
            }
        }
    }

    /**
     * Displays the uncaught PHP exception.
     * This method displays the exception in HTML when there is
     * no active error handler.
     * @param Exception $exception the uncaught exception
     */
    public function displayException($exception)
    {
        if ($this->shortOutput) {
            echo get_class($exception) . PHP_EOL;
            echo $exception->getMessage() . ' (' . $exception->getFile() . ':' . $exception->getLine() . ')' . PHP_EOL;
            echo $exception->getTraceAsString() . PHP_EOL;
        } else if (Cli::isCli()) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                echo Console::color(get_class($exception), Console::FOREGROUND_RED) . PHP_EOL;
                echo $exception->getMessage() . ' (' . $exception->getFile() . ':' . $exception->getLine() . ')' . PHP_EOL;
                echo $exception->getTraceAsString() . PHP_EOL;
            } else {
                echo Console::color(get_class($exception), Console::FOREGROUND_RED) . PHP_EOL;
                echo $exception->getMessage() . PHP_EOL;
            }
        } else {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                echo '<h1>' . get_class($exception) . "</h1>\n";
                echo '<p>' . $exception->getMessage() . ' (' . $exception->getFile() . ':' . $exception->getLine() . ')</p>';
                echo '<pre>' . $exception->getTraceAsString() . '</pre>';
            } else {
                echo '<h1>' . get_class($exception) . "</h1>\n";
                echo '<p>' . $exception->getMessage() . '</p>';
            }
        }
    }

    /**
     * Handles the PHP error.
     */
    public function handleError($code, $message, $file, $line, array $errcontext = [])
    {
        $app = Xcart::app();
        if (defined('APP_DEBUG') && APP_DEBUG) {
            // Tryhard
            switch ($code) {
                case E_ERROR:
                    $app->logger->critical($message, ['code' => $code, 'file' => $file, 'line' => $line], 'error');
                    throw new ErrorException($message, 0, $code, $file, $line);
                case E_WARNING:
                    $app->logger->critical($message, ['code' => $code, 'file' => $file, 'line' => $line], 'error');
                    throw new WarningException($message, 0, $code, $file, $line);
                case E_PARSE:
                    $app->logger->critical($message, ['code' => $code, 'file' => $file, 'line' => $line], 'error');
                    throw new ParseException($message, 0, $code, $file, $line);
                case E_NOTICE:
                    $app->logger->critical($message, ['code' => $code, 'file' => $file, 'line' => $line], 'error');
                    throw new NoticeException($message, 0, $code, $file, $line);
                case E_CORE_ERROR:
                    $app->logger->critical($message, ['code' => $code, 'file' => $file, 'line' => $line], 'error');
                    throw new CoreErrorException($message, 0, $code, $file, $line);
                case E_CORE_WARNING:
                    $app->logger->critical($message, ['code' => $code, 'file' => $file, 'line' => $line], 'error');
                    throw new CoreWarningException($message, 0, $code, $file, $line);
                case E_COMPILE_ERROR:
                    $app->logger->critical($message, ['code' => $code, 'file' => $file, 'line' => $line], 'error');
                    throw new CompileErrorException($message, 0, $code, $file, $line);
                case E_COMPILE_WARNING:
                    $app->logger->critical($message, ['code' => $code, 'file' => $file, 'line' => $line], 'error');
                    throw new CoreWarningException($message, 0, $code, $file, $line);
                case E_USER_ERROR:
                    $app->logger->critical($message, ['code' => $code, 'file' => $file, 'line' => $line], 'error');
                    throw new UserErrorException($message, 0, $code, $file, $line);
                case E_USER_WARNING:
                    $app->logger->critical($message, ['code' => $code, 'file' => $file, 'line' => $line], 'error');
                    throw new UserWarningException($message, 0, $code, $file, $line);
                case E_USER_NOTICE:
                    $app->logger->critical($message, ['code' => $code, 'file' => $file, 'line' => $line], 'error');
                    throw new UserNoticeException($message, 0, $code, $file, $line);
                case E_STRICT:
                    $app->logger->critical($message, ['code' => $code, 'file' => $file, 'line' => $line], 'error');
                    throw new StrictException($message, 0, $code, $file, $line);
                case E_RECOVERABLE_ERROR:
                    $app->logger->critical($message, ['code' => $code, 'file' => $file, 'line' => $line], 'error');
                    throw new RecoverableErrorException($message, 0, $code, $file, $line);
                case E_DEPRECATED:
                    $app->logger->critical($message, ['code' => $code, 'file' => $file, 'line' => $line], 'error');
                    throw new DeprecatedException($message, 0, $code, $file, $line);
                case E_USER_DEPRECATED:
                    $app->logger->critical($message, ['code' => $code, 'file' => $file, 'line' => $line], 'error');
                    throw new UserDeprecatedException($message, 0, $code, $file, $line);
            }
        }
        else {
            if ($this->ignoreDeprecated) {
                switch ($code) {
                    case E_DEPRECATED:
                    case E_USER_DEPRECATED:
                        return false;
                }
            }

            $msg = "Error: {$message}\nFile: {$file}\nLine: {$line}";

            $trace = debug_backtrace();
            // skip the first 3 stacks as they do not tell the error position
            if (count($trace) > 3) {
                $trace = array_slice($trace, 3);
            }
            $traceString = '';
            foreach ($trace as $i => $t) {
                if (!isset($t['file'])) {
                    $trace[$i]['file'] = 'unknown';
                }

                if (!isset($t['line'])) {
                    $trace[$i]['line'] = 0;
                }

                if (!isset($t['function'])) {
                    $trace[$i]['function'] = 'unknown';
                }

                $traceString .= "#$i {$trace[$i]['file']}({$trace[$i]['line']}): ";
                if (isset($t['object']) && is_object($t['object'])) {
                    $traceString .= get_class($t['object']) . '->';
                }
                $traceString .= "{$trace[$i]['function']}()\n";

                unset($trace[$i]['object']);
            }

            $app = Xcart::app();
            if ($app instanceof Application && Console::isCli() === false) {
                switch ($code) {
                    case E_WARNING:
                        $type = 'PHP warning';
                        break;
                    case E_NOTICE:
                        $type = 'PHP notice';
                        break;
                    case E_USER_ERROR:
                        $type = 'User error';
                        break;
                    case E_USER_WARNING:
                        $type = 'User warning';
                        break;
                    case E_USER_NOTICE:
                        $type = 'User notice';
                        break;
                    case E_RECOVERABLE_ERROR:
                        $type = 'Recoverable error';
                        break;
                    default:
                        $type = 'PHP error';
                }
                $this->_exception = null;
                $this->_error = [
                    'code' => 500,
                    'type' => $type,
                    'message' => $message,
                    'file' => $file,
                    'line' => $line,
                    'trace' => $traceString,
                    'traces' => $trace,
                ];

                $err = $this->_error;
                unset($err['trace']);

                $app->logger->error($type, $err, 'error');

                if (!headers_sent()) {
                    header("HTTP/1.0 500 Internal Server Error");
                }
                $this->renderError();
            } else {
                $this->displayError($code, $message, $file, $line);
            }
        }
    }

    /**
     * whether the current request is an AJAX (XMLHttpRequest) request.
     * @return boolean whether the current request is an AJAX request.
     */
    protected function getIsAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * Returns the exact trace where the problem occurs.
     * @param Exception $exception the uncaught exception
     * @return array the exact trace where the problem occurs
     */
    protected function getExactTrace($exception)
    {
        $traces = $exception->getTrace();

        foreach ($traces as $trace) {
            // property access exception
            if (isset($trace['function']) && ($trace['function'] === '__get' || $trace['function'] === '__set')) {
                return $trace;
            }
        }
        return null;
    }

    /**
     * Renders the view.
     * @param string $view the view name (file name without extension).
     * See {@link getViewFile} for how a view file is located given its name.
     * @param array $data data to be passed to the view
     */
    protected function render($view, $data)
    {
        $data = [
            'data' => array_merge($data, [
                'time' => time(),
                'admin' => $this->adminInfo,
                'version' => $this->getVersionInfo()
            ]),
            'this' => $this
        ];
        if ($this->useTemplate) {
            echo $this->renderTemplate($view . '.tpl', $data);
        } else {
            echo $this->renderInternal(__DIR__ . '/templates/' . $view . '.php', $data);
        }
        Xcart::app()->end();
    }

    /**
     * Renders the exception information.
     * This method will display information from current {@link error} value.
     */
    protected function renderException()
    {
        $exception = $this->getException();
        if ($this->getIsAjax() || Console::isCli() || $this->shortOutput) {
            $this->displayException($exception);
        } else {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                $this->render('exception', $this->getError());
            } else {
                $this->renderError();
            }
        }
    }

    /**
     * Renders the current error information.
     * This method will display information from current {@link error} value.
     */
    protected function renderError()
    {
        $this->render('error', $this->getError());
    }

    /**
     * Returns server version information.
     * If the application is in production mode, empty string is returned.
     * @return string server version information. Empty if in production mode.
     */
    protected function getVersionInfo()
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $version = '<a href="http://s3stores.com/">S3 Stores Framework</a>/' . Xcart::getVersion();
            if (isset($_SERVER['SERVER_SOFTWARE'])) {
                $version = $_SERVER['SERVER_SOFTWARE'] . ' ' . $version;
            }
        } else {
            $version = '';
        }
        return $version;
    }

    /**
     * Converts arguments array to its string representation
     *
     * @param array $args arguments array to be converted
     * @return string string representation of the arguments array
     */
    protected function argumentsToString($args)
    {
        $count = 0;

        $isAssoc = $args !== array_values($args);

        foreach ($args as $key => $value) {
            $count++;
            if ($count >= 5) {
                if ($count > 5)
                    unset($args[$key]);
                else
                    $args[$key] = '...';
                continue;
            }

            if (is_object($value))
                $args[$key] = get_class($value);
            elseif (is_bool($value))
                $args[$key] = $value ? 'true' : 'false';
            elseif (is_string($value)) {
                if (strlen($value) > 64)
                    $args[$key] = '"' . substr($value, 0, 64) . '..."';
                else
                    $args[$key] = '"' . $value . '"';
            } elseif (is_array($value))
                $args[$key] = 'array(' . $this->argumentsToString($value) . ')';
            elseif ($value === null)
                $args[$key] = 'null';
            elseif (is_resource($value))
                $args[$key] = 'resource';

            if (is_string($key)) {
                $args[$key] = '"' . $key . '" => ' . $args[$key];
            } elseif ($isAssoc) {
                $args[$key] = $key . ' => ' . $args[$key];
            }
        }
        $out = implode(", ", $args);

        return $out;
    }

    /**
     * Returns a value indicating whether the call stack is from application code.
     * @param array $trace the trace data
     * @return boolean whether the call stack is from application code.
     */
    protected function isCoreCode($trace)
    {
        if (isset($trace['file'])) {
            $systemPath = realpath(dirname(__FILE__) . '/..');
            return $trace['file'] === 'unknown' || strpos(realpath($trace['file']), $systemPath . DIRECTORY_SEPARATOR) === 0;
        }
        return false;
    }

    /**
     * Renders the source code around the error line.
     * @param string $file source file path
     * @param integer $errorLine the error line number
     * @param integer $maxLines maximum number of lines to display
     * @return string the rendering result
     */
    protected function renderSourceCode($file, $errorLine, $maxLines)
    {
        $errorLine--; // adjust line number to 0-based from 1-based
        $lines = false;

        if ($errorLine < 0
            || (file_exists($file) && ($lines = @file($file)) === false)
            || ($lineCount = count($lines)) <= $errorLine
        ){
            return '';
        }

        $halfLines = (int)($maxLines / 2);
        $beginLine = $errorLine - $halfLines > 0 ? $errorLine - $halfLines : 0;
        $endLine = $errorLine + $halfLines < $lineCount ? $errorLine + $halfLines : $lineCount - 1;

        $output = '';
        for ($i = $beginLine; $i <= $endLine; ++$i) {
//            $code = sprintf("%s", htmlentities(str_replace("\t", '    ', $lines[$i]), ENT_QUOTES, Xcart::app()->getTranslate()->charset));
            $code = sprintf("%s", htmlentities(str_replace("\t", '    ', $lines[$i]), ENT_QUOTES, Xcart::app()->locale['charset']));
            $output .= $code;
        }
        return strtr('<pre class="brush: php; highlight: {errorLine}; first-line: {beginLine}; toolbar: false;">{content}</pre>', [
            '{beginLine}' => $beginLine + 1,
            '{errorLine}' => $errorLine + 1,
            '{content}' => $output
        ]);
    }

    /**
     * Return correct message for each known http error code
     * @param integer $httpCode error code to map
     * @param string $replacement replacement error string that is returned if code is unknown
     * @return string the textual representation of the given error code or the replacement string if the error code is unknown
     */
    protected function getHttpHeader($httpCode, $replacement = '')
    {
        $httpCodes = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            118 => 'Connection timed out',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            210 => 'Content Different',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            310 => 'Too many Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Time-out',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested range unsatisfiable',
            417 => 'Expectation failed',
            418 => 'I’m a teapot',
            422 => 'Unprocessable entity',
            423 => 'Locked',
            424 => 'Method failure',
            425 => 'Unordered Collection',
            426 => 'Upgrade Required',
            449 => 'Retry With',
            450 => 'Blocked by Windows Parental Controls',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway ou Proxy Error',
            503 => 'Service Unavailable',
            504 => 'Gateway Time-out',
            505 => 'HTTP Version not supported',
            507 => 'Insufficient storage',
            509 => 'Bandwidth Limit Exceeded',
        ];
        return isset($httpCodes[$httpCode]) ? $httpCodes[$httpCode] : $replacement;
    }

    /**
     * @deprecated
     * @param $args
     * @return string
     */
    public function argsToString($args)
    {
        return $this->argumentsToString($args);
    }

    /**
     * @deprecated
     * @param $trace
     * @return bool
     */
    public function isCore($trace)
    {
        return $this->isCoreCode($trace);
    }

    /**
     * @deprecated
     * @param $file
     * @param $errorLine
     * @param $maxLines
     * @return string
     */
    public function renderSource($file, $errorLine, $maxLines)
    {
        return $this->renderSourceCode($file, $errorLine, $maxLines);
    }
}