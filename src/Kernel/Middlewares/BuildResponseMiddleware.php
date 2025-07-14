<?php
declare(strict_types=1);

namespace MaplePHP\Unitary\Kernel\Middlewares;

use MaplePHP\Emitron\Contracts\MiddlewareInterface;
use MaplePHP\Emitron\Contracts\RequestHandlerInterface;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Prompts\Command;

class BuildResponseMiddleware implements MiddlewareInterface
{

    private array $controllerDispatch;

    function __construct(array $controllerDispatch)
    {
        $this->controllerDispatch = $controllerDispatch;
    }

    /**
     * Get the body content length reliably with PSR Stream.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //$this->container->set("request", $this->request);
        //$this->container->set("args", $args);

        $command = new Command();
        [$class, $method] = $this->controllerDispatch;
        if(method_exists($class, $method)) {

            $inst = new $class($this->request, $this->container);
            $response = $inst->{$method}($args, $command);


            if ($response instanceof ResponseInterface) {
                $stream = $response->getBody();
                $stream->rewind();
                echo $stream->getContents();
            }

        } else {
            $command->error("The controller {$class}::{$method}() not found");
        }

        return $response;
    }
}