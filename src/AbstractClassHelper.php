<?php

namespace MaplePHP\Unitary;

use Exception;
use MaplePHP\Container\Reflection;

abstract class AbstractClassHelper
{
    protected $reflectionPool;
    protected $reflection;
    protected $constructor;
    protected $instance;

    /**
     * @throws \ReflectionException
     */
    function __construct(string $className, array $classArgs = [])
    {

        $this->reflectionPool = new Reflection($className);
        $this->reflection = $this->reflection->getReflect();
        //$this->constructor = $this->reflection->getConstructor();
        //$reflectParam = ($this->constructor) ? $this->constructor->getParameters() : [];
        if (count($classArgs) > 0) {
            $this->instance = $this->reflection->newInstanceArgs($classArgs);
        }
    }

    public function inspectMethod(string $method): array
    {
        if (!$this->reflection || !$this->reflection->hasMethod($method)) {
            throw new Exception("Method '$method' does not exist.");
        }

        $methodReflection = $this->reflection->getMethod($method);
        $parameters = [];
        foreach ($methodReflection->getParameters() as $param) {
            $paramType = $param->hasType() ? $param->getType()->getName() : 'mixed';
            $parameters[] = [
                'name' => $param->getName(),
                'type' => $paramType,
                'is_optional' => $param->isOptional(),
                'default_value' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null
            ];
        }

        return [
            'name' => $methodReflection->getName(),
            'visibility' => implode(' ', \Reflection::getModifierNames($methodReflection->getModifiers())),
            'is_static' => $methodReflection->isStatic(),
            'return_type' => $methodReflection->hasReturnType() ? $methodReflection->getReturnType()->getName() : 'mixed',
            'parameters' => $parameters
        ];
    }
    
    /**
     * Will create the main instance with dependency injection support
     *
     * @param string $className
     * @param array $args
     * @return mixed|object
     * @throws \ReflectionException
     */
    final protected function createInstance(string $className, array $args)
    {
        if(count($args) === 0) {
            return $this->reflection->dependencyInjector();
        }
        return new $className(...$args);
    }
}