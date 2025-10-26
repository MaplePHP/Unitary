<?php

namespace MaplePHP\Unitary\Console\Middlewares;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use MaplePHP\Prompts\Command;

class AddCommandMiddleware implements MiddlewareInterface
{
    private ContainerInterface $container;
    private StreamInterface $stream;

    /**
     * Get the active Container and Stream instance with the Dependency injector
     *
     * @param ContainerInterface $container
     * @param StreamInterface $stream
     */
    public function __construct(ContainerInterface $container, StreamInterface $stream)
    {
        $this->container = $container;
        $this->stream = $stream;
    }

    /**
     * Will bind current Stream to the Command CLI library class
     * this is initialized and passed to the Container
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->container->set("command", new Command($this->stream));
        return $handler->handle($request);
    }
}
