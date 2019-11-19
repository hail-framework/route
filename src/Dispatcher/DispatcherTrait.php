<?php
namespace Hail\Route\Dispatcher;


use Hail\Route\Processor\Tree;

/**
 * Trait DispatcherTrait
 *
 * @package Hail\Route
 * @author  Feng Hao <flyinghail@msn.com>
 */
trait DispatcherTrait
{
    /**
     * @var array
     */
    protected $result = [];

    /**
     * @var array[]
     */
    protected $routes;

    protected function formatResult(?array $route, ?string $method): array
    {
        if ($route === null) {
            $result = [
                'url' => $route['url'],
                'error' => 404
            ];
        } elseif ($method !== null && isset($route['methods'][$method])) {
            $params = $route['params'];
            $handler = $route['methods'][$method];

            if (!$handler instanceof \Closure) {
                if (isset($handler['params'])) {
                    $params += $handler['params'];
                }

                $handler = [
                    'app' => $handler['app'] ?? $params['app'] ?? null,
                    'controller' => $handler['controller'] ?? $params['controller'] ?? null,
                    'action' => $handler['action'] ?? $params['action'] ?? null,
                ];
            }

            $result = [
                'url' => $route['url'],
                'method' => $method,
                'route' => $route['route'],
                'params' => $params,
                'handler' => $handler,
            ];
        } else {
            $result = [
                'url' => $route['url'],
                'error' => 405,
                'route' => $route['route'],
                'params' => $route['params'],
                'allowed' => \array_keys($route['methods']),
            ];
        }

        return $this->result = $result;
    }

    /**
     * @return array
     */
    public function result(): array
    {
        return $this->result;
    }

    /**
     * @param string $url
     *
     * @return array|null
     */
    public function methods(string $url): ?array
    {
        $result = $this->dispatch($url);
        if ($result['error'] === 404) {
            return null;
        }

        return $result['allowed'];
    }


    /**
     * @param string $key
     *
     * @return array|string|null
     */
    public function param(string $key = null)
    {
        if ($key === null) {
            return $this->result['params'] ?? null;
        }

        return $this->result['params'][$key] ?? null;
    }

    /**
     * @return array|\Closure
     */
    public function handler()
    {
        return $this->result['handler'];
    }
}
