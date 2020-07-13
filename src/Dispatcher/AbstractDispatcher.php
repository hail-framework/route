<?php

namespace Hail\Route\Dispatcher;

abstract class AbstractDispatcher
{
    protected const NAME = 'name';
    protected const NAMES = 'names';
    protected const METHODS = 'methods';
    protected const ROUTE = 'route';
    protected const PARAMS = 'params';

    protected const URL = 'url';
    protected const METHOD = 'method';
    protected const ERROR = 'error';
    protected const HANDLER = 'handler';

    protected const CHILDREN = 'children';
    protected const REGEXPS = 'regexps';
    protected const VARIABLES = 'variables';
    protected const WILDCARD = 'wildcard';

    protected const SEPARATOR = "/ \t\n\r";

    protected const NODE = [
        self::CHILDREN => [],
        self::REGEXPS => [],
        self::VARIABLES => [],
        self::WILDCARD => false,
    ];

    /**
     * @var array[]
     */
    protected $routes;

    /**
     * @var array
     */
    protected $result = [];

    abstract public function dispatch(string $url, string $method = null): array;

    protected function formatResult(?array $route, ?string $method): array
    {
        if ($route === null) {
            return $this->result = [
                self::URL => $route[self::URL],
                self::ERROR => 404,
            ];
        }

        if ($method !== null && isset($route[self::METHODS][$method])) {
            $params = $route[self::PARAMS];
            $handler = $route[self::METHODS][$method];

            if (!$handler instanceof \Closure) {
                if (isset($handler[self::PARAMS])) {
                    $params += $handler[self::PARAMS];
                }

                $handler = [
                    'app' => $handler['app'] ?? $params['app'] ?? null,
                    'controller' => $handler['controller'] ?? $params['controller'] ?? null,
                    'action' => $handler['action'] ?? $params['action'] ?? null,
                ];
            }

            return $this->result = [
                self::URL => $route[self::URL],
                self::METHOD => $method,
                self::ROUTE => $route[self::ROUTE],
                self::PARAMS => $params,
                self::HANDLER => $handler,
            ];
        }

        return $this->result = [
            self::URL => $route[self::URL],
            self::ERROR => 405,
            self::ROUTE => $route[self::ROUTE],
            self::PARAMS => $route[self::PARAMS],
            self::METHODS => \array_keys($route[self::METHODS]),
        ];
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
        if ($result[self::ERROR] === 404) {
            return null;
        }

        return $result[self::METHODS];
    }


    /**
     * @param string $key
     *
     * @return array|string|null
     */
    public function param(string $key = null)
    {
        if ($key === null) {
            return $this->result[self::PARAMS] ?? null;
        }

        return $this->result[self::PARAMS][$key] ?? null;
    }

    /**
     * @return array|\Closure
     */
    public function handler()
    {
        return $this->result[self::HANDLER];
    }

    protected function url(string $url): string
    {
        return \trim(\explode('?', $url, 2)[0], self::SEPARATOR);
    }

    /**
     * @param string $url
     *
     * @return array|null
     */
    protected function match(string $url): ?array
    {
        $path = $this->url($url);
        if ($path === '') {
            $parts = [];
        } else {
            $parts = \explode('/', $path);
        }

        $params = $variables = [];
        $current = $this->routes;
        foreach ($parts as $i => $v) {
            if (isset($current[self::CHILDREN][$v])) {
                $current = $current[self::CHILDREN][$v];
                continue;
            }

            if ($current[self::REGEXPS] !== []) {
                foreach ($current[self::REGEXPS] as $regexp => $route) {
                    if (\preg_match($regexp, $v)) {
                        $current = $route;
                        $params[$current[self::NAME]] = $v;
                        continue 2;
                    }
                }
            }

            if ($current[self::VARIABLES] !== []) {
                $current = $current[self::VARIABLES];
                $variables[] = $v;
                continue;
            }

            if ($current[self::WILDCARD]) {
                $params['*'] = \implode('/', \array_slice($parts, $i));
            }

            break;
        }

        if (!isset($current[self::METHODS])) {
            return null;
        }

        if (isset($current[self::NAMES])) {
            foreach ($current[self::NAMES] as $i => $key) {
                $params[$key] = $variables[$i];
            }
        }

        return [
            self::URL => $url,
            self::METHODS => $current[self::METHODS],
            self::ROUTE => $current[self::ROUTE],
            self::PARAMS => $params,
        ];
    }

    /**
     * @return array[]
     */
    public function getRoutes(): ?array
    {
        return $this->routes;
    }

    /**
     * @param array[] $routes
     */
    public function setRoutes(array $routes): void
    {
        $this->routes = $routes;
    }
}
