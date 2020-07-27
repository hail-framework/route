<?php

namespace Hail\Route;

/**
 * Class RestRouter
 *
 * @package Hail\Route
 * @author  Feng Hao <flyinghail@msn.com>
 *
 * @method $this head(string $route, array|callable $handler)
 * @method $this get(string $route, array|callable $handler)
 * @method $this post(string $route, array|callable $handler)
 * @method $this put(string $route, array|callable $handler)
 * @method $this patch(string $route, array|callable $handler)
 * @method $this delete(string $route, array|callable $handler)
 * @method $this purge(string $route, array|callable $handler)
 * @method $this options(string $route, array|callable $handler)
 * @method $this trace(string $route, array|callable $handler)
 * @method $this connect(string $route, array|callable $handler)
 */
class RestRouter extends Router
{
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->addMethods([
            'HEAD',
            'GET',
            'POST',
            'PUT',
            'PATCH',
            'DELETE',
            'PURGE',
            'OPTIONS',
            'TRACE',
            'CONNECT',
        ]);
    }
}
