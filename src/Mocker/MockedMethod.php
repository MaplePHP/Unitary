<?php

/**
 * MockedMethod — Part of the MaplePHP Unitary Testing Library
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */
declare(strict_types=1);

namespace MaplePHP\Unitary\Mocker;

use BadMethodCallException;
use Closure;
use Exception;
use InvalidArgumentException;
use MaplePHP\Unitary\Support\TestUtils\ExecutionWrapper;
use Throwable;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
final class MockedMethod
{
    private ?MockBuilder $mocker;
    private ?Throwable $throwable = null;

    public mixed $return = null;
    public array $throw = [];
    public int|array|null $called = null;

    public ?string $class = null;
    public ?string $name = null;
    public array $arguments = [];
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
    public bool $throwOnce = false;
    protected bool $hasReturn = false;
    protected ?Closure $wrapper = null;


    public function __construct(?MockBuilder $mocker = null)
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
     * @throws Exception
     */
    public function wrap(Closure $call): self
    {
        if ($this->mocker === null) {
            throw new BadMethodCallException('Mocker is not set. Use the method "mock" to set the mocker.');
        }

        if ($this->mocker->getReflectionClass()->isInterface()) {
            throw new BadMethodCallException('You only use "wrap()" on regular classes and not "interfaces".');
        }

        $inst = $this;
        $wrap = new class ($this->mocker->getClassName(), $this->mocker->getClassArgs()) extends ExecutionWrapper {
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
     * Get the throwable if added as Throwable
     *
     * @return Throwable|null
     */
    public function getThrowable(): ?Throwable
    {
        return $this->throwable;
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
     * Validates arguments for the first called method
     *
     * @example method('addEmail')->withArguments('john.doe@gmail.com', 'John Doe')
     * @param mixed ...$args
     * @return $this
     */
    public function withArguments(mixed ...$args): self
    {
        foreach ($args as $key => $value) {
            $this->withArgumentAt($key, $value);
        }
        return $this;
    }

    /**
     * Validates arguments for multiple method calls with different argument sets
     *
     * @example method('addEmail')->withArguments(
     *              ['john.doe@gmail.com', 'John Doe'], ['jane.doe@gmail.com', 'Jane Doe']
     *          )
     * @param mixed ...$args
     * @return $this
     */
    public function withArgumentsForCalls(mixed ...$args): self
    {
        $inst = $this;
        foreach ($args as $called => $data) {
            if (!is_array($data)) {
                throw new InvalidArgumentException(
                    'The argument must be a array that contains the expected method arguments.'
                );
            }
            foreach ($data as $key => $value) {
                $inst = $inst->withArgumentAt($key, $value, $called);
            }
        }
        return $inst;
    }

    /**
     * This will validate an argument at position
     *
     * @param int $called
     * @param int $position
     * @param mixed $value
     * @return $this
     */
    public function withArgumentAt(int $position, mixed $value, int $called = 0): self
    {
        $inst = $this;
        $inst->arguments[] = [
            "validateInData" => ["$called.$position", "equal", [$value]],
        ];
        return $inst;
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
     * Check if a return value has been added
     *
     * @return bool
     */
    public function hasReturn(): bool
    {
        return $this->hasReturn;
    }

    /**
     * Change what the method should return
     *
     * @param mixed $value
     * @return $this
     */
    public function willReturn(mixed ...$value): self
    {
        $inst = $this;
        $inst->hasReturn = true;
        $inst->return = $value;
        return $inst;
    }

    /**
     * Configures the method to throw an exception every time it's called
     *
     * @param Throwable $throwable
     * @return $this
     */
    public function willThrow(Throwable $throwable): self
    {
        $this->throwable = $throwable;
        $this->throw = [];
        return $this;
    }

    /**
     * Configures the method to throw an exception only once
     *
     * @param Throwable $throwable
     * @return $this
     */
    public function willThrowOnce(Throwable $throwable): self
    {
        $this->throwOnce = true;
        $this->willThrow($throwable);
        return $this;
    }

    /**
     * Compare if method has expected class name.
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
     * Compare if method has expected method name.
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
     * Check if the method is expected to be static
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
     * Check if the method is expected to be public
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
     * Check if the method is expected to be private
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
     * Check if the method is expected to be protected.
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
     * Check if the method is expected to be abstract.
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
     * Check if the method is expected to be final.
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
     * Check if the method is expected to return a reference
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
     * Check if the method has a return type.
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
     * Check if the method return type has expected type
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
     * Check if the method is the constructor.
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
     * Check if the method is the destructor.
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
     * Check if the method parameters exists
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
     * Check if the method has parameter types
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
     * Check if the method is missing parameters
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
     * Check if the method has equal number of parameters as expected
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
     * Check if the method parameter at given index location has expected data type
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
     * Check if the method parameter at given index location has a default value
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
     * Check if the method parameter at given index location has a data type
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
     * Check if the method parameter at given index location is optional
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
     * Check if the method parameter at given index location is a reference
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
     * Check if the method parameter at given index location is a variadic (spread)
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
     * Check if the method has comment block
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
     * Check if the method exist in file with name
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
     * Check if the method starts at line number
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
     * Check if the method return ends at line number
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
