<?php

namespace MaplePHP\Unitary\Console\Services;

use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Emitron\Contracts\DispatchConfigInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Prompts\Command;

abstract class AbstractMainService
{
    protected ResponseInterface $response;
    protected ContainerInterface $container;
    protected array $args;
    protected Command $command;
    protected DispatchConfigInterface $configs;
    protected ServerRequestInterface|RequestInterface $request;

    public function __construct(ResponseInterface $response, ContainerInterface $container)
    {
        $this->response = $response;
        $this->container = $container;
        $this->args = $this->container->get("args");
        $this->request = $this->container->get("request");
        $this->configs = $this->container->get("dispatchConfig");
        $this->command = $this->container->get("command");
    }

}