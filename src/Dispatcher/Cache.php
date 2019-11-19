<?php

namespace Hail\Route\Dispatcher;

use Hail\Route\Processor\Tree;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Cache router result with PSR6
 *
 * @package Hail\Database
 * @author  FENG Hao <flyinghail@msn.com>
 */
class Cache implements DispatcherInterface
{
    use DispatcherTrait;
    use HashTrait;

    /**
     * @var CacheItemPoolInterface
     */
    protected $cache;

    public function __construct(array $config, CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
        $this->config = \serialize($config);

        $key = $this->hash('#routes');
        $item = $cache->getItem($key);

        if ($item->isHit()) {
            $this->routes = $item->get();
        } else {
            Tree::init($this->routes, $config);

            $cache->save(
                $item->set($this->routes)
            );
        }
    }

    public function dispatch(string $url, string $method = null): array
    {
        $key = $this->hash($url);

        $item = $this->cache->getItem($key);
        if ($item->isHit()) {
            $result = $item->get();
        } else {
            $result = Tree::match($url, $this->routes);
            if ($result !== null) {
                $this->cache->save(
                    $item->set($result)
                );
            }
        }

        return $this->formatResult($result, $method);
    }
}
