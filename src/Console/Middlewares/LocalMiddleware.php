<?php

namespace MaplePHP\Unitary\Console\Middlewares;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use MaplePHP\DTO\Format\Clock;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
        Clock::setDefaultLocale($props->locale);
        Clock::setDefaultTimezone($props->timezone);
        return $handler->handle($request);
    }
}
