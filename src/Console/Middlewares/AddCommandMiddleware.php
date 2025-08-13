<?php

namespace MaplePHP\Unitary\Console\Middlewares;

use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Emitron\Contracts\MiddlewareInterface;
use MaplePHP\Emitron\Contracts\RequestHandlerInterface;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Prompts\Command;

class AddCommandMiddleware implements MiddlewareInterface
{
    private ContainerInterface $container;

    /**
     * Get the active Container instance with the Dependency injector
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Will bind current Response and Stream to the Command CLI library class
     * this is initialized and passed to the Container
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $this->container->set("command", new Command($response));
        return $response;
    }
}