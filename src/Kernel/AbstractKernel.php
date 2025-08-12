<?php
/**
 * Unit — Part of the MaplePHP Unitary Kernel/Dispatcher,
 * A simple and fast dispatcher, will work great for this solution
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */

declare(strict_types=1);

namespace MaplePHP\Unitary\Kernel;

use MaplePHP\Container\Reflection;
use MaplePHP\Emitron\Contracts\DispatchConfigInterface;
use MaplePHP\Emitron\Contracts\EmitterInterface;
use MaplePHP\Emitron\DispatchConfig;
use MaplePHP\Emitron\Emitters\CliEmitter;
use MaplePHP\Emitron\Emitters\HttpEmitter;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Http\ResponseFactory;
use MaplePHP\Http\Stream;
use MaplePHP\Container\Interfaces\ContainerInterface;

abstract class AbstractKernel
{

    public const CONFIG_FILE_PATH = __DIR__ . '/../emitron.config';

    protected static ?string $configFilePath = null;

    protected ContainerInterface $container;
    protected array $userMiddlewares;
    protected ?DispatchConfigInterface $dispatchConfig = null;
    protected array $config = [];

    /**
     * @param ContainerInterface $container
     * @param array $userMiddlewares
     * @param DispatchConfigInterface|null $dispatchConfig
     * @throws \Exception
     */
    public function __construct(
        ContainerInterface $container,
        array $userMiddlewares = [],
        ?DispatchConfigInterface $dispatchConfig = null,
    ) {
        $this->userMiddlewares = $userMiddlewares;
        $this->container = $container;
        $this->dispatchConfig = ($dispatchConfig === null) ?
            new DispatchConfig(static::getConfigFilePath()) : $dispatchConfig;
    }

    /**
     * Makes it easy to specify a config file inside a custom kernel file
     *
     * @param string $path
     * @return void
     */
    public static function setConfigFilePath(string $path): void
    {
        static::$configFilePath = $path;
    }

    /**
     * Get expected config file
     *
     * @return string
     */
    public static function getConfigFilePath(): string
    {
        if(static::$configFilePath === null) {
            return static::CONFIG_FILE_PATH;
        }
        return static::$configFilePath;
    }

    /**
     * Get config instance for configure dispatch result
     *
     * @return DispatchConfigInterface
     */
    public function getDispatchConfig(): DispatchConfigInterface
    {
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

    /**
     * Will bind core instances (singletons) to interface classes that then is loaded through
     * the dependency injector preferably that in implementable of that class
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    protected function bindCoreInstToInterfaces(ServerRequestInterface $request, ResponseInterface $response): void
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
    protected function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * Will Create preferred Stream and Response instance depending on a platform
     *
     * @return ResponseInterface
     */
    protected function createResponse(): ResponseInterface
    {
        $stream = new Stream($this->isCli() ? Stream::STDOUT : Stream::TEMP);
        $factory = new ResponseFactory();
        $response = $factory->createResponse(body: $stream);
        if ($this->isCli()) {
            // In CLI, the status code is used as the exit code rather than an HTTP status code.
            // By default, a successful execution should return 0 as the exit code.
            $response = $response->withStatus(0);
        }
        return $response;
    }

    /**
     * Get emitter based on a platform
     *
     * @return EmitterInterface
     */
    protected function createEmitter(): EmitterInterface
    {
        return $this->isCli() ? new CliEmitter() : new HttpEmitter();
    }

}