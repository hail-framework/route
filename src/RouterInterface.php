<?php

namespace Hail\Route;

use Hail\Route\Dispatcher\DispatcherInterface;

interface RouterInterface extends DispatcherInterface
{
    public function addRoutes(array $config): void;

    public function addRoute($methods, string $route, $handler): void;

    public function head(string $route, $handler): self;

    public function get(string $route, $handler): self;

    public function post(string $route, $handler): self;

    public function put(string $route, $handler): self;

    public function patch(string $route, $handler): self;

    public function delete(string $route, $handler): self;

    public function purge(string $route, $handler): self;

    public function options(string $route, $handler): self;

    public function trace(string $route, $handler): self;

    public function connect(string $route, $handler): self;
}
