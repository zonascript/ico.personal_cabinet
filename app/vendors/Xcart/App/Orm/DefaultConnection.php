<?php
namespace Xcart\App\Orm;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Connection as DBALConnection;
use Xcart\App\Main\Xcart;

class DefaultConnection extends DBALConnection
{
    private $__errHandler = null;
    private $__enableErrHandler = true;
    private $__ignoreErrors = false;

    private $__countQueries = 0;

    public function setErrorHandler($handlerLink)
    {
        $this->__errHandler = $handlerLink;
        return $this;
    }

    public function unsetErrorHandler()
    {
        $this->__errHandler = null;
        return $this;
    }

    public function setEnableErrHandler($enable = true)
    {
        $this->__enableErrHandler = $enable;
        return $this;
    }

    public function setIgnoreErrors($ignore = false)
    {

        $this->__ignoreErrors = $ignore;
        return $this;
    }


//    /**
//     * {@inheritdoc}
//     */
//    public function connect()
//    {
//        return $this->__internalCall(__FUNCTION__, func_get_args());
//    }

    /**
     * {@inheritdoc}
     */
    public function delete($tableExpression, array $identifier, array $types = array())
    {
        return $this->__internalCall(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function update($tableExpression, array $data, array $identifier, array $types = array())
    {
        return $this->__internalCall(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function insert($tableExpression, array $data, array $types = array())
    {
        return $this->__internalCall(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($statement)
    {
        return $this->__internalCall(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function executeQuery($query, array $params = array(), $types = array(), QueryCacheProfile $qcp = null)
    {
        $this->__countQueries++;
        return $this->__internalCall(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function executeCacheQuery($query, $params, $types, QueryCacheProfile $qcp)
    {
        return $this->__internalCall(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function query()
    {
        $this->__countQueries++;
        return $this->__internalCall(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function executeUpdate($query, array $params = array(), array $types = array())
    {
        $this->__countQueries++;
        return $this->__internalCall(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function exec($statement)
    {
        $this->__countQueries++;
        return $this->__internalCall(__FUNCTION__, func_get_args());
    }

    private function __internalCall($function, array $args = [])
    {
        if (!$this->__enableErrHandler) {
            call_user_func_array('parent::' . $function, $args);
        }

        try {
            return call_user_func_array('parent::' . $function, $args);
        }
        catch (DBALException $e) {
            $this->processException($e, $args[0]);
        }

        return null;
    }

    public function getCountQueries()
    {
        return $this->__countQueries;
    }

    /**
     * @param DBALException $exception
     * @param string $query
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function processException($exception, $query = '')
    {
        if ($this->__ignoreErrors) {
            return;
        }

        if ($this->__errHandler) {
            call_user_func_array($this->__errHandler, [$exception, $query]);
            return;
        }


        $msg = '';

        if (Xcart::app()->getIsWebMode()) {

            $login = '';
            $session = Xcart::app()->request->session;

            if ($session) {
                $login = $session->get('admin_login') ?: $session->get('admin_login');
            }

            $msg .= "Site        : ".((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER["HTTP_HOST"]. $_SERVER['REQUEST_URI']."\n";
            $msg .= "Remote IP   : {$_SERVER['REMOTE_ADDR']}\n";
            $msg .= "Logged as   : {$login}\n";
        }

        if (!empty($query)) {

            $msg .= "SQL query   : {$query}\n";
        }

        $msg .= "Error code  : ".$exception->getCode()."\n";
        $msg .= "Description : ".$exception->getMessage() ."\n\n";
        $msg .= "Backtrace: \n";
        $msg .= $exception->getTraceAsString();

//        $oMail = Xcart::app()->mail;
//        $oMail->to = 'team@s3stores.com';
//        $oMail->from = ('team@s3stores.com');
//        $oMail->subject = 'S3 Stores, Inc.: SQL error notification';
//        $oMail->body = $msg;
//        $oMail->sendEmail();

        if (function_exists('x_log_add')) {
            x_log_add('SQL', $msg);
        }
        else {
            print_r($msg);
        }
    }
}