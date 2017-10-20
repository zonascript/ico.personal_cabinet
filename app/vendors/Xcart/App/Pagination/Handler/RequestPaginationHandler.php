<?php

namespace Xcart\App\Pagination\Handler;

use Xcart\App\Pagination\Exception\IncorrectPageException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class RequestPaginationHandler
 * @package Xcart\App\Pagination\Handler
 */
class RequestPaginationHandler implements PaginationHandlerInterface
{
    /**
     * @var \Closure
     */
    protected $callback;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * RequestPaginationHandler constructor.
     * @param RequestStack $requestStack
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(RequestStack $requestStack, UrlGeneratorInterface $urlGenerator)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function getPageSize($key, $defaultPageSize)
    {
        $pageSize = $this->request->query->getInt($key);
        if (empty($pageSize) || $pageSize < 1) {
            return $defaultPageSize;
        }

        return (int)$pageSize;
    }

    /**
     * {@inheritdoc}
     */
    public function getPage($key, $defaultPage = 1)
    {
        $page = $this->request->query->getInt($key);
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
        return $this->urlGenerator->generate(
            $this->request->attributes->get('_route'),
            array_merge(
                $this->request->attributes->all(),
                $this->request->query->all(),
                [$key => $value]
            )
        );
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
            $this->callback->__invoke($this);
        } else {
            throw new IncorrectPageException();
        }
    }
}