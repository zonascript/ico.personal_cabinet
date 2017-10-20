<?php

namespace Xcart\App\Middleware;

use Exception;

interface IMiddleware
{
    /**
     * @param \Xcart\App\Request\Request|\Xcart\App\Request\RequestManager $request
     *
     * @void
     */
    public function processRequest($request);

    /**
     * Event owner RenderTrait
     * @param \Xcart\App\Request\Request|\Xcart\App\Request\RequestManager $request
     * @param $output string
     */
    public function processView($request, &$output);

    /**
     * @param Exception $exception
     * @void
     */
    public function processException(Exception $exception);

    /**
     * @param \Xcart\App\Request\Request|\Xcart\App\Request\RequestManager $request
     * @return mixed
     */
    public function processResponse($request);
}
