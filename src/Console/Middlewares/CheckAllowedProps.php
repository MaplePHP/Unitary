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

    /**
     * Will automatically trigger 404 response codes if used prop does not exist
     * as an option in config prop class, with exception for help and if CLI keyword is empty
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cliKeyword = $request->getCliKeyword();
        $response = $handler->handle($request);
        foreach ($this->args as $key => $value) {
            if(!$this->configs->getProps()->hasProp($key) && ($cliKeyword === '' || $key !== "help")) {
                $response = $response->withStatus(404);
                break;
            }
        }
        return $response;
    }
}
