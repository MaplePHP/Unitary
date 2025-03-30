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

use ArrayIterator;
use Closure;
use Exception;
use MaplePHP\Log\InvalidArgumentException;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

class TestMocker
{
    protected object $instance;

    static private mixed $return;

    protected $reflection;

    protected $methods;

    function __construct(string $className, array $args = [])
    {
        $this->reflection = new ReflectionClass($className);
        $this->methods = $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC);

    }

    /**
     * Executes the creation of a dynamic mock class and returns an instance of the mock.
     *
     * @return mixed
     */
    function execute(): mixed
    {
        $className = $this->reflection->getName();
        $mockClassName = 'UnitaryMockery_' . uniqid();
        $overrides = $this->overrideMethods();
        $code = "
            class {$mockClassName} extends {$className} {
                {$overrides}
            }
        ";
        eval($code);
        return new $mockClassName();
    }

    function return(mixed $returnValue): self
    {


        self::$return = $returnValue;
        return $this;
    }


    static public function getReturn(): mixed
    {
        return self::$return;
    }

    /**
     * @param array $types
     * @return string
     * @throws \ReflectionException
     */
    function getReturnValue(array $types): string
    {
        $property = new ReflectionProperty($this, 'return');
        if ($property->isInitialized($this)) {
            $type = gettype(self::getReturn());
            if($types && !in_array($type, $types) && !in_array("mixed", $types)) {
                throw new InvalidArgumentException("Mock value \"" . self::getReturn() . "\"  should return data type: " . implode(', ', $types));
            }

            return $this->getMockValueForType($type, self::getReturn());
        }
        if ($types) {
             return $this->getMockValueForType($types[0]);
        }
        return "return 'MockedValue';";
    }

    /**
     * Overrides all methods in class
     *
     * @return string
     */
    protected function overrideMethods(): string
    {
        $overrides = '';
        foreach ($this->methods as $method) {
            if ($method->isConstructor()) {
                continue;
            }

            $params = [];
            $methodName = $method->getName();
            $types = $this->getReturnType($method);
            $returnValue = $this->getReturnValue($types);

            foreach ($method->getParameters() as $param) {
                $paramStr = '';
                if ($param->hasType()) {
                    $paramStr .= $param->getType() . ' ';
                }
                if ($param->isPassedByReference()) {
                    $paramStr .= '&';
                }
                $paramStr .= '$' . $param->getName();
                if ($param->isDefaultValueAvailable()) {
                    $paramStr .= ' = ' . var_export($param->getDefaultValue(), true);
                }
                $params[] = $paramStr;
            }

            $paramList = implode(', ', $params);
            $returnType = ($types) ? ': ' . implode('|', $types) : '';
            $overrides .= "
                public function {$methodName}({$paramList}){$returnType}
                {
                    {$returnValue}
                }
                ";
        }

        return $overrides;
    }

    /**
     * Get expected return types
     *
     * @param $method
     * @return array
     */
    protected function getReturnType($method): array
    {
        $types = [];
        $returnType = $method->getReturnType();
        if ($returnType instanceof ReflectionNamedType) {
            $types[] = $returnType->getName();
        } elseif ($returnType instanceof ReflectionUnionType) {
            foreach ($returnType->getTypes() as $type) {
                $types[] = $type->getName();
            }

        } elseif ($returnType instanceof ReflectionIntersectionType) {
            $intersect = array_map(fn($type) => $type->getName(), $returnType->getTypes());
            $types[] = $intersect;
        }
        if(!in_array("mixed", $types) && $returnType->allowsNull()) {
            $types[] = "null";
        }
        return $types;
    }

    /**
     * Generates a mock value for the specified type.
     *
     * @param string $typeName The name of the type for which to generate the mock value.
     * @param bool $nullable Indicates if the returned value can be nullable.
     * @return mixed Returns a mock value corresponding to the given type, or null if nullable and conditions allow.
     */
    protected function getMockValueForType(string $typeName, mixed $value = null, bool $nullable = false): mixed
    {
        $typeName = strtolower($typeName);
        if(!is_null($value)) {
            return "return \MaplePHP\Unitary\TestMocker::getReturn();";
        }
        $mock = match ($typeName) {
            'integer' => "return 123456;",
            'double' => "return 3.14;",
            'string' => "return 'mockString';",
            'boolean' => "return true;",
            'array' => "return ['item'];",
            'object' => "return (object)['item'];",
            'resource' => "return fopen('php://memory', 'r+');",
            'callable' => "return fn() => 'called';",
            'iterable' => "return new ArrayIterator(['a', 'b']);",
            'null' => "return null;",
            'void' => "",
            default => 'return class_exists($typeName) ? new class($typeName) extends TestMocker {} : null;',
        };
        return $nullable && rand(0, 1) ? null : $mock;
    }


    /**
     * Will return a streamable content
     * @param $resourceValue
     * @return string|null
     */
    protected function handleResourceContent($resourceValue)
    {
        return var_export(stream_get_contents($resourceValue), true);
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
        if (method_exists($this->instance, $name)) {

            $types = $this->getReturnType($name);
            if(!isset($types[0]) && is_null($this->return)) {
                throw new Exception("Could automatically mock Method \"$name\". " .
                    "You will need to manually mock it with ->return([value]) mock method!");
            }

            if (!is_null($this->return)) {
                return $this->return;
            }

            if(isset($types[0]) && is_array($types[0]) && count($types[0]) > 0) {
                $last = end($types[0]);
                return new self($last);
            }

            $mockValue = $this->getMockValueForType($types[0]);
            if($mockValue instanceof self) {
                return $mockValue;
            }

            if(!in_array(gettype($mockValue), $types)) {
                throw new Exception("Mock value $mockValue is not in the return type " . implode(', ', $types));
            }
            return $mockValue;
        }

        throw new \BadMethodCallException("Method \"$name\" does not exist in class \"" . $this->instance::class . "\".");
    }
}