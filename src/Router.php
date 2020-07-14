<?php

namespace Hail\Route;

use Hail\Route\Dispatcher\DispatcherInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Class Router
 *
 * @package Hail\Route
 * @author  Feng Hao <flyinghail@msn.com>
 */
class Router extends AbstractRouter implements RouterInterface
{
    /**
     * @var DispatcherInterface
     */
    protected $cache;

    /**
     * Router constructor.
     *
     * @param array                                 $config
     * @param CacheInterface|CacheItemPoolInterface $cache
     */
    public function __construct(array $config = [], $cache = null)
    {
        if ($config !== []) {
            if ($cache instanceof CacheInterface) {
                $this->cache = new Dispatcher\SimpleCache($config, $cache);
            } elseif ($cache instanceof CacheItemPoolInterface) {
                $this->cache = new Dispatcher\Cache($config, $cache);
            } else {
                $this->addRoutes($config);
            }

            if ($this->cache && $this->cache->getRoutes() === null) {
                $this->addRoutes($config);
                $this->cache->setRoutes($this->routes);
                $this->routes = null;
            }
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
            $result = $this->match($url);
        }

        if ($result === null && $this->cache) {
            $result = $this->cache->dispatch($url, $method);
        }

        if ($result === null) {
            return $this->result = [
                self::URL => $result[self::URL],
                self::ERROR => 404,
            ];
        }

        if ($method !== null && isset($result[self::METHODS][$method])) {
            $params = $result[self::PARAMS];
            $handler = $result[self::METHODS][$method];

            if (!$handler instanceof \Closure) {
                if (isset($handler[self::PARAMS])) {
                    $params += $handler[self::PARAMS];
                }

                $handler = [
                    self::HANDLER_APP => $handler[self::HANDLER_APP] ?? $params[self::HANDLER_APP] ?? null,
                    self::HANDLER_CONTROLLER => $handler[self::HANDLER_CONTROLLER] ?? $params[self::HANDLER_CONTROLLER] ?? null,
                    self::HANDLER_ACTION => $handler[self::HANDLER_ACTION] ?? $params[self::HANDLER_ACTION] ?? null,
                    self::METHOD => $method,
                ];
            }

            return $this->result = [
                self::URL => $result[self::URL],
                self::METHOD => $method,
                self::ROUTE => $result[self::ROUTE],
                self::PARAMS => $params,
                self::HANDLER => $handler,
            ];
        }

        return $this->result = [
            self::URL => $result[self::URL],
            self::ERROR => 405,
            self::ROUTE => $result[self::ROUTE],
            self::PARAMS => $result[self::PARAMS],
            self::METHODS => \array_keys($result[self::METHODS]),
        ];
    }
}
