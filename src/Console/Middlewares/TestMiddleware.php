<?php

namespace MaplePHP\Unitary\Console\Middlewares;

use MaplePHP\Emitron\Contracts\MiddlewareInterface;
use MaplePHP\Emitron\Contracts\RequestHandlerInterface;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;

class TestMiddleware implements MiddlewareInterface
{
    /**
     * Just a test middleware to test execution
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $response->getBody()->write("\n");
        $response->getBody()->write("Hello World from: " . get_class($this));
        $response->getBody()->write("\n");
        return $response;
    }
}
