<?php

namespace Hail\Route\Dispatcher;

use Hail\Route\Processor\Tree;
use Psr\SimpleCache\CacheInterface;

/**
 * Cache router result with PSR16
 *
 * @package Hail\Database
 * @author  FENG Hao <flyinghail@msn.com>
 */
class SimpleCache implements DispatcherInterface
{
    use DispatcherTrait;
    use HashTrait;

    /**
     * @var CacheInterface
     */
    protected $cache;

    public function __construct(array $config, CacheInterface $cache)
    {
        $this->cache = $cache;
        $this->config = \serialize($config);

        $key = $this->hash('#routes');
        $item = $cache->get($key);

        if ($item !== null) {
            $this->routes = $item;
        } else {
            Tree::init($this->routes, $config);
            $cache->set($key, $this->routes);
        }
    }

    public function dispatch(string $url, string $method = null): array
    {
        $key = $this->hash($url);

        $result = $this->cache->get($key);
        if ($result === null) {
            $result = Tree::match($url, $this->routes);
            if ($result !== null) {
                $this->cache->set($key, $result);
            }
        }

        return $this->formatResult($result, $method);
    }
}
