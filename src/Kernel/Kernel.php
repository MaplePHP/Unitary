<?php
/**
 * Unit — Part of the MaplePHP Unitary Kernel/ Dispatcher,
 * A simple and fast dispatcher, will work great for this solution
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */

declare(strict_types=1);

namespace MaplePHP\Unitary\Kernel;

use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Container\Reflection;
use MaplePHP\Emitron\Contracts\EmitterInterface;
use MaplePHP\Emitron\Emitters\CliEmitter;
use MaplePHP\Emitron\Emitters\HttpEmitter;
use MaplePHP\Emitron\RequestHandler;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Http\Interfaces\ResponseFactoryInterface;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Http\Response;
use MaplePHP\Http\ResponseFactory;
use MaplePHP\Http\Stream;
use MaplePHP\Prompts\Command;
use MaplePHP\Unitary\Utils\FileIterator;
use MaplePHP\Unitary\Utils\Router;

class Kernel
{

    private ResponseFactoryInterface $responseFactory;
    private ContainerInterface $container;
    private array $userMiddlewares;

    function __construct(ResponseFactoryInterface $responseFactory, ContainerInterface $container, array $userMiddlewares = [])
    {
        $this->responseFactory = $responseFactory;
        $this->userMiddlewares = $userMiddlewares;
        $this->container = $container;
    }

    /**
     * You can bind an instance (singleton) to an interface class that then is loaded through
     * the dependency injector preferably that in implementable of that class
     *
     * @param callable $call
     * @return void
     */
    public function bindInstToInterfaces(callable $call): void
    {
        Reflection::interfaceFactory($call);
    }

    /**
     * Run the emitter and init all routes, middlewares and configs
     *
     * @param ServerRequestInterface|RequestInterface $request
     * @return void
     * @throws \ReflectionException
     */
    public function run(ServerRequestInterface|RequestInterface $request): void
    {

        $router = new Router($request->getCliKeyword(), $request->getCliArgs());
        require_once __DIR__ . "/routes.php";

        $router->dispatch(function($controller, $args) use ($request) {

            $fileIterator = null;
            $response = $this->createResponse();
            $handler = new RequestHandler($this->userMiddlewares, $response);
            $command = new Command($response);

            $this->container->set("command", $command);
            $this->container->set("request", $request);
            $this->container->set("args", $args);

            $response = $handler->handle($request);
            $this->bindCoreInstToInterfaces($request, $response);

            [$class, $method] = $controller;
            if(method_exists($class, $method)) {
                $reflect = new Reflection($class);
                $classInst = $reflect->dependencyInjector();

                // Can replace the active Response instance through Command instance
                $hasNewResponse = $reflect->dependencyInjector($classInst, $method);

                $response = ($hasNewResponse instanceof ResponseInterface) ? $hasNewResponse : $response;
                $fileIterator = ($hasNewResponse instanceof FileIterator) ? $hasNewResponse : null;
            } else {
                $response->getBody()->write("\nERROR: Could not load Controller class {$class} and method {$method}()\n");
            }

            $this->getEmitter()->emit($response, $request);

            if ($fileIterator !== null && $this->isCli()) {
                $fileIterator->exitScript();
            }
        });
    }

    /**
     * Will bind core instances (singletons) to interface classes that then is loaded through
     * the dependency injector preferably that in implementable of that class
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    private function bindCoreInstToInterfaces(RequestInterface $request, ResponseInterface $response): void
    {
        Reflection::interfaceFactory(function ($className) use ($request, $response) {
            return match ($className) {
                "ContainerInterface" => $this->container,
                "RequestInterface" => $request,
                "ResponseInterface" => $response,
                default => null,
            };
        });
    }

    /**
     * Check if is inside a command line interface (CLI)
     *
     * @return bool
     */
    private function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * Will Create preferred Stream and Response instance depending on a platform
     *
     * @return ResponseInterface
     */
    private function createResponse(): ResponseInterface
    {
        $stream = new Stream($this->isCli() ? Stream::STDOUT : Stream::TEMP);
        $factory = new ResponseFactory();
        return $factory->createResponse(body: $stream);
    }

    /**
     * Get emitter based on a platform
     *
     * @return EmitterInterface
     */
    private function getEmitter(): EmitterInterface
    {
        return $this->isCli() ? new CliEmitter() : new HttpEmitter();
    }
}