<?php

namespace Hail\Route\Dispatcher;

use Hail\Route\AbstractDispatcher;
use Hail\Route\DispatcherInterface;
use Hail\Route\Processor\Tree;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Cache router result with PSR6
 *
 * @package Hail\Database
 * @author  FENG Hao <flyinghail@msn.com>
 */
class Cache extends AbstractDispatcher implements DispatcherInterface
{
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
            $this->addRoutes($config);

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
            $result = $this->match($url);
            if ($result !== null) {
                $this->cache->save(
                    $item->set($result)
                );
            }
        }

        return $this->formatResult($result, $method);
    }
}
