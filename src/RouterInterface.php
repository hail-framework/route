<?php

namespace Hail\Route;

interface RouterInterface
{
    public function addMethods(array $methods): void;

    public function addMethod(string $method): void;

    public function addRoutes(array $config): void;

    public function addRoute($methods, string $route, $handler): void;

    public function result(): array;

    public function methods(string $url): ?array;

    /**
     * @param string $key
     *
     * @return array|string|null
     */
    public function param(string $key = null);

    /**
     * @return array|\Closure
     */
    public function handler();

    public function dispatch(string $url, string $method = null): array;
}
