<?php

namespace Hail\Route\Dispatcher;

use Psr\SimpleCache\CacheInterface;

/**
 * Cache router result with PSR16
 *
 * @package Hail\Database
 * @author  FENG Hao <flyinghail@msn.com>
 */
class SimpleCache extends AbstractDispatcher implements DispatcherInterface
{
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
        }
    }

    public function dispatch(string $url): ?array
    {
        $key = $this->hash($url);

        $result = $this->cache->get($key);
        if ($result === null) {
            $result = $this->match($url);
            if ($result !== null) {
                $this->cache->set($key, $result);
            }
        }

        return $result;
    }

    /**
     * @param array $rules
     */
    public function setRules(array $rules): void
    {
        $this->routes = $rules;

        $this->cache->set(
            $this->hash('#routes'),
            $rules
        );
    }
}
