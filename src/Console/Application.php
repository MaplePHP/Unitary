<?php
namespace MaplePHP\Unitary\Console;

use MaplePHP\Blunder\Interfaces\AbstractHandlerInterface;
use MaplePHP\Blunder\Run;
use MaplePHP\Container\Container;
use MaplePHP\Http\Environment;
use MaplePHP\Http\ServerRequest;
use MaplePHP\Http\Uri;
use MaplePHP\Unitary\Console\Middlewares\{
    AddCommandMiddleware,
    CliInitMiddleware,
    ConfigPropsMiddleware,
    LocalMiddleware
};

final class Application
{

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
            LocalMiddleware::class,
            CliInitMiddleware::class
        ]);
        $kernel->run($request);
        return $kernel;
    }
}
