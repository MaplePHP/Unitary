<?php
namespace MaplePHP\Unitary\Console;

use MaplePHP\Blunder\Interfaces\AbstractHandlerInterface;
use MaplePHP\Blunder\Run;
use MaplePHP\Container\Container;
use MaplePHP\Http\Environment;
use MaplePHP\Http\ServerRequest;
use MaplePHP\Http\Uri;
use MaplePHP\Emitron\Kernel as EmitronKernel;
use MaplePHP\Unitary\Console\Middlewares\{AddCommandMiddleware,
    CheckAllowedProps,
    CliInitMiddleware,
    ConfigPropsMiddleware,
    LocalMiddleware};

final class Application
{
    public function __construct()
    {
        // Default config
        if(is_file(__DIR__ . '/../../../../../unitary.config.php')) {
            // From the vendor dir
            EmitronKernel::setConfigFilePath(__DIR__ . '/../../../../../unitary.config.php');
        } else {
            // From the repo dir
            EmitronKernel::setConfigFilePath(__DIR__ . '/../../unitary.config.php');
        }
        EmitronKernel::setRouterFilePath(__DIR__ . "/ConsoleRouter.php");
    }

    /**
     * Change router file
     *
     * @param string $path
     * @return $this
     */
    public function withRouter(string $path): self
    {
        $inst = clone $this;
        EmitronKernel::setRouterFilePath($path);
        return $inst;
    }

    /**
     * Change the config file
     *
     * @param string $path
     * @return $this
     */
    public function withConfig(string $path): self
    {
        $inst = clone $this;
        EmitronKernel::setConfigFilePath($path);
        return $inst;
    }

    /**
     * Default error handler boot
     * @param AbstractHandlerInterface $handler
     * @return $this
     */
    public function withErrorHandler(AbstractHandlerInterface $handler): self
    {
        $inst = clone $this;
        $run = new Run($handler);
        $run->severity()
            ->excludeSeverityLevels([E_USER_WARNING, E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED])
            ->redirectTo(function () {
                // Let PHP’s default error handler process excluded severities
                return false;
            });
        $run->setExitCode(1);
        $run->load();
        return $inst;
    }

    /**
     * @param array $parts
     * @return Kernel
     * @throws \Exception
     */
    public function boot(array $parts): Kernel
    {
        $env = new Environment();
        $request = new ServerRequest(new Uri($env->getUriParts($parts)), $env);
        $kernel = new Kernel(new Container(), [
            AddCommandMiddleware::class,
            ConfigPropsMiddleware::class,
            CheckAllowedProps::class,
            LocalMiddleware::class,
            CliInitMiddleware::class
        ]);
        $kernel->run($request);
        return $kernel;
    }
}
