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

    /**
     * @var array[]
     */
    protected $routes;

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
    public function getRules(): ?array
    {
        return $this->routes;
    }

    /**
     * @param array[] $rules
     */
    public function setRules(array $rules): void
    {
        $this->routes = $rules;
    }
}
