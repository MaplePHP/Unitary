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

use Exception;
use MaplePHP\Emitron\Contracts\DispatchConfigInterface;
use MaplePHP\Emitron\DispatchConfig;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Unitary\Kernel\Middlewares\AddCommandMiddleware;
use MaplePHP\Unitary\Utils\Router;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Emitron\Kernel as EmitronKernel;

class Kernel
{
    public const CONFIG_FILE_PATH = __DIR__ . '/../../unitary.config';
    private ContainerInterface $container;
    private array $userMiddlewares;
    private ?DispatchConfig $dispatchConfig;

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
        $this->dispatchConfig = $dispatchConfig;

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
        if($this->dispatchConfig === null) {
            $this->dispatchConfig = $this->configuration($request);
        }
        $kernel = new EmitronKernel($this->container, $this->userMiddlewares, $this->dispatchConfig);
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
                $routerFile = $path . "/src/Kernel/routes.php";
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