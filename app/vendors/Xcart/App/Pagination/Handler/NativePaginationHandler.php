<?php
namespace Xcart\App\Pagination\Handler;
use Xcart\App\Pagination\BasePagination;
use Xcart\App\Pagination\Exception\IncorrectPageException;

/**
 * Class NativePaginationHandler
 * @package Xcart\App\Pagination\Handler
 */
class NativePaginationHandler implements PaginationHandlerInterface
{
    /** @var  BasePagination */
    protected $pager;

    /**
     * @var \Closure
     */
    protected $callback;

    private function defaultWrongPageCallback()
    {
        if ($this->pager->getPagesCount()) {
            header("Location: " . $this->getUrlForQueryParam($this->pager->getPageKey(), $this->pager->getPagesCount()), true, 302);
            die();
        }
    }

    public function setPager($pager)
    {
        $this->pager = $pager;
    }

    /**
     * {@inheritdoc}
     */
    public function getPageSize($key, $defaultPageSize)
    {
        $pageSize = isset($_GET[$key]) ? (int)$_GET[$key] : $defaultPageSize;
        if (empty($pageSize) || $pageSize < 1) {
            $pageSize = $defaultPageSize;
        }

        return (int)$pageSize;
    }

    /**
     * {@inheritdoc}
     */
    public function getPage($key, $defaultPage = 1)
    {
        $page = isset($_GET[$key]) ? (int)$_GET[$key] : 1;
        if (empty($page) || $page < 1) {
            $page = $defaultPage;
        }

        return (int)$page;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrlForQueryParam($key, $value)
    {
        $uri = parse_url($_SERVER['REQUEST_URI']);
        if (!isset($uri['query'])) {
            $uri['query'] = '';
        }
        parse_str($uri['query'], $params);
        $params[$key] = $value;
        return $uri['path'] . "?" . http_build_query($params);
    }

    /**
     * @param callable $callback
     */
    public function setIncorrectPageCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Throw exception or redirect user to correct page
     */
    public function wrongPageCallback()
    {
        if (is_callable($this->callback)) {
            $this->callback->__invoke($this, $this->pager);
        } else {
            $this->defaultWrongPageCallback();
//            throw new IncorrectPageException();
        }
    }
}