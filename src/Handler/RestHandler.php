<?php

namespace Hail\Route\Handler;


class RestHandler extends ControllerHandler
{
    public function __construct(array $options)
    {
        parent::__construct($options);

        $method = $options['method'] ?? 'get';

        $this->method .= \ucfirst(\strtolower($method));
    }
}
