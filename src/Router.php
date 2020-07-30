<?php

namespace Hail\Route;

/**
 * Class Router
 *
 * @package Hail\Route
 * @author  Feng Hao <flyinghail@msn.com>
 */
class Router implements RouterInterface
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

    protected const HANDLER_APP = 'app';
    protected const HANDLER_CONTROLLER = 'controller';
    protected const HANDLER_ACTION = 'action';

    /**
     * @var array[]
     */
    protected $routes;

    /**
     * @var string[]
     */
    protected $methods = [];

    /**
     * @var array
     */
    protected $result = [];

    /**
     * Router constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
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
        $result = $this->match($url);

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
     * @return array
     */
    public function result(): array
    {
        return $this->result;
    }

    /**
     * @param string|null $url
     *
     * @return array|null
     */
    public function methods(string $url = null): ?array
    {
        if ($url !== null) {
            $result = $this->dispatch($url);
        } else {
            if ($this->result === null) {
                return null;
            }

            $result = $this->result;
        }

        if ($result[self::ERROR] === 404) {
            return null;
        }

        return $result[self::METHODS];
    }

    /**
     * @return array|null
     */
    public function params(): ?array
    {
        return $this->result[self::PARAMS] ?? null;
    }

    /**
     * @param string $key
     *
     * @return string|null
     */
    public function param(string $key): ?string
    {
        return $this->result[self::PARAMS][$key] ?? null;
    }

    /**
     * @return array|\Closure
     */
    public function handler()
    {
        return $this->result[self::HANDLER];
    }

    public function addMethods(array $methods): void
    {
        \array_map([$this, 'addMethod'], $methods);
    }

    public function addMethod(string $method): void
    {
        $this->methods[\strtoupper($method)] = true;
    }

    public function __call($name, $arguments)
    {
        $name = \strtoupper($name);
        if (!isset($this->methods[$name])) {
            throw new \RuntimeException("\"{$name}\" Method not support!");
        }

        $this->addRoute([$name], ...$arguments);

        return $this;
    }

    /**
     * @param string|array $methods
     *
     * @return array
     */
    protected function parseMethods($methods): array
    {
        if (empty($methods)) {
            throw new \InvalidArgumentException('Methods is empty');
        }

        if (\is_string($methods)) {
            $methods = \explode('|', $methods);
        }

        if (!\is_array($methods)) {
            throw new \InvalidArgumentException('Methods must be array');
        }

        foreach ($methods as $method) {
            $method = \strtoupper(\trim($method));
            if (!isset($this->methods[$method])) {
                throw new \InvalidArgumentException("\"$method\" method not supported");
            }
        }

        return $methods;
    }

    public function addRoutes(array $config): void
    {
        foreach ($config as $app => $rules) {
            $app = \ucfirst($app);
            foreach ($rules as $route => $rule) {
                $handler = ['app' => $app];
                $methods = ['GET'];

                if (\is_string($rule)) {
                    $route = $rule;
                } else {
                    $methods = $this->parseMethods($rule[self::METHODS] ?? $methods);

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

        $methods = $this->parseMethods($methods);

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
