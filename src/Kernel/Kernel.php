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

use Psr\Container\ContainerInterface;
use MaplePHP\Container\Reflection;
use MaplePHP\Emitron\Contracts\EmitterInterface;
use MaplePHP\Emitron\Emitters\CliEmitter;
use MaplePHP\Emitron\Emitters\HttpEmitter;
use MaplePHP\Emitron\RequestHandler;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Http\ResponseFactory;
use MaplePHP\Http\Stream;
use MaplePHP\Log\InvalidArgumentException;
use MaplePHP\Prompts\Command;
use MaplePHP\Unitary\Contracts\RouterInterface;
use MaplePHP\Unitary\Utils\FileIterator;
use MaplePHP\Unitary\Utils\Router;

class Kernel
{

    private ContainerInterface $container;
    private array $userMiddlewares;
    private ?RouterInterface $router = null;
    private ?int $exitCode = null;
    private ?DispatchConfig $dispatchConfig = null;

    function __construct(ContainerInterface $container, array $userMiddlewares = [])
    {
        $this->userMiddlewares = $userMiddlewares;
        $this->container = $container;
    }

    public function getDispatchConfig(): DispatchConfig
    {
       if ($this->dispatchConfig === null) {
           $this->dispatchConfig = new DispatchConfig();
       }
       return $this->dispatchConfig;
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

    public function addExitCode(int $exitCode): self
    {
        $inst = clone $this;
        $inst->exitCode = $exitCode;
        return $inst;
    }

    public function addRouter(RouterInterface $router): self
    {
        $inst = clone $this;
        $inst->router = $router;
        return $inst;
    }

    /**
     * Run the emitter and init all routes, middlewares and configs
     *
     * @param ServerRequestInterface $request
     * @return void
     * @throws \ReflectionException
     */
    public function run(ServerRequestInterface $request): void
    {
        $router = $this->createRouter($request->getCliKeyword(), $request->getCliArgs());

        $router->dispatch(function($data, $args) use ($request) {
            if (!isset($data['handler'])) {
               throw new InvalidArgumentException("The router dispatch method arg 1 is missing the 'handler' key.");
            }

            $controller = $data['handler'];
            $response = $this->createResponse();

            $handler = new RequestHandler($this->userMiddlewares, $response);

            $this->container->set("request", $request);
            $this->container->set("args", $args);
            $this->container->set("dispatchConfig", $this->getDispatchConfig());

            $response = $handler->handle($request);
            $this->bindCoreInstToInterfaces($request, $response);

            [$class, $method] = $controller;
            if(method_exists($class, $method)) {
                $reflect = new Reflection($class);
                $classInst = $reflect->dependencyInjector();

                // Can replace the active Response instance through Command instance
                $hasNewResponse = $reflect->dependencyInjector($classInst, $method);
                $response = ($hasNewResponse instanceof ResponseInterface) ? $hasNewResponse : $response;

            } else {
                $response->getBody()->write("\nERROR: Could not load Controller class {$class} and method {$method}()\n");
            }

            $this->createEmitter()->emit($response, $request);

            if ($this->getDispatchConfig()->getExitCode() !== null) {
                exit($this->exitCode);
            }
        });
    }

    /**
     * Will bind core instances (singletons) to interface classes that then is loaded through
     * the dependency injector preferably that in implementable of that class
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    private function bindCoreInstToInterfaces(ServerRequestInterface $request, ResponseInterface $response): void
    {
        Reflection::interfaceFactory(function ($className) use ($request, $response) {
            return match ($className) {
                "ContainerInterface" => $this->container,
                "RequestInterface", "ServerRequestInterface" => $request,
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
     * Will create the router
     *
     * @param string $needle
     * @param array $argv
     * @return RouterInterface
     */
    private function createRouter(string $needle, array $argv): RouterInterface
    {
        if($this->router !== null) {
            return $this->router;
        }
        $router = new Router($needle, $argv);
        require_once __DIR__ . "/routes.php";
        return $router;
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
    private function createEmitter(): EmitterInterface
    {
        return $this->isCli() ? new CliEmitter() : new HttpEmitter();
    }
}