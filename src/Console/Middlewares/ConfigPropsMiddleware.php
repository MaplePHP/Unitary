<?php

namespace MaplePHP\Unitary\Console\Middlewares;

use MaplePHP\Container\Interfaces\ContainerExceptionInterface;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Container\Interfaces\NotFoundExceptionInterface;
use MaplePHP\Emitron\Contracts\MiddlewareInterface;
use MaplePHP\Emitron\Contracts\RequestHandlerInterface;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Unitary\Config\ConfigProps;
use Throwable;

class ConfigPropsMiddleware implements MiddlewareInterface
{

    protected ?ConfigProps $props = null;
    private ContainerInterface $container;

    /**
     * Get the active Container instance with the Dependency injector
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Will bind current Response and Stream to the Command CLI library class
     * this is initialized and passed to the Container
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->container->set("props", $this->getInitProps());
        return $handler->handle($request);
    }


    /**
     * Builds the list of allowed CLI arguments from ConfigProps.
     *
     * These properties can be defined either in the configuration file or as CLI arguments.
     * If invalid arguments are passed, and verbose mode is enabled, an error will be displayed
     * along with a warning about the unknown properties.
     *
     * @return ConfigProps
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getInitProps(): ConfigProps
    {

        if ($this->props === null) {

            $args = $this->container->get("args");
            $configs = $this->container->get("dispatchConfig");
            $command = $this->container->get("command");

            try {
                $props = array_merge($configs->getProps()->toArray(), $args);
                $this->props = new ConfigProps($props);

                if ($this->props->hasMissingProps() !== [] && isset($args['verbose'])) {
                    $command->error('The properties (' .
                        implode(", ", $this->props->hasMissingProps()) . ') is not exist in config props');
                    $command->message(
                        "One or more arguments you passed are not recognized as valid options.\n" .
                        "Check your command syntax or configuration."
                    );
                }

            } catch (Throwable $e) {
                if (isset($args['verbose'])) {
                    $command->error($e->getMessage());
                    exit(1);
                }
            }
        }
        return $this->props;
    }
}
