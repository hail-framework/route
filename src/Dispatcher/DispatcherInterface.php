<?php

namespace Hail\Route\Dispatcher;

interface DispatcherInterface
{
    public function dispatch(string $url, string $method = null): array;

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

    public function getRoutes(): ?array;

    public function setRoutes(array $routes): void;
}
