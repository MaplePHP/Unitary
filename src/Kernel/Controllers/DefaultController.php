<?php

namespace MaplePHP\Unitary\Kernel\Controllers;

use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;

abstract class DefaultController
{
    protected readonly ServerRequestInterface|RequestInterface $request;
    protected readonly ContainerInterface $container;

    public function __construct(
        ServerRequestInterface|RequestInterface $request,
        ContainerInterface $container
    ) {
        $this->request = $request;
        $this->container = $container;
    }
}