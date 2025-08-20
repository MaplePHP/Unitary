<?php

/**
 * MockBuilder — Part of the MaplePHP Unitary Testing Library
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */
declare(strict_types=1);

namespace MaplePHP\Unitary\Mocker;

use Closure;
use Exception;
use MaplePHP\Unitary\Support\TestUtils\DataTypeMock;
use Reflection;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use RuntimeException;
use Throwable;

final class MockBuilder
{
    protected ReflectionClass $reflection;
    protected string $className;
    /** @var class-string|null */
    protected string $mockClassName;
    protected string $copyClassName;
    /** @var array<array-key, mixed> */
    protected array $constructorArgs = [];
    protected array $methods;
    protected array $methodList = [];
    protected array $isFinal = [];
    private DataTypeMock $dataTypeMock;

    /**
     * @param string $className
     * @param array $args
     */
    public function __construct(string $className, array $args = [])
    {
        $this->className = $className;
        /** @var class-string $className */
        $this->reflection = new ReflectionClass($className);
        $this->dataTypeMock = new DataTypeMock();
        $this->methods = $this->reflection->getMethods();
        $this->constructorArgs = $args;
        $shortClassName = explode("\\", $className);
        $shortClassName = end($shortClassName);
        /**
         * @var class-string $shortClassName
         * @psalm-suppress PropertyTypeCoercion
         */
        $this->mockClassName = "Unitary_" . uniqid() . "_Mock_" . $shortClassName;
        $this->copyClassName = "Unitary_Mock_" . $shortClassName;
    }

    protected function getMockClass(?MockedMethod $methodItem, callable $call, mixed $fallback = null): mixed
    {
        return ($methodItem instanceof MockedMethod) ? $call($methodItem) : $fallback;
    }

    /**
     * Adds metadata to the mock method, including the mock class name, return value.
     * This is possible custom-added data that "has to" validate against the MockedMethod instance
     *
     * @param array $data The base data array to add metadata to
     * @param string $mockClassName The name of the mock class
     * @param mixed $returnValue
     * @param mixed $methodItem
     * @return array The data array with added metadata
     */
    protected function addMockMetadata(array $data, string $mockClassName, mixed $returnValue, ?MockedMethod $methodItem): array
    {
        $data['mocker'] = $mockClassName;
        $data['return'] = ($methodItem instanceof MockedMethod && $methodItem->hasReturn()) ? $methodItem->return : eval($returnValue);
        $data['keepOriginal'] = ($methodItem instanceof MockedMethod && $methodItem->keepOriginal) ? $methodItem->keepOriginal : false;
        $data['throwOnce'] = ($methodItem instanceof MockedMethod && $methodItem->throwOnce) ? $methodItem->throwOnce : false;
        return $data;
    }

    /**
     * Get reflection of the expected class
     * @return ReflectionClass
     */
    public function getReflectionClass(): ReflectionClass
    {
        return $this->reflection;
    }

    /**
     * Gets the fully qualified name of the class being mocked.
     *
     * @return string The class name that was provided during instantiation
     */
    public function getClassName(): string
    {
        return $this->className;
    }


    /**
     * Returns the constructor arguments provided during instantiation.
     *
     * @return array The array of constructor arguments used to create the mock instance
     */
    public function getClassArgs(): array
    {
        return $this->constructorArgs;
    }

    /**
     * Gets the mock class name generated during mock creation.
     * This method should only be called after execute() has been invoked.
     *
     * @return string The generated mock class name
     */
    public function getMockedClassName(): string
    {
        return (string)$this->mockClassName;
    }

    /**
     * Return all final methods
     *
     * @return array
     */
    public function getFinalMethods(): array
    {
        return $this->isFinal;
    }

    /**
     * Gets the list of methods that are mocked.
     *
     * @return bool
     */
    public function hasFinal(): bool
    {
        return $this->isFinal !== [];
    }

    /**
     * Sets a custom mock value for a specific data type. The mock value can be bound to a specific method
     * or used as a global default for the data type.
     *
     * @param string $dataType The data type to mock (e.g., 'int', 'string', 'bool')
     * @param mixed $value The value to use when mocking this data type
     * @param string|null $bindToMethod Optional method name to bind this mock value to
     * @return self Returns the current instance for method chaining
     */
    public function mockDataType(string $dataType, mixed $value, ?string $bindToMethod = null): self
    {
        if ($bindToMethod) {
            $this->dataTypeMock = $this->dataTypeMock->withCustomBoundDefault($bindToMethod, $dataType, $value);
        } else {
            $this->dataTypeMock = $this->dataTypeMock->withCustomDefault($dataType, $value);
        }
        return $this;
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
        $overrides = $this->generateMockMethodOverrides((string)$this->mockClassName);
        $unknownMethod = $this->errorHandleUnknownMethod($className, !$this->reflection->isInterface());
        $extends = $this->reflection->isInterface() ? "implements $className" : "extends $className";

        $code = "
            class $this->mockClassName $extends {
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
    private function errorHandleUnknownMethod(string $className, bool $checkOriginal = true): string
    {
        if (!in_array('__call', $this->methodList)) {

            $checkOriginalCall = $checkOriginal ? "
                if (method_exists(get_parent_class(\$this), '__call')) {
                        return parent::__call(\$name, \$arguments);
                    }
                " : "";

            return "
                public function __call(string \$name, array \$arguments) {
                    {$checkOriginalCall}
                    throw new \\BadMethodCallException(\"Method '\$name' does not exist in class '$className'.\");
                }
            ";
        }
        return "";
    }

    /**
     * @param array $types
     * @param mixed $method
     * @param MockedMethod|null $methodItem
     * @return string
     */
    protected function getReturnValue(array $types, mixed $method, ?MockedMethod $methodItem = null): string
    {
        // Will overwrite the auto generated value
        if ($methodItem && $methodItem->hasReturn()) {
            return "  
            \$returnData = " . var_export($methodItem->return, true) . ";
            return \$returnData[\$data->called-1] ?? \$returnData[0];
            ";
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

            $methodName = $method->getName();
            if ($method->isFinal()) {
                $this->isFinal[] = $methodName;
                continue;
            }
            $this->methodList[] = $methodName;

            // The MethodItem contains all items that are validatable
            $methodItem = MethodRegistry::getMethod($this->getMockedClassName(), $methodName);

            $types = $this->getReturnType($method);
            $returnValue = $this->getReturnValue($types, $method, $methodItem);
            $paramList = $this->generateMethodSignature($method);

            if ($method->isConstructor()) {
                $types = [];
                $returnValue = "";
                if (count($this->constructorArgs) === 0) {
                    $paramList = "";
                }
            }
            $returnType = ($types) ? ': ' . implode('|', $types) : '';
            $modifiersArr = Reflection::getModifierNames($method->getModifiers());
            $modifiers = $this->handleModifiers($modifiersArr);

            $arr = $this->getMethodInfoAsArray($method);
            $arr = $this->addMockMetadata($arr, $mockClassName, $returnValue, $methodItem);

            $info = json_encode($arr);
            if ($info === false) {
                throw new RuntimeException('JSON encoding failed: ' . json_last_error_msg(), json_last_error());
            }

            MockController::getInstance()->buildMethodData($info);
            if ($methodItem) {
                $returnValue = $this->generateWrapperReturn($methodItem->getWrap(), $methodName, $returnValue);
            }

            if ($methodItem && $methodItem->keepOriginal) {
                $returnValue = "parent::$methodName(...func_get_args());";
                if (!in_array('void', $types)) {
                    $returnValue = "return $returnValue";
                }
            }

            $exception = ($methodItem && $methodItem->getThrowable()) ? $this->handleThrownExceptions($methodItem->getThrowable()) : "";
            $safeJson = base64_encode($info);
            $overrides .= "
                $modifiers function $methodName($paramList){$returnType}
                {
                    \$obj = \\MaplePHP\\Unitary\\Mocker\\MockController::getInstance()->buildMethodData('$safeJson', func_get_args(), true);
                    \$data = \\MaplePHP\\Unitary\\Mocker\\MockController::getDataItem(\$obj->mocker, \$obj->name);
                    
                    if(\$data->throwOnce === false || \$data->called <= 1) {
                        {$exception}
                    }
                    {$returnValue}
                }
                ";
        }
        return $overrides;
    }

    /**
     * Will handle modifier correctly
     *
     * @param array $modifiersArr
     * @return string
     */
    protected function handleModifiers(array $modifiersArr): string
    {
        $modifiersArr = array_filter($modifiersArr, fn ($val) => $val !==  "abstract");
        return implode(" ", $modifiersArr);
    }

    /**
     * Will mocked a handle the thrown exception
     *
     * @param Throwable $exception
     * @return string
     */
    protected function handleThrownExceptions(Throwable $exception): string
    {
        $class = get_class($exception);
        $reflection = new ReflectionClass($exception);
        $constructor = $reflection->getConstructor();
        $args = [];
        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                $name = $param->getName();
                $value = $exception->{$name} ?? null;
                switch ($name) {
                    case 'message':
                        $value = $exception->getMessage();
                        break;
                    case 'code':
                        $value = $exception->getCode();
                        break;
                    case 'previous':
                        $value = null;
                        break;
                }
                $args[] = var_export($value, true);
            }
        }

        return "throw new \\$class(" . implode(', ', $args) . ");";
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
        MockController::addData((string)$this->mockClassName, $methodName, 'wrapper', $wrapper);
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
                fn (ReflectionNamedType $type) => $type->getName(),
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
        if ($value !== null) {
            return "return " . DataTypeMock::exportValue($value) . ";";
        }

        $methodName = ($method instanceof ReflectionMethod) ? $method->getName() : null;

        $mock = match ($dataTypeName) {
            'int', 'integer' => "return " . $this->dataTypeMock->getDataTypeValue('int', $methodName) . ";",
            'float', 'double' => "return " . $this->dataTypeMock->getDataTypeValue('float', $methodName) . ";",
            'string' => "return " . $this->dataTypeMock->getDataTypeValue('string', $methodName) . ";",
            'bool', 'boolean' => "return " . $this->dataTypeMock->getDataTypeValue('bool', $methodName) . ";",
            'array' => "return " . $this->dataTypeMock->getDataTypeValue('array', $methodName) . ";",
            'object' => "return " . $this->dataTypeMock->getDataTypeValue('object', $methodName) . ";",
            'resource' => "return " . $this->dataTypeMock->getDataTypeValue('resource', $methodName) . ";",
            'callable' => "return " . $this->dataTypeMock->getDataTypeValue('callable', $methodName) . ";",
            'iterable' => "return " . $this->dataTypeMock->getDataTypeValue('iterable', $methodName) . ";",
            'null' => "return " . $this->dataTypeMock->getDataTypeValue('null', $methodName) . ";",
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
