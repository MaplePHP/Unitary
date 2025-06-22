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

use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Prompts\Command;
use MaplePHP\Unitary\Utils\Router;

class Kernel
{

    private ServerRequestInterface $request;
    private ContainerInterface $container;
    private Router $router;

    function __construct(ServerRequestInterface|RequestInterface $request, ContainerInterface $container)
    {
        $this->request = $request;
        $this->container = $container;
        $this->router = new Router($this->request->getCliKeyword(), $this->request->getCliArgs());
    }

    /**
     * Dispatch routes and call controller
     *
     * @return void
     */
    function dispatch()
    {
        $router = $this->router;
        require_once __DIR__ . "/routes.php";

        $this->container->set("request", $this->request);

        $router->dispatch(function($controller, $args) {
            $command = new Command();
            [$class, $method] = $controller;
            if(method_exists($class, $method)) {
                $inst = new $class($this->request, $this->container);
                $inst->{$method}($args, $command);

            } else {
                $command->error("The controller {$class}::{$method}() not found");
            }
        });
    }
}