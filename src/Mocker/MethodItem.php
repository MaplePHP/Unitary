<?php

namespace MaplePHP\Unitary\Mocker;

use BadMethodCallException;
use Closure;
use MaplePHP\Unitary\TestWrapper;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
final class MethodItem
{
    private ?Mocker $mocker;
    public mixed $return = null;
    public int|array|null $called = null;

    public ?string $class = null;
    public ?string $name = null;
    public ?bool $isStatic = null;
    public ?bool $isPublic = null;
    public ?bool $isPrivate = null;
    public ?bool $isProtected = null;
    public ?bool $isAbstract = null;
    public ?bool $isFinal = null;
    public ?bool $returnsReference = null;
    public ?bool $hasReturnType = null;
    public ?string $returnType = null;
    public ?bool $isConstructor = null;
    public ?bool $isDestructor = null;
    public ?array $parameters = null;
    public ?array $hasDocComment = null;
    public ?int $startLine = null;
    public ?int $endLine = null;
    public ?string $fileName = null;
    public bool $keepOriginal = false;
    protected bool $hasReturn = false;
    protected ?Closure $wrapper = null;

    public function __construct(?Mocker $mocker = null)
    {
        $this->mocker = $mocker;
    }

    /**
     * Creates a proxy wrapper around a method to enable integration testing.
     * The wrapper allows intercepting and modifying method behavior during tests.
     *
     * @param Closure $call The closure to be executed as the wrapper function
     * @return $this Method chain
     * @throws BadMethodCallException When mocker is not set
     */
    public function wrap(Closure $call): self
    {
        if (is_null($this->mocker)) {
            throw new BadMethodCallException('Mocker is not set. Use the method "mock" to set the mocker.');
        }

        $inst = $this;
        $wrap = new class ($this->mocker->getClassName(), $this->mocker->getClassArgs()) extends TestWrapper {
            public function __construct(string $class, array $args = [])
            {
                parent::__construct($class, $args);
            }
        };
        $this->wrapper = $wrap->bind($call);
        return $inst;
    }

    /**
     * Get the wrapper if added as Closure else null
     *
     * @return Closure|null
     */
    public function getWrap(): ?Closure
    {
        return $this->wrapper;
    }

    /**
     * Check if a return value has been added
     *
     * @return bool
     */
    public function hasReturn(): bool
    {
        return $this->hasReturn;
    }

    /**
     * Preserve the original method functionality instead of mocking it.
     * When this is set, the method will execute its original implementation instead of any mock behavior.
     *
     * @return $this Method chain
     */
    public function keepOriginal(): self
    {
        $inst = $this;
        $inst->keepOriginal = true;
        return $inst;
    }

    /**
     * Check if a method has been called x times
     * 
     * @param int $times
     * @return $this
     */
    public function called(int $times): self
    {
        $inst = $this;
        $inst->called = $times;
        return $inst;
    }

    /**
     * Check if a method has been called x times
     * 
     * @return $this
     */
    public function hasBeenCalled(): self
    {
        $inst = $this;
        $inst->called = [
            "isAtLeast" => [1],
        ];
        return $inst;
    }

    /**
     * Check if a method has been called x times
     *
     * @param int $times
     * @return $this
     */
    public function calledAtLeast(int $times): self
    {
        $inst = $this;
        $inst->called = [
            "isAtLeast" => [$times],
        ];
        return $inst;
    }

    /**
     * Check if a method has been called x times
     *
     * @param int $times
     * @return $this
     */
    public function calledAtMost(int $times): self
    {
        $inst = $this;
        $inst->called = [
            "isAtMost" => [$times],
        ];
        return $inst;
    }

    /**
     * Change what the method should return
     *
     * @param mixed $value
     * @return $this
     */
    public function willReturn(mixed $value): self
    {
        $inst = $this;
        $inst->hasReturn = true;
        $inst->return = $value;
        return $inst;
    }

    /**
     * Set the class name.
     *
     * @param string $class
     * @return self
     */
    public function hasClass(string $class): self
    {
        $inst = $this;
        $inst->class = $class;
        return $inst;
    }

    /**
     * Set the method name.
     *
     * @param string $name
     * @return self
     */
    public function hasName(string $name): self
    {
        $inst = $this;
        $inst->name = $name;
        return $inst;
    }

    /**
     * Mark the method as static.
     *
     * @return self
     */
    public function isStatic(): self
    {
        $inst = $this;
        $inst->isStatic = true;
        return $inst;
    }

    /**
     * Mark the method as public.
     *
     * @return self
     */
    public function isPublic(): self
    {
        $inst = $this;
        $inst->isPublic = true;
        return $inst;
    }

    /**
     * Mark the method as private.
     *
     * @return self
     */
    public function isPrivate(): self
    {
        $inst = $this;
        $inst->isPrivate = true;
        return $inst;
    }

    /**
     * Mark the method as protected.
     *
     * @return self
     */
    public function isProtected(): self
    {
        $inst = $this;
        $inst->isProtected = true;
        return $inst;
    }

    /**
     * Mark the method as abstract.
     *
     * @return self
     */
    public function isAbstract(): self
    {
        $inst = $this;
        $inst->isAbstract = true;
        return $inst;
    }

    /**
     * Mark the method as final.
     *
     * @return self
     */
    public function isFinal(): self
    {
        $inst = $this;
        $inst->isFinal = true;
        return $inst;
    }

    /**
     * Mark the method as returning by reference.
     *
     * @return self
     */
    public function returnsReference(): self
    {
        $inst = $this;
        $inst->returnsReference = true;
        return $inst;
    }

    /**
     * Mark the method as having a return type.
     *
     * @return self
     */
    public function hasReturnType(): self
    {
        $inst = $this;
        $inst->hasReturnType = true;
        return $inst;
    }

    /**
     * Set the return type of the method.
     *
     * @param string $type
     * @return self
     */
    public function isReturnType(string $type): self
    {
        $inst = $this;
        $inst->returnType = $type;
        return $inst;
    }

    /**
     * Mark the method as a constructor.
     *
     * @return self
     */
    public function isConstructor(): self
    {
        $inst = $this;
        $inst->isConstructor = true;
        return $inst;
    }

    /**
     * Mark the method as a destructor.
     *
     * @return self
     */
    public function isDestructor(): self
    {
        $inst = $this;
        $inst->isDestructor = true;
        return $inst;
    }

    /**
     * Check if parameter exists
     *
     * @return $this
     */
    public function hasParams(): self
    {
        $inst = $this;
        $inst->parameters[] = [
            "isCountMoreThan" => [0],
        ];
        return $inst;
    }

    /**
     * Check if all parameters have a data type
     *
     * @return $this
     */
    public function hasParamsTypes(): self
    {
        $inst = $this;
        $inst->parameters[] = [
            "itemsAreTruthy" => ['hasType', true],
        ];
        return $inst;
    }

    /**
     * Check if parameter does not exist
     *
     * @return $this
     */
    public function hasNotParams(): self
    {
        $inst = $this;
        $inst->parameters[] = [
            "isArrayEmpty" => [],
        ];
        return $inst;
    }

    /**
     * Check a parameter type for method
     *
     * @param int $length
     * @return $this
     */
    public function paramsHasCount(int $length): self
    {
        $inst = $this;
        $inst->parameters[] = [
            "isCountEqualTo" => [$length],
        ];
        return $inst;
    }

    /**
     * Check a parameter type for method
     *
     * @param int $paramPosition
     * @param string $dataType
     * @return $this
     */
    public function paramIsType(int $paramPosition, string $dataType): self
    {
        $inst = $this;
        $inst->parameters[] = [
            "validateInData" => ["$paramPosition.type", "equal", [$dataType]],
        ];
        return $inst;
    }

    /**
     * Check parameter default value for method
     *
     * @param int $paramPosition
     * @param string $defaultArgValue
     * @return $this
     */
    public function paramHasDefault(int $paramPosition, string $defaultArgValue): self
    {
        $inst = $this;
        $inst->parameters[] = [
            "validateInData" => ["$paramPosition.default", "equal", [$defaultArgValue]],
        ];
        return $inst;
    }

    /**
     * Check a parameter type for method
     *
     * @param int $paramPosition
     * @return $this
     */
    public function paramHasType(int $paramPosition): self
    {
        $inst = $this;
        $inst->parameters[] = [
            "validateInData" => ["$paramPosition.hasType", "equal", [true]],
        ];
        return $inst;
    }

    /**
     * Check a parameter type for method
     *
     * @param int $paramPosition
     * @return $this
     */
    public function paramIsOptional(int $paramPosition): self
    {
        $inst = $this;
        $inst->parameters[] = [
            "validateInData" => ["$paramPosition.isOptional", "equal", [true]],
        ];
        return $inst;
    }

    /**
     * Check parameter is Reference for method
     *
     * @param int $paramPosition
     * @return $this
     */
    public function paramIsReference(int $paramPosition): self
    {
        $inst = $this;
        $inst->parameters[] = [
            "validateInData" => ["$paramPosition.isReference", "equal", [true]],
        ];
        return $inst;
    }

    /**
     * Check the parameter is variadic (spread) for a method
     *
     * @param int $paramPosition
     * @return $this
     */
    public function paramIsVariadic(int $paramPosition): self
    {
        $inst = $this;
        $inst->parameters[] = [
            "validateInData" => ["$paramPosition.isVariadic", "equal", [true]],
        ];
        return $inst;
    }

    // Symlink to paramIsVariadic
    public function paramIsSpread(int $paramPosition): self
    {
        return $this->paramIsVariadic($paramPosition);
    }

    /**
     * Set the doc comment for the method.
     *
     * @return self
     */
    public function hasDocComment(): self
    {
        $inst = $this;
        $inst->hasDocComment = [
            "isString" => [],
            "startsWith" => ["/**"]
        ];
        return $inst;
    }

    /**
     * Set the file name where the method is declared.
     *
     * @param string $file
     * @return self
     */
    public function hasFileName(string $file): self
    {
        $inst = $this;
        $inst->fileName = $file;
        return $inst;
    }

    /**
     * Set the starting line number of the method.
     *
     * @param int $line
     * @return self
     */
    public function startLine(int $line): self
    {
        $inst = $this;
        $inst->startLine = $line;
        return $inst;
    }

    /**
     * Set the ending line number of the method.
     *
     * @param int $line
     * @return self
     */
    public function endLine(int $line): self
    {
        $inst = $this;
        $inst->endLine = $line;
        return $inst;
    }
}
