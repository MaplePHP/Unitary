<?php

namespace MaplePHP\Unitary\Console\Controllers;

use MaplePHP\Container\Interfaces\ContainerExceptionInterface;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Container\Interfaces\NotFoundExceptionInterface;
use MaplePHP\DTO\Format\Clock;
use MaplePHP\Emitron\Contracts\DispatchConfigInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Prompts\Command;
use MaplePHP\Unitary\Config\ConfigProps;
use Throwable;

abstract class DefaultController
{
    protected readonly ServerRequestInterface|RequestInterface $request;
    protected readonly ContainerInterface $container;
    protected Command $command;
    protected DispatchConfigInterface $configs;
    protected array $args;
    protected ?ConfigProps $props = null;

    /**
     * Set some data type safe object that comes from container and the dispatcher
     *
     * @param ContainerInterface $container
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->args = $this->container->get("args");
        $this->command = $this->container->get("command");
        $this->request = $this->container->get("request");
        $this->configs = $this->container->get("dispatchConfig");
    }
}
