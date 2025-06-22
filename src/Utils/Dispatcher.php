<?php

namespace MaplePHP\Unitary\Utils;

class Dispatcher
{

    /**
     * A simple dispatcher to handle CLI request
     *
     * @param array $controller
     * @param array $args
     * @return void
     */
    public function dispatch(array $controller, array $args): void
    {
        [$class, $method] = $controller;
        if (!class_exists($class)) {
            throw new \RuntimeException("Controller class {$class} not found");
        }
        $instance = new $class();
        if (!method_exists($instance, $method)) {
            throw new \RuntimeException("Method {$method} not found in controller {$class}");
        }
        $instance->$method($args);
    }

}