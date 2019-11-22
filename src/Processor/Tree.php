<?php


namespace Hail\Route\Processor;


class Tree
{
    private const NAME = 'name';
    private const NAMES = 'names';
    private const METHODS = 'methods';
    private const ROUTE = 'route';
    private const PARAMS = 'params';

    private const CHILDREN = 'children';
    private const REGEXPS = 'regexps';
    private const VARIABLES = 'variables';

    private const SEPARATOR = "/ \t\n\r";

    public static function url(string $url): string
    {
        return \trim(\explode('?', $url, 2)[0], self::SEPARATOR);
    }

    /**
     * @param string     $url
     * @param array|null $routes
     *
     * @return array|null
     */
    public static function match(string $url, array $routes = null): ?array
    {
        if ($routes === null) {
            return null;
        }

        $path = self::url($url);
        if ($path === '') {
            $parts = [];
        } else {
            $parts = \explode('/', $path);
        }

        $params = $variables = [];
        $current = $routes;
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

    public static function init(array &$routes, array $config)
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

                    if (\is_string($methods)) {
                        $methods = \array_map('\trim',
                            \explode('|', $methods)
                        );
                    }

                    foreach (['controller', 'action', self::PARAMS] as $v) {
                        if (!empty($rule[$v])) {
                            $handler[$v] = $rule[$v];
                        }
                    }
                }

                self::parse($routes, $methods, $route, $handler);
            }
        }
    }

    /**
     * @param array          $routes
     * @param array          $methods
     * @param string         $route
     * @param array|callable $handler
     */
    public static function parse(array &$routes, array $methods, string $route, $handler): void
    {
        if ($methods === []) {
            throw new \InvalidArgumentException('Methods is empty');
        }

        if (!\is_callable($handler) && !\is_array($handler)) {
            throw new \InvalidArgumentException('Handler is not a valid type: ' . \var_export($handler, true));
        }

        if ($routes === null) {
            $routes = [
                self::CHILDREN => [],
                self::REGEXPS => [],
                self::VARIABLES => [],
            ];
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

        $pattern = '';
        foreach ($optionals as $p) {
            $pattern .= $p;
            self::add($routes, $methods, $pattern, $handler);
        }
    }

    private static function add(array &$routes, array $methods, string $route, $handler): void
    {
        if ($route === '') {
            $parts = [];
        } else {
            $parts = \explode('/', $route);
        }

        $names = [];
        $current = &$routes;
        foreach ($parts as $v) {
            if ($v[0] === '{' && $v[-1] === '}') {
                $v = \substr($v, 1, -1);
                if (\strpos($v, ':') !== false) {
                    [$name, $regexp] = \explode(':', $v, 2);

                    $regexp = '/^' . $regexp . '$/';
                    if (!isset($current[self::REGEXPS][$regexp])) {
                        $current[self::REGEXPS][$regexp] = [
                            self::CHILDREN => [],
                            self::REGEXPS => [],
                            self::VARIABLES => [],
                            self::NAME => $name,
                        ];
                    }
                    $current = &$current[self::REGEXPS][$regexp];
                } else {
                    $current[self::VARIABLES] = [
                        self::CHILDREN => [],
                        self::REGEXPS => [],
                        self::VARIABLES => [],
                    ];
                    $names[] = $v;
                    $current = &$current[self::VARIABLES];
                }
            } else {
                if (!isset($current[self::CHILDREN][$v])) {
                    $current[self::CHILDREN][$v] = [
                        self::CHILDREN => [],
                        self::REGEXPS => [],
                        self::VARIABLES => [],
                    ];
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
