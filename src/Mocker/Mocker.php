<?php
/**
 * @Package:    MaplePHP - Lightweight test mocker
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */

namespace MaplePHP\Unitary\Mocker;

use Reflection;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

class Mocker
{
    protected object $instance;

    static private mixed $return;

    protected ReflectionClass $reflection;

    protected string $className;
    protected string $mockClassName;
    protected array $constructorArgs = [];

    protected array $overrides = [];

    protected array $methods;
    protected array $methodList = [];

    protected static ?MethodPool $methodPool = null;

    /**
     * @param string $className
     * @param array $args
     * @throws \ReflectionException
     */
    public function __construct(string $className, array $args = [])
    {
        $this->className = $className;
        $this->reflection = new ReflectionClass($className);
        $this->methods = $this->reflection->getMethods();
        $this->constructorArgs = $args;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Override the default method overrides with your own mock logic and validation rules
     *
     * @return MethodPool
     */
    public function getMethodPool(): MethodPool
    {
        if(is_null(self::$methodPool)) {
            self::$methodPool = new MethodPool($this);
        }
        return self::$methodPool;
    }

    public function getMockedClassName(): string
    {
        return $this->mockClassName;
    }

    /**
     * Executes the creation of a dynamic mock class and returns an instance of the mock.
     *
     * @return object An instance of the dynamically created mock class.
     * @throws \ReflectionException
     */
    public function execute(): object
    {
        $className = $this->reflection->getName();

        $shortClassName = explode("\\", $className);
        $shortClassName = end($shortClassName);

        $this->mockClassName = 'Unitary_' . uniqid() . "_Mock_" . $shortClassName;
        $overrides = $this->generateMockMethodOverrides($this->mockClassName);
        $unknownMethod = $this->errorHandleUnknownMethod($className);
        $code = "
            class {$this->mockClassName} extends {$className} {
                {$overrides}
                {$unknownMethod}
            }
        ";

        eval($code);
        return new $this->mockClassName(...$this->constructorArgs);
    }

    /**
     * Handles the situation where an unknown method is called on the mock class.
     * If the base class defines a __call method, it will delegate to it.
     * Otherwise, it throws a BadMethodCallException.
     *
     * @param string $className The name of the class for which the mock is created.
     * @return string The generated PHP code for handling unknown method calls.
     */
    private function errorHandleUnknownMethod(string $className): string
    {
        if(!in_array('__call', $this->methodList)) {
            return "
                public function __call(string \$name, array \$arguments) {
                    if (method_exists(get_parent_class(\$this), '__call')) {
                        return parent::__call(\$name, \$arguments);
                    }
                    throw new \\BadMethodCallException(\"Method '\$name' does not exist in class '{$className}'.\");
                }
            ";
        }
        return "";
    }

    /**
     * @param array $types
     * @param mixed $method
     * @param MethodItem|null $methodItem
     * @return string
     */
    protected function getReturnValue(array $types, mixed $method, ?MethodItem $methodItem = null): string
    {
        // Will overwrite the auto generated value
        if($methodItem && $methodItem->hasReturn()) {
            return "return " . var_export($methodItem->return, true) . ";";
        }
        if ($types) {
            return $this->getMockValueForType($types[0], $method);
        }
        return "return 'MockedValue';";
    }

    /**
     * Builds and returns PHP code that overrides all public methods in the class being mocked.
     * Each overridden method returns a predefined mock value or delegates to the original logic.
     *
     * @return string PHP code defining the overridden methods.
     * @throws \ReflectionException
     */
    protected function generateMockMethodOverrides(string $mockClassName): string
    {
        $overrides = '';
        foreach ($this->methods as $method) {
            if ($method->isConstructor() || $method->isFinal()) {
                continue;
            }

            $methodName = $method->getName();
            $this->methodList[] = $methodName;

            // The MethodItem contains all items that are validatable
            $methodItem = $this->getMethodPool()->get($methodName);
            $types = $this->getReturnType($method);
            $returnValue = $this->getReturnValue($types, $method, $methodItem);
            $paramList = $this->generateMethodSignature($method);
            $returnType = ($types) ? ': ' . implode('|', $types) : '';
            $modifiersArr = Reflection::getModifierNames($method->getModifiers());
            $modifiers = implode(" ", $modifiersArr);

            $return = ($methodItem && $methodItem->hasReturn()) ? $methodItem->return : eval($returnValue);
            $arr = $this->getMethodInfoAsArray($method);
            $arr['mocker'] = $mockClassName;
            $arr['return'] = $return;

            $info = json_encode($arr);
            MockerController::getInstance()->buildMethodData($info);

            if($methodItem && !in_array("void", $types)) {
                $returnValue = $this->generateWrapperReturn($methodItem->getWrap(), $methodName, $returnValue);
            }

            $overrides .= "
                {$modifiers} function {$methodName}({$paramList}){$returnType}
                {
                    \$obj = \\MaplePHP\\Unitary\\Mocker\\MockerController::getInstance()->buildMethodData('{$info}');
                    \$data = \\MaplePHP\\Unitary\\Mocker\\MockerController::getDataItem(\$obj->mocker, \$obj->name);
                    {$returnValue}
                }
                ";
        }

        return $overrides;
    }


    protected function generateWrapperReturn(?\Closure $wrapper, string $methodName, string $returnValue) {
        MockerController::addData($this->mockClassName, $methodName, 'wrapper', $wrapper);
        return "
            if (isset(\$data->wrapper) && \$data->wrapper instanceof \\Closure) {
                return call_user_func_array(\$data->wrapper, func_get_args());
            }
            {$returnValue}
            ";
    }

    /**
     * Generates the signature for a method, including type hints, default values, and by-reference indicators.
     *
     * @param ReflectionMethod $method The reflection object for the method to analyze.
     * @return string The generated method signature.
     */
    protected function generateMethodSignature(ReflectionMethod $method): string
    {
        $params = [];
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
        return implode(', ', $params);
    }

    /**
     * Determines and retrieves the expected return types of a given method.
     *
     * @param ReflectionMethod $method The reflection object for the method to inspect.
     * @return array An array of the expected return types for the given method.
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

        if(!in_array("mixed", $types) && $returnType && $returnType->allowsNull()) {
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
    protected function getMockValueForType(string $typeName, mixed $method, mixed $value = null, bool $nullable = false): mixed
    {
        $typeName = strtolower($typeName);
        if(!is_null($value)) {
            return "return " . var_export($value, true) . ";";
        }

        $mock = match ($typeName) {
            'int' => "return 123456;",
            'integer' => "return 123456;",
            'float' => "return 3.14;",
            'double' => "return 3.14;",
            'string' => "return 'mockString';",
            'bool' => "return true;",
            'boolean' => "return true;",
            'array' => "return ['item'];",
            'object' => "return (object)['item'];",
            'resource' => "return fopen('php://memory', 'r+');",
            'callable' => "return fn() => 'called';",
            'iterable' => "return new ArrayIterator(['a', 'b']);",
            'null' => "return null;",
            'void' => "",
            'self' => ($method->isStatic()) ? 'return new self();' :  'return $this;',
            default => (is_string($typeName) && class_exists($typeName))
                ? "return new class() extends " . $typeName . " {};"
                : "return null;",

        };
        return $nullable && rand(0, 1) ? null : $mock;
    }

    /**
     * Will return a streamable content
     * 
     * @param $resourceValue
     * @return string|null
     */
    protected function handleResourceContent($resourceValue): ?string
    {
        return var_export(stream_get_contents($resourceValue), true);
    }

    /**
     * Build a method information array form ReflectionMethod instance
     *
     * @param ReflectionMethod $refMethod
     * @return array
     */
    function getMethodInfoAsArray(ReflectionMethod $refMethod): array
    {
        $params = [];
        foreach ($refMethod->getParameters() as $param) {
            $params[] = [
                'name'        => $param->getName(),
                'position'    => $param->getPosition(),
                'hasType'     => $param->hasType(),
                'type'        => $param->hasType() ? $param->getType()->__toString() : null,
                'isOptional'  => $param->isOptional(),
                'isVariadic'  => $param->isVariadic(),
                'isPassedByReference' => $param->isPassedByReference(),
                'default'     => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
            ];
        }

        return [
            'class'              => $refMethod->getDeclaringClass()->getName(),
            'name'               => $refMethod->getName(),
            'isStatic'           => $refMethod->isStatic(),
            'isPublic'           => $refMethod->isPublic(),
            'isPrivate'          => $refMethod->isPrivate(),
            'isProtected'        => $refMethod->isProtected(),
            'isAbstract'         => $refMethod->isAbstract(),
            'isFinal'            => $refMethod->isFinal(),
            'returnsReference'   => $refMethod->returnsReference(),
            'hasReturnType'      => $refMethod->hasReturnType(),
            'returnType'         => $refMethod->hasReturnType() ? $refMethod->getReturnType()->__toString() : null,
            'isConstructor'      => $refMethod->isConstructor(),
            'isDestructor'       => $refMethod->isDestructor(),
            'parameters'         => $params,
            'hasDocComment'      => $refMethod->getDocComment(),
            'startLine'          => $refMethod->getStartLine(),
            'endLine'            => $refMethod->getEndLine(),
            'fileName'           => $refMethod->getFileName(),
        ];
    }

}