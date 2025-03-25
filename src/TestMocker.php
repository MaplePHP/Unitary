<?php
/**
 * @Package:    MaplePHP - Lightweight test wrapper for class method overrides.
 *              Extend this class to a new mock class or anonymous class
 *              and override specific methods for testing.
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */

namespace MaplePHP\Unitary;

use Closure;
use Exception;
use MaplePHP\Container\Reflection;

abstract class TestMocker
{
    protected object $instance;
    private array $methods = [];

    /**
     * Pass class and the class arguments if exists
     *
     * @param string $className
     * @param array $args
     * @throws Exception
     */
    public function __construct(string $className, array $args = [])
    {
        if (!class_exists($className)) {
            throw new Exception("Class $className does not exist.");
        }
        $this->instance = $this->createInstance($className, $args);
    }

    /**
     * Will bind Closure to class instance and directly return the Closure
     *
     * @param Closure $call
     * @return Closure
     */
    public function bind(Closure $call): Closure
    {
        return $call->bindTo($this->instance);
    }

    /**
     * Overrides a method in the instance
     *
     * @param string $method
     * @param Closure $call
     * @return $this
     */
    public function override(string $method, Closure $call): self
    {
        if( !method_exists($this->instance, $method)) {
            throw new \BadMethodCallException(
                "Method '$method' does not exist in the class '" . get_class($this->instance) .
                "' and therefore cannot be overridden or called."
            );
        }
        $call = $call->bindTo($this->instance);
        $this->methods[$method] = $call;
        return $this;
    }

    /**
     * Add a method to the instance, allowing it to be called as if it were a real method.
     *
     * @param string $method
     * @param Closure $call
     * @return $this
     */
    public function add(string $method, Closure $call): self
    {
        if(method_exists($this->instance, $method)) {
            throw new \BadMethodCallException(
                "Method '$method' already exists in the class '" . get_class($this->instance) .
                "'. Use the 'override' method in TestWrapper instead."
            );
        }
        $call = $call->bindTo($this->instance);
        $this->methods[$method] = $call;
        return $this;
    }

    /**
     * Proxies calls to the wrapped instance or bound methods.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws Exception
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (isset($this->methods[$name])) {
            return $this->methods[$name](...$arguments);
        }

        if (method_exists($this->instance, $name)) {
            return call_user_func_array([$this->instance, $name], $arguments);
        }
        throw new Exception("Method $name does not exist.");
    }


}