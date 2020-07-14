<?php

namespace Hail\Route\Dispatcher;

interface DispatcherInterface
{
    public function dispatch(string $url): ?array;

    public function getRules(): ?array;

    public function setRules(array $rules): void;
}
