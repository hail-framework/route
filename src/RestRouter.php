<?php

namespace Hail\Route;

/**
 * Class RestRouter
 *
 * @package Hail\Route
 * @author  Feng Hao <flyinghail@msn.com>
 *
 * @method self head(string $route, array|callable $handler)
 * @method self get(string $route, array|callable $handler)
 * @method self post(string $route, array|callable $handler)
 * @method self put(string $route, array|callable $handler)
 * @method self patch(string $route, array|callable $handler)
 * @method self delete(string $route, array|callable $handler)
 * @method self purge(string $route, array|callable $handler)
 * @method self options(string $route, array|callable $handler)
 * @method self trace(string $route, array|callable $handler)
 * @method self connect(string $route, array|callable $handler)
 */
class RestRouter extends Router
{
    public function __construct(array $config = [], $cache = null)
    {
        parent::__construct($config, $cache);

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
