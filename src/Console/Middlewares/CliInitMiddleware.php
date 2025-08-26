<?php

namespace MaplePHP\Unitary\Console\Middlewares;

use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Emitron\Contracts\MiddlewareInterface;
use MaplePHP\Emitron\Contracts\RequestHandlerInterface;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Http\Interfaces\StreamInterface;
use MaplePHP\Prompts\Command;

class CliInitMiddleware implements MiddlewareInterface
{
    /**
     * In CLI, the status code is used as the exit code rather than an HTTP status code.
     * By default, a successful execution should return 0 as the exit code.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if ($this->isCli()) {
            $response = $response->withStatus(0);
        }
        return $response;
    }

    /**
     * Check if is inside a command line interface (CLI)
     *
     * @return bool
     */
    protected function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }
}
