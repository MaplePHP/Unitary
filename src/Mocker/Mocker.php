<?php

/**
 * @Package:    MaplePHP - Lightweight test mocker
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */

namespace MaplePHP\Unitary\Mocker;

use Closure;
use Exception;
use Reflection;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use RuntimeException;

final class Mocker
{
    //protected object $instance;
    //protected array $overrides = [];
    protected ReflectionClass $reflection;
    protected string $className;
    /** @var class-string|null */
    protected ?string $mockClassName = null;
    /** @var array<array-key, mixed> */
    protected array $constructorArgs = [];
    protected array $methods;
    protected array $methodList = [];
    protected static ?MethodPool $methodPool = null;

    /**
     * @param string $className
     * @param array $args
     */
    public function __construct(string $className, array $args = [])
    {
        $this->className = $className;
        /** @var class-string $className */
        $this->reflection = new ReflectionClass($className);

        /*
        // Auto fill the Constructor args!
        $test = $this->reflection->getConstructor();
        $test = $this->generateMethodSignature($test);
        $param = $test->getParameters();
         */

        $this->methods = $this->reflection->getMethods();
        $this->constructorArgs = $args;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getClassArgs(): array
    {
        return $this->constructorArgs;
    }

    /**
     * Override the default method overrides with your own mock logic and validation rules
     *
     * @return MethodPool
     */
    public function getMethodPool(): MethodPool
    {
        if (is_null(self::$methodPool)) {
            self::$methodPool = new MethodPool($this);
        }
        return self::$methodPool;
    }

    /**
     * @throws Exception
     */
    public function getMockedClassName(): string
    {
        if (!$this->mockClassName) {
            throw new Exception("Mock class name is not set");
        }
        return $this->mockClassName;
    }

    /**
     * Executes the creation of a dynamic mock class and returns an instance of the mock.
     *
     * @return mixed An instance of the dynamically created mock class.
     * @throws Exception
     */
    public function execute(): mixed
    {
        $className = $this->reflection->getName();

        $shortClassName = explode("\\", $className);
        $shortClassName = end($shortClassName);

        /**
         * @var class-string $shortClassName
         * @psalm-suppress PropertyTypeCoercion
         */
        $this->mockClassName = 'Unitary_' . uniqid() . "_Mock_" . $shortClassName;
        $overrides = $this->generateMockMethodOverrides($this->mockClassName);
        $unknownMethod = $this->errorHandleUnknownMethod($className);

        $code = "
            class $this->mockClassName extends $className {
                {$overrides}
                {$unknownMethod}
                public static function __set_state(array \$an_array): self
                {
                    \$obj = new self(..." . var_export($this->constructorArgs, true) . ");
                    return \$obj;
                }
            }
        ";

        eval($code);


        /**
         * @psalm-suppress MixedMethodCall
         * @psalm-suppress InvalidStringClass
         */
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
        if (!in_array('__call', $this->methodList)) {
            return "
                public function __call(string \$name, array \$arguments) {
                    if (method_exists(get_parent_class(\$this), '__call')) {
                        return parent::__call(\$name, \$arguments);
                    }
                    throw new \\BadMethodCallException(\"Method '\$name' does not exist in class '$className'.\");
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
        if ($methodItem && $methodItem->hasReturn()) {
            return "return " . var_export($methodItem->return, true) . ";";
        }
        if ($types) {
            return (string)$this->getMockValueForType((string)$types[0], $method);
        }
        return "return 'MockedValue';";
    }

    /**
     * Builds and returns PHP code that overrides all public methods in the class being mocked.
     * Each overridden method returns a predefined mock value or delegates to the original logic.
     *
     * @param string $mockClassName
     * @return string PHP code defining the overridden methods.
     * @throws Exception
     */
    protected function generateMockMethodOverrides(string $mockClassName): string
    {
        $overrides = '';
        foreach ($this->methods as $method) {

            if (!($method instanceof ReflectionMethod)) {
                throw new Exception("Method is not a ReflectionMethod");
            }

            if ($method->isFinal()) {
                continue;
            }

            $methodName = $method->getName();
            $this->methodList[] = $methodName;

            // The MethodItem contains all items that are validatable
            $methodItem = $this->getMethodPool()->get($methodName);
            $types = $this->getReturnType($method);
            $returnValue = $this->getReturnValue($types, $method, $methodItem);
            $paramList = $this->generateMethodSignature($method);
            if($method->isConstructor()) {
                $types = [];
                $returnValue = "";
                if(count($this->constructorArgs) === 0) {
                    $paramList = "";
                }
            }
            $returnType = ($types) ? ': ' . implode('|', $types) : '';
            $modifiersArr = Reflection::getModifierNames($method->getModifiers());
            $modifiers = implode(" ", $modifiersArr);

            $return = ($methodItem && $methodItem->hasReturn()) ? $methodItem->return : eval($returnValue);
            $arr = $this->getMethodInfoAsArray($method);
            $arr['mocker'] = $mockClassName;
            $arr['return'] = $return;

            $info = json_encode($arr);
            if ($info === false) {
                throw new RuntimeException('JSON encoding failed: ' . json_last_error_msg(), json_last_error());
            }
            MockerController::getInstance()->buildMethodData($info);

            if ($methodItem) {
                $returnValue = $this->generateWrapperReturn($methodItem->getWrap(), $methodName, $returnValue);
            }
            $safeJson = base64_encode($info);
            $overrides .= "
                $modifiers function $methodName($paramList){$returnType}
                {
                    \$obj = \\MaplePHP\\Unitary\\Mocker\\MockerController::getInstance()->buildMethodData('$safeJson', true);
                    \$data = \\MaplePHP\\Unitary\\Mocker\\MockerController::getDataItem(\$obj->mocker, \$obj->name);
                    {$returnValue}
                }
                ";
        }

        return $overrides;
    }


    /**
     * Will build the wrapper return
     *
     * @param Closure|null $wrapper
     * @param string $methodName
     * @param string $returnValue
     * @return string
     */
    protected function generateWrapperReturn(?Closure $wrapper, string $methodName, string $returnValue): string
    {
        MockerController::addData((string)$this->mockClassName, $methodName, 'wrapper', $wrapper);
        $return = ($returnValue) ? "return " : "";
        return "
            if (isset(\$data->wrapper) && \$data->wrapper instanceof \\Closure) {
                {$return}call_user_func_array(\$data->wrapper, func_get_args());
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
                $getType = (string)$param->getType();
                $paramStr .= $getType . ' ';
            }
            if ($param->isPassedByReference()) {
                $paramStr .= '&';
            }
            $paramStr .= '$' . $param->getName();
            if ($param->isDefaultValueAvailable()) {
                $paramStr .= ' = ' . var_export($param->getDefaultValue(), true);
            }

            if ($param->isVariadic()) {
                $paramStr = "...$paramStr";
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
    protected function getReturnType(ReflectionMethod $method): array
    {
        $types = [];
        $returnType = $method->getReturnType();
        if ($returnType instanceof ReflectionNamedType) {
            $types[] = $returnType->getName();
        } elseif ($returnType instanceof ReflectionUnionType) {
            foreach ($returnType->getTypes() as $type) {
                if (method_exists($type, "getName")) {
                    $types[] = $type->getName();
                }
            }

        } elseif ($returnType instanceof ReflectionIntersectionType) {
            $intersect = array_map(
                fn ($type) => $type->getName(),
                $returnType->getTypes()
            );
            $types[] = $intersect;
        }

        if (!in_array("mixed", $types) && $returnType && $returnType->allowsNull()) {
            $types[] = "null";
        }
        return array_unique($types);
    }

    /**
     * Generates a mock value for the specified type.
     *
     * @param string $typeName The name of the type for which to generate the mock value.
     * @param bool $nullable Indicates if the returned value can be nullable.
     * @return string|null Returns a mock value corresponding to the given type, or null if nullable and conditions allow.
     */
    protected function getMockValueForType(string $typeName, mixed $method, mixed $value = null, bool $nullable = false): ?string
    {
        $dataTypeName = strtolower($typeName);
        if (!is_null($value)) {
            return "return " . var_export($value, true) . ";";
        }

        $mock = match ($dataTypeName) {
            'int', 'integer' => "return 123456;",
            'float', 'double' => "return 3.14;",
            'string' => "return 'mockString';",
            'bool', 'boolean' => "return true;",
            'array' => "return ['item'];",
            'object' => "return (object)['item'];",
            'resource' => "return fopen('php://memory', 'r+');",
            'callable' => "return fn() => 'called';",
            'iterable' => "return new ArrayIterator(['a', 'b']);",
            'null' => "return null;",
            'void' => "",
            'self' => (is_object($method) && method_exists($method, "isStatic") && $method->isStatic()) ? 'return new self();' : 'return $this;',
            /** @var class-string $typeName */
            default => (class_exists($typeName))
                ? "return new class() extends " . $typeName . " {};"
                : "return null;",

        };
        return $nullable && rand(0, 1) ? null : $mock;
    }

    /**
     * Will return a streamable content
     *
     * @param mixed $resourceValue
     * @return string|null
     */
    protected function handleResourceContent(mixed $resourceValue): ?string
    {
        if (!is_resource($resourceValue)) {
            return null;
        }
        return var_export(stream_get_contents($resourceValue), true);
    }

    /**
     * Build a method information array from a ReflectionMethod instance
     *
     * @param ReflectionMethod $refMethod
     * @return array
     */
    public function getMethodInfoAsArray(ReflectionMethod $refMethod): array
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
                'isReference' => $param->isPassedByReference(),
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
