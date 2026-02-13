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

namespace MaplePHP\Unitary\Console;

use Exception;
use MaplePHP\Emitron\Contracts\DispatchConfigInterface;
use MaplePHP\Emitron\DispatchConfig;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use MaplePHP\Unitary\Support\Router;
use Psr\Container\ContainerInterface;
use MaplePHP\Emitron\Kernel as EmitronKernel;

class Kernel
{
    private const DEFAULT_ROUTER_FILE = '/src/Console/ConsoleRouter.php';
    public const UNITARY_DIR = __DIR__ . '/../../';
    public const DEFAULT_CONFIG_FILE_PATH = __DIR__ . '/../../unitary.config';

    private ContainerInterface $container;
    private array $userMiddlewares;
    private ?DispatchConfig $config;

    /**
     * Unitary kernel file
     *
     * @param ContainerInterface $container
     * @param array $userMiddlewares
     * @param DispatchConfig|null $dispatchConfig
     */
    public function __construct(
        ContainerInterface $container,
        array $userMiddlewares = [],
        ?DispatchConfig $dispatchConfig = null,
    ) {
        $this->container = $container;
        $this->userMiddlewares = $userMiddlewares;
        $this->config = $dispatchConfig;

        if(is_file(self::UNITARY_DIR . '/../../../unitary.config.php')) {
            EmitronKernel::setConfigFilePath(self::UNITARY_DIR . '/../../../unitary.config');
        } else {
            EmitronKernel::setConfigFilePath(self::DEFAULT_CONFIG_FILE_PATH);
        }
    }

    /**
     * This will run Emitron kernel with Unitary configuration
     *
     * @param ServerRequestInterface $request
     * @param StreamInterface|null $stream
     * @return void
     * @throws Exception
     */
    public function run(ServerRequestInterface $request, ?StreamInterface $stream = null): void
    {
        if ($this->config === null) {
            $this->config = $this->configuration($request);
        }
        $kernel = new EmitronKernel($this->container, $this->userMiddlewares, $this->config);
        $kernel->run($request, $stream);
    }

    /**
     * This is the default unitary configuration
     *
     * @param ServerRequestInterface $request
     * @return DispatchConfigInterface
     * @throws Exception
     */
    private function configuration(ServerRequestInterface $request): DispatchConfigInterface
    {
        $config = new DispatchConfig(EmitronKernel::getConfigFilePath());
        return $config
            ->setRouter(function ($routerFile) use ($request) {
                $router = new Router($request->getCliKeyword(), $request->getCliArgs());
                if (!is_file($routerFile)) {
                    throw new Exception('The routes file (' . $routerFile . ') is missing.');
                }
                $newRouterInst = require_once $routerFile;
                if (!($newRouterInst instanceof Router)) {
                    throw new \RuntimeException('You need to return the router instance ' .
                        'at the end of the router file (' . $routerFile . ').');
                }
                return $newRouterInst;
            })
            ->setProp('exitCode', 0);
    }
}
