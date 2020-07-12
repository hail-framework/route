<?php

namespace Hail\Route\Dispatcher;

trait HashTrait
{
    /**
     * @var string
     */
    protected $config;

    protected function hash(string $url = ''): string
    {
        if ($this->config === null) {
            throw new \RuntimeException('Router not init');
        }

        if ($url !== '') {
            $url = $this->url($url);
        }

        return 'ROUTE#' . \sha1($this->config . $url);
    }

    abstract protected function url(string $url): string;
}
