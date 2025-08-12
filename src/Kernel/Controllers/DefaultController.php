<?php

namespace MaplePHP\Unitary\Kernel\Controllers;

use MaplePHP\Emitron\Contracts\DispatchConfigInterface;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Prompts\Command;
use MaplePHP\Unitary\ConfigProps;

abstract class DefaultController
{
    protected readonly ServerRequestInterface|RequestInterface $request;
    protected readonly ContainerInterface $container;
    protected Command $command;
    protected DispatchConfigInterface $configs;
    protected array $args;
    private ?ConfigProps $props = null;

    /**
     * Set some data type safe object that comes from container and the dispatcher
     *
     * @param ContainerInterface $container
     * @throws \MaplePHP\Container\Interfaces\ContainerExceptionInterface
     * @throws \MaplePHP\Container\Interfaces\NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->args = $this->container->get("args");
        $this->command = $this->container->get("command");
        $this->request = $this->container->get("request");
        $this->configs = $this->container->get("dispatchConfig");

        $this->buildAllowedProps();
    }

    /**
     * Builds the list of allowed CLI arguments from ConfigProps.
     *
     * These properties can be defined either in the configuration file or as CLI arguments.
     * If invalid arguments are passed, and verbose mode is enabled, an error will be displayed
     * along with a warning about the unknown properties.
     *
     * @return void
     */
    private function buildAllowedProps(): void
    {
        if($this->props === null) {
            try {
                $props = array_merge($this->configs->getProps()->toArray(), $this->autoCastArgsToType());
                $this->props = new ConfigProps($props);

            } catch (\RuntimeException $e) {
                if($e->getCode() === 2 && isset($this->args['verbose'])) {
                    $this->command->error($e->getMessage());
                    $this->command->message(
                        "One or more arguments you passed are not recognized as valid options.\n" .
                        "Check your command syntax or configuration."
                    );
                    exit(1);
                }
            } catch (\Throwable $e) {
                if(isset($this->args['verbose'])) {
                    $this->command->error($e->getMessage());
                    exit(1);
                }
            }
        }
    }


    /**
     * Will try to auto cast argument data type from CLI argument
     *
     * @return array
     */
    private function autoCastArgsToType(): array
    {
        $args = [];
        foreach($this->args as $key => $value) {
            $lower = strtolower($value);
            if ($lower === "true") {
                $value = true;
            }
            if ($lower === "false") {
                $value = false;
            }
            if (is_numeric($value)) {
                $value = (strpos($value, '.') !== false) ? (float)$value : (int)$value;
            }
            $args[$key] = ($value === "") ? null : $value;
        }
        return $args;
    }


}