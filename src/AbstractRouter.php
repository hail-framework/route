<?php

namespace Hail\Route;

use Hail\Route\Dispatcher\AbstractDispatcher;

abstract class AbstractRouter extends AbstractDispatcher
{
    /**
     * @var string[]
     */
    protected $methods = [];

    public function addMethods(array $methods)
    {
        \array_map([$this, 'addMethod'], $methods);
    }

    public function addMethod(string $method)
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
