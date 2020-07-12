<?php

namespace Hail\Route;


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
}