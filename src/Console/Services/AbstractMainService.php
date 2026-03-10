<?php

namespace MaplePHP\Unitary\Console\Services;

use MaplePHP\Emitron\Contracts\ConfigPropsInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use MaplePHP\Emitron\Contracts\DispatchConfigInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use MaplePHP\Prompts\Command;

abstract class AbstractMainService
{
    protected ResponseInterface $response;
    protected ContainerInterface $container;
    protected array $args;
    protected Command $command;
    protected DispatchConfigInterface $configs;
    protected ServerRequestInterface|RequestInterface $request;
    protected ?ConfigPropsInterface $props = null;

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
        $this->configs = $this->container->get("configuration");
        $this->command = $this->container->get("command");
        $this->props = $this->container->get("props");
    }

}
