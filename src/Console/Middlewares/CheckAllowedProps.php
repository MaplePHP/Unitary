<?php

namespace MaplePHP\Unitary\Console\Middlewares;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use MaplePHP\Emitron\Contracts\DispatchConfigInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CheckAllowedProps implements MiddlewareInterface
{

    protected DispatchConfigInterface $configs;
    protected array $args;

    /**
     * Get the active Container instance with the Dependency injector
     *
     * @param ContainerInterface $container
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $this->args = $container->get("args");
        $this->configs = $container->get("configuration");
    }


    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        foreach ($this->args as $key => $value) {
            if(!$this->configs->getProps()->hasProp($key)) {
                $response = $response->withStatus(404);
                break;
            }
        }
        return $response;
    }

}
