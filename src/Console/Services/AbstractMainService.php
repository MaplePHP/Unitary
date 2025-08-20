<?php

namespace MaplePHP\Unitary\Console\Services;

use MaplePHP\Container\Interfaces\ContainerExceptionInterface;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Container\Interfaces\NotFoundExceptionInterface;
use MaplePHP\Emitron\Contracts\DispatchConfigInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Prompts\Command;
use MaplePHP\Unitary\Config\ConfigProps;

abstract class AbstractMainService
{
    protected ResponseInterface $response;
    protected ContainerInterface $container;
    protected array $args;
    protected Command $command;
    protected DispatchConfigInterface $configs;
    protected ServerRequestInterface|RequestInterface $request;
    protected ?ConfigProps $props = null;

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function __construct(ResponseInterface $response, ContainerInterface $container)
    {
        $this->response = $response;
        $this->container = $container;
        $this->args = $this->container->get("args");
        $this->request = $this->container->get("request");
        $this->configs = $this->container->get("dispatchConfig");
        $this->command = $this->container->get("command");
        $this->props = $this->container->get("props");
    }

}
