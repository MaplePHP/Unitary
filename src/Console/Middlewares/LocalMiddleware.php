<?php

namespace MaplePHP\Unitary\Console\Middlewares;

use MaplePHP\Container\Interfaces\ContainerExceptionInterface;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Container\Interfaces\NotFoundExceptionInterface;
use MaplePHP\DTO\Format\Clock;
use MaplePHP\Emitron\Contracts\MiddlewareInterface;
use MaplePHP\Emitron\Contracts\RequestHandlerInterface;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;

class LocalMiddleware implements MiddlewareInterface
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
     * @throws \DateInvalidTimeZoneException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $props = $this->container->get("props");
        Clock::setDefaultLocale($props->local);
        Clock::setDefaultTimezone($props->timezone);
        return $handler->handle($request);
    }
}
