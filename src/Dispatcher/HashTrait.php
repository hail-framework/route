<?php

namespace Hail\Route\Dispatcher;

use Hail\Route\Processor\Tree;

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
            $url = Tree::url($url);
        }

        return 'ROUTE#' . \sha1($this->config . $url);
    }
}
