<?php

namespace MaplePHP\Unitary\Kernel\Controllers;

use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Prompts\Command;

abstract class DefaultController
{
    protected readonly ServerRequestInterface|RequestInterface $request;
    protected readonly ContainerInterface $container;
    protected Command $command;
    protected array $args;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->args = $this->container->get("args");
        $this->command = $this->container->get("command");
        $this->request = $this->container->get("request");
    }
}