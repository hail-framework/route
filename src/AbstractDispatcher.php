<?php

namespace Hail\Route;

abstract class AbstractDispatcher
{
    protected const NAME = 'name';
    protected const NAMES = 'names';
    protected const METHODS = 'methods';
    protected const ROUTE = 'route';
    protected const PARAMS = 'params';

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

    protected function url(string $url): string
    {
        return \trim(\explode('?', $url, 2)[0], self::SEPARATOR);
    }

    /**
     * @param string     $url
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
            'url' => $url,
            self::METHODS => $current[self::METHODS],
            self::ROUTE => $current[self::ROUTE],
            self::PARAMS => $params,
        ];
    }

    public function addRoutes(array $config): void
    {
        foreach ($config as $app => $rules) {
            $app = \ucfirst($app);
            foreach ($rules as $route => $rule) {
                $handler = ['app' => $app];
                $methods = ['GET', 'POST'];

                if (\is_string($rule)) {
                    $route = $rule;
                } else {
                    $methods = $rule[self::METHODS] ?? $methods;

                    foreach (['controller', 'action', self::PARAMS] as $v) {
                        if (!empty($rule[$v])) {
                            $handler[$v] = $rule[$v];
                        }
                    }
                }

                $this->addRoute($methods, $route, $handler);
            }
        }
    }

    /**
     * @param array|string   $methods
     * @param string         $route
     * @param array|callable $handler
     */
    public function addRoute($methods, string $route, $handler): void
    {
        if (empty($methods)) {
            throw new \InvalidArgumentException('Methods is empty');
        }

        if (!\is_callable($handler) && !\is_array($handler)) {
            throw new \InvalidArgumentException('Handler is not a valid type: ' . \var_export($handler, true));
        }

        $route = \trim($route, self::SEPARATOR);

        $len = \strlen($route);

        $route = \rtrim($route, ']');
        $num = $len - \strlen($route);

        if (\strpos($route, ']') !== false) {
            throw new \InvalidArgumentException('Optional can only be defined at the end of a route');
        }

        $optionals = \explode('[', $route);
        if ($num !== \count($optionals) - 1) {
            throw new \InvalidArgumentException('Optional pattern error, "[" and "]" does not match');
        }

        if (\is_string($methods)) {
            $methods = \array_map('\trim', \explode('|', $methods));
        }

        if ($this->routes === null) {
            $this->routes = self::NODE;
        }

        $pattern = '';
        foreach ($optionals as $p) {
            $pattern .= $p;
            $this->parseRoute($methods, $pattern, $handler);
        }
    }

    /**
     * @param array          $methods
     * @param string         $route
     * @param array|callable $handler
     */
    private function parseRoute(array $methods, string $route, $handler): void
    {
        if ($route === '') {
            $parts = [];
        } else {
            $parts = \explode('/', $route);
        }

        $endIndex = \count($parts) - 1;

        $names = [];
        $current = &$this->routes;
        foreach ($parts as $index => $v) {
            if ($v[0] === '{' && $v[-1] === '}') {
                $v = \substr($v, 1, -1);
                if (\strpos($v, ':') !== false) {
                    [$name, $regexp] = \explode(':', $v, 2);

                    $regexp = '/^' . $regexp . '$/';
                    if (!isset($current[self::REGEXPS][$regexp])) {
                        $current[self::REGEXPS][$regexp] = self::NODE + [
                                self::NAME => $name,
                            ];
                    }
                    $current = &$current[self::REGEXPS][$regexp];
                } else {
                    $current[self::VARIABLES] = self::NODE;
                    $names[] = $v;
                    $current = &$current[self::VARIABLES];
                }
            } elseif ($v === '*') {
                if ($index !== $endIndex) {
                    throw new \InvalidArgumentException('Wildcard can only be defined at the end of a route');
                }
                $current[self::WILDCARD] = true;
            } else {
                if (!isset($current[self::CHILDREN][$v])) {
                    $current[self::CHILDREN][$v] = self::NODE;
                }
                $current = &$current[self::CHILDREN][$v];
            }
        }

        if ($names !== []) {
            $current[self::NAMES] = $names;
        }

        $current[self::ROUTE] = $route;

        if (!isset($current[self::METHODS])) {
            $current[self::METHODS] = [];
        }

        foreach ($methods as $v) {
            $current[self::METHODS][\strtoupper($v)] = $handler;
        }
    }
}