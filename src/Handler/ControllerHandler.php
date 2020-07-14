<?php

namespace Hail\Route\Handler;


class ControllerHandler
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var array
     */
    protected static $instances;

    public function __construct(array $options)
    {
        $namespace = '\\';
        if (isset($options['namespace'])) {
            $namespace = \trim($options['namespace'], '\\');
        }

        if (isset($options['app'])) {
            $namespace .= '\\' . \ucfirst($options['app']);
        }

        if (isset($options['controller'])) {
            if (\strpos($options['controller'], '-') !== false) {
                $class = \implode('',
                    \array_map('\ucfirst',
                        \explode('-', $options['controller'])
                    )
                );
            } else {
                $class = \ucfirst($options['controller']);
            }
        } else {
            $class = 'Index';
        }

        $class = \strpos($class, $namespace) === 0 ? $class : $namespace . '\\' . $class;

        $action = $options['action'] ?? 'index';
        $actionClass = $class . '\\' . \ucfirst($action);
        if (\class_exists($actionClass) && \method_exists($actionClass, '__invoke')) {
            $class = $actionClass;
            $method = '__invoke';
        } elseif (\class_exists($class)) {
            $method = \lcfirst($action);

            if (!\method_exists($class, $method)) {
                throw new \InvalidArgumentException("Action not defined: {$class}::{$method}", 404);
            }
        } else {
            throw new \InvalidArgumentException("Controller not defined: {$class}", 404);
        }

        $this->class = $class;
        $this->method = $method;
    }

    public function instance(array $options): self
    {
        $keys = [];
        if (isset($options['namespace'])) {
            $keys[] = \trim($options['namespace'], '\\');
        }

        if (isset($options['app'])) {
            $keys[] = $options['app'];
        }

        $keys[] = $options['controller'] ?? 'index';
        $key = \implode(':', $keys);

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($options);
        }

        return self::$instances[$key];
    }

    public function __invoke()
    {
        return [$this->class, $this->method];
    }
}
