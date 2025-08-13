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
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Unitary\Console\Middlewares\AddCommandMiddleware;
use MaplePHP\Unitary\Support\Router;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Emitron\EmitronKernel;

class Kernel
{
    private const DEFAULT_ROUTER_FILE = '/src/Console/ConsoleRouter.php';
    public const CONFIG_FILE_PATH = __DIR__ . '/../../unitary.config';

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

        // This middleware is used in the DefaultController, which is why I always load it,
        // It will not change any response but will load a CLI helper Command library
        if(!in_array(AddCommandMiddleware::class, $this->userMiddlewares)) {
            $this->userMiddlewares[] = AddCommandMiddleware::class;
        }
        EmitronKernel::setConfigFilePath(self::CONFIG_FILE_PATH);
    }

    /**
     * This will run Emitron kernel with Unitary configuration
     *
     * @param ServerRequestInterface $request
     * @return void
     * @throws Exception
     */
    public function run(ServerRequestInterface $request): void
    {
        if($this->config === null) {
            $this->config = $this->configuration($request);
        }
        $kernel = new EmitronKernel($this->container, $this->userMiddlewares, $this->config);
        $kernel->run($request);
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
            ->setRouter(function($path) use ($request) {
                $routerFile = $path . self::DEFAULT_ROUTER_FILE;
                $router = new Router($request->getCliKeyword(), $request->getCliArgs());
                if(!is_file($routerFile)) {
                    throw new Exception('The routes file (' . $routerFile . ') is missing.');
                }
                require_once $routerFile;
                return $router;
            })
            ->setProp('exitCode', 0);
    }
}