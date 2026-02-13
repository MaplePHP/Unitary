<?php

namespace MaplePHP\Unitary\Console\Controllers;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use MaplePHP\Emitron\Contracts\DispatchConfigInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use MaplePHP\Prompts\Command;
use MaplePHP\Unitary\Config\ConfigProps;
use MaplePHP\Validate\Validator;

abstract class DefaultController
{
    protected readonly ServerRequestInterface|RequestInterface $request;
    protected readonly ContainerInterface $container;
    protected Command $command;
    protected DispatchConfigInterface $configs;
    protected array $args;
    protected ?ConfigProps $props = null;
    protected string|bool $path;

    /**
     * Set some data type safe object that comes from container and the dispatcher
     *
     * @param ContainerInterface $container
     * @param ResponseInterface $response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ErrorException
     */
    public function __construct(ContainerInterface $container, ResponseInterface $response)
    {
        $this->container = $container;
        $this->args = $this->container->get("args");
        $this->props = $this->container->get("props");
        $this->command = $this->container->get("command");
        $this->request = $this->container->get("request");
        $this->configs = $this->container->get("configuration");
        $defaultPath = $this->container->get("request")->getUri()->getDir();
        $defaultPath = ($this->configs->getProps()->path !== null) ? $this->configs->getProps()->path : $defaultPath;
        $this->path = realpath($this->args['path'] ?? $defaultPath);
        $this->forceShowHelp($response);
    }

    /**
     * This is a temporary solution that will show help if a user
     * writes a wrong argv param in CLI
     *
     * @param ResponseInterface $response
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ErrorException
     */
    protected function forceShowHelp(ResponseInterface $response): void
    {
        if (!Validator::value($response->getStatusCode())->isHttpSuccess()) {
            $props = $this->configs->getProps();
            $help = ($props->helpController !== null) ?
                $props->helpController : "\MaplePHP\Unitary\Console\Controllers\HelpController";
            $help = new $help($this->container, $response->withStatus(200));
            $help->index();
            exit(1);
        }
    }
}
