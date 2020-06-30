<?php

namespace Hail\Route;

use Hail\Route\Processor\Tree;
use Hail\Route\Dispatcher\DispatcherTrait;
use Psr\SimpleCache\CacheInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Class Router
 *
 * @package Hail\Route
 * @author  Feng Hao <flyinghail@msn.com>
 */
class Router implements RouterInterface
{
    use DispatcherTrait;

    /**
     * @var DispatcherInterface
     */
    protected $cache;

    public function __construct(array $config = [], $cache = null)
    {
        if ($cache instanceof CacheInterface) {
            $this->cache = new Dispatcher\SimpleCache($config, $cache);
        } elseif ($cache instanceof CacheItemPoolInterface) {
            $this->cache = new Dispatcher\Cache($config, $cache);
        }

        if ($config !== []) {
            $this->addRoutes($config);
        }
    }

    /**
     * @param string $method
     * @param string $url
     *
     * @return array
     */
    public function dispatch(string $url, string $method = null): array
    {
        $result = null;
        if ($this->routes !== null) {
            $result = Tree::match($url, $this->routes);
        }

        if ($result === null && $this->cache) {
             return $this->cache->dispatch($url, $method);
        }

        return $this->formatResult($result, $method);
    }

    /**
     * @param array $config
     */
    public function addRoutes(array $config): void
    {
        Tree::init($this->routes, $config);
    }

    /**
     * @param string|array   $methods
     * @param string         $route
     * @param array|callable $handler
     *
     * @throws \InvalidArgumentException
     */
    public function addRoute($methods, string $route, $handler): void
    {
        if (\is_string($methods)) {
            $methods = \array_map('\trim', \explode('|', $methods));
        }

        Tree::parse($this->routes, $methods, $route, $handler);
    }

    public function head(string $route, $handler): RouterInterface
    {
        $this->addRoute(['HEAD'], $route, $handler);

        return $this;
    }

    public function get(string $route, $handler): RouterInterface
    {
        $this->addRoute(['GET'], $route, $handler);

        return $this;
    }

    public function post(string $route, $handler): RouterInterface
    {
        $this->addRoute(['POST'], $route, $handler);

        return $this;
    }

    public function put(string $route, $handler): RouterInterface
    {
        $this->addRoute(['PUT'], $route, $handler);

        return $this;
    }

    public function patch(string $route, $handler): RouterInterface
    {
        $this->addRoute(['PATCH'], $route, $handler);

        return $this;
    }

    public function delete(string $route, $handler): RouterInterface
    {
        $this->addRoute(['DELETE'], $route, $handler);

        return $this;
    }

    public function purge(string $route, $handler): RouterInterface
    {
        $this->addRoute(['PURGE'], $route, $handler);

        return $this;
    }

    public function options(string $route, $handler): RouterInterface
    {
        $this->addRoute(['OPTIONS'], $route, $handler);

        return $this;
    }

    public function trace(string $route, $handler): RouterInterface
    {
        $this->addRoute(['TRACE'], $route, $handler);

        return $this;
    }

    public function connect(string $route, $handler): RouterInterface
    {
        $this->addRoute(['CONNECT'], $route, $handler);

        return $this;
    }
}
