<?php

namespace MaplePHP\Unitary\Kernel\Services;

use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Unitary\Kernel\DispatchConfig;

abstract class AbstractTestService
{

    protected ResponseInterface $response;
    protected ContainerInterface $container;
    protected array $args;
    protected DispatchConfig $configs;
    protected ServerRequestInterface|RequestInterface $request;

    public function __construct(ResponseInterface $response, ContainerInterface $container)
    {
        $this->response = $response;
        $this->container = $container;
        $this->args = $this->container->get("args");
        $this->request = $this->container->get("request");
        $this->configs = $this->container->get("dispatchConfig");
    }

    protected function getArg($key): mixed
    {
        return ($this->args[$key] ?? null);
    }

}