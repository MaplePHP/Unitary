<?php

namespace MaplePHP\Unitary\Console\Controllers;

use MaplePHP\Container\Interfaces\ContainerExceptionInterface;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Container\Interfaces\NotFoundExceptionInterface;
use MaplePHP\Emitron\Contracts\DispatchConfigInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Prompts\Command;
use MaplePHP\Unitary\Config\ConfigProps;
use Throwable;

abstract class DefaultController
{
    protected readonly ServerRequestInterface|RequestInterface $request;
    protected readonly ContainerInterface $container;
    protected Command $command;
    protected DispatchConfigInterface $configs;
    protected array $args;
    protected ?ConfigProps $props = null;

    /**
     * Set some data type safe object that comes from container and the dispatcher
     *
     * @param ContainerInterface $container
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->args = $this->container->get("args");
        $this->command = $this->container->get("command");
        $this->request = $this->container->get("request");
        $this->configs = $this->container->get("dispatchConfig");

        // $this->props is set in getInitProps
        $this->container->set("props", $this->getInitProps());
    }

    /**
     * Builds the list of allowed CLI arguments from ConfigProps.
     *
     * These properties can be defined either in the configuration file or as CLI arguments.
     * If invalid arguments are passed, and verbose mode is enabled, an error will be displayed
     * along with a warning about the unknown properties.
     *
     * @return ConfigProps
     */
    private function getInitProps(): ConfigProps
    {
        if($this->props === null) {
            try {
                $props = array_merge($this->configs->getProps()->toArray(), $this->args);
                $this->props = new ConfigProps($props);

                if($this->props->hasMissingProps() !== [] && isset($this->args['verbose'])) {
                    $this->command->error('The properties (' .
                        implode(", ", $this->props->hasMissingProps()) . ') is not exist in config props');
                    $this->command->message(
                        "One or more arguments you passed are not recognized as valid options.\n" .
                        "Check your command syntax or configuration."
                    );
                }

            } catch (Throwable $e) {
                if(isset($this->args['verbose'])) {
                    $this->command->error($e->getMessage());
                    exit(1);
                }
            }
        }
        return $this->props;
    }

}