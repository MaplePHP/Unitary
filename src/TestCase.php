<?php

/**
 * TestCase — Part of the MaplePHP Unitary Testing Library
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */
declare(strict_types=1);

namespace MaplePHP\Unitary;

use AssertionError;
use BadMethodCallException;
use Closure;
use ErrorException;
use Exception;
use MaplePHP\Blunder\ExceptionItem;
use MaplePHP\Blunder\Exceptions\BlunderErrorException;
use MaplePHP\Blunder\Exceptions\BlunderSilentException;
use MaplePHP\DTO\Format\Str;
use MaplePHP\DTO\Traverse;
use MaplePHP\Unitary\Config\TestConfig;
use MaplePHP\Unitary\Mocker\MethodRegistry;
use MaplePHP\Unitary\Mocker\MockBuilder;
use MaplePHP\Unitary\Mocker\MockController;
use MaplePHP\Unitary\Support\Helpers;
use MaplePHP\Unitary\Support\TestUtils\ExecutionWrapper;
use MaplePHP\Validate\Validator;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Throwable;

/**
 * @template T of object
 */
final class TestCase
{
    /**
     * List of properties to exclude from validation
     * (some properties are not valid for comparison)
     *
     * @var array<string>
     */
    private const EXCLUDE_VALIDATE = ["return"];
    private mixed $value;
    private TestConfig $config;
    private array $test = [];
    private int $count = 0;
    private ?Closure $bind = null;
    private ?string $error = null;
    private ?string $warning = null;
    private bool $assert = false;
    private array $deferredValidation = [];
    private ?MockBuilder $mocker = null;
    private int $hasError = 0;
    private int $skipped = 0;
    private float $duration = 0;
    private float $memory = 0;
    private bool $hasAssertError = false;
    private bool $failFast = false;
    private ?TestUnit $testUnit = null;
    private ?ExceptionItem $throwable = null;

    /**
     * Initialize a new TestCase instance with an optional message.
     *
     * @param TestConfig|string|null $config
     */
    public function __construct(TestConfig|string|null $config = null)
    {
        if (!($config instanceof TestConfig)) {
            $this->config = new TestConfig((string)$config);
        } else {
            $this->config = $config;
        }
    }

    /**
     * Will exit script if errors is thrown
     *
     * @param bool $failFast
     * @return $this
     */
    public function setFailFast(bool $failFast): self
    {
        $this->failFast = $failFast;
        return $this;
    }

    /**
     * Bind the test case to the Closure
     *
     * @param Closure $bind
     * @param bool $bindToClosure choose bind to closure or not (recommended)
     *                            Used primary as a fallback for older versions of Unitary
     * @return void
     */
    public function bind(Closure $bind, bool $bindToClosure = false): void
    {
        $this->bind = ($bindToClosure) ? $bind->bindTo($this) : $bind;
    }


    /**
     * Get the total number of skipped group test
     *
     * @return int
     */
    public function getSkipped(): int
    {
        return $this->skipped;
    }

    /**
     * Get current test group duration
     *
     * @return float
     */
    public function getDuration(): float
    {
        return $this->duration;
    }

    /**
     * Get current test group memory
     *
     * @return float
     */
    public function getMemory(): float
    {
        return $this->memory;
    }

    /**
     * Check if group has any skipped tests
     *
     * @return bool
     */
    public function hasSkipped(): bool
    {
        return $this->skipped > 0;
    }

    /**
     * Increment skipped test
     *
     * @return void
     */
    public function incrementSkipped(): void
    {
        $this->skipped++;
    }

    /**
     * Sets the error flag to true
     *
     * @return void
     */
    public function incrementError(): void
    {
        $this->hasError++;
    }

    /**
     * Gets the errors count
     *
     * @return int
     */
    public function getErrors(): int
    {
        return $this->hasError;
    }

    /**
     * Gets the current state of the error flag
     *
     * @return bool
     */
    public function getHasError(): bool
    {
        return ($this->hasError > 0);
    }

    /**
     * If an error as occurred then you can access the error object through this method
     *
     * @return ExceptionItem|null
     */
    public function getThrowable(): ?ExceptionItem
    {
        return $this->throwable;
    }

    /**
     * Sets the assertion error flag to true
     *
     * @return void
     */
    public function setAsAssert(): void
    {
        $this->hasAssertError = true;
    }

    /**
     * Gets the current state of the assertion error flag
     *
     * @return bool
     */
    public function isAssert(): bool
    {
        return $this->hasAssertError;
    }

    /**
     * Get a possible warning message if exists
     *
     * @return string|null
     */
    public function getWarning(): ?string
    {
        return $this->warning;
    }

    /**
     * Set a possible warning in the test group
     *
     * @param string $message
     * @return $this
     */
    public function warning(string $message): self
    {
        $this->warning = $message;
        return $this;
    }

    public function assert(?string $message = null): self
    {
        $this->describe($message);
        $this->assert = true;
        return $this;
    }

    /**
     * Add custom error message if validation fails
     *
     * @param ?string $message
     * @return $this
     */
    public function describe(?string $message): self
    {
        if ($message !== null) {
            $this->error = $message;
        }
        return $this;
    }

    // Alias to describe
    public function error(?string $message): self
    {
        return $this->describe($message);
    }

    // Alias to describe
    public function message(?string $message): self
    {
        return $this->describe($message);
    }

    /**
     * Will dispatch the case tests and return them as an array
     *
     * @param self $row
     * @return array
     * @throws BlunderErrorException
     * @throws Throwable
     */
    public function dispatchTest(self &$row): array
    {
        $row = $this;
        $test = $this->bind;
        $start = microtime(true);
        $memStart = memory_get_usage();
        $newInst = null;

        if ($test !== null) {
            try {
                if($this->getConfig()->skip) {
                    $this->incrementSkipped();
                }
                $newInst = $test($this);
                //$inst = ($newInst instanceof self) ? $newInst : $this;

            } catch (AssertionError $e) {
                $newInst = $this->createTraceError($e, "Assertion failed");
                $newInst->setAsAssert();

            } catch (Throwable $e) {
                if (str_contains($e->getFile(), "eval()")) {
                    throw new BlunderErrorException($e->getMessage(), (int)$e->getCode());
                }

                if(!($e instanceof BlunderSilentException)) {
                    if($this->failFast) {
                        throw $e;
                    }
                    $newInst = $this->createTraceError($e, trace: [
                        "file" => $e->getFile(),
                        "line" => $e->getLine(),
                    ]);

                    $newInst->incrementError();
                }
            }
            if ($newInst instanceof self) {
                $row = $newInst;
            }
        }

        $this->memory = (float)(memory_get_usage() - $memStart);
        $this->duration = (float)(microtime(true) - $start);

        return $this->test;
    }

    /**
     * Add a test unit validation using the provided expectation and validation logic
     *
     * @param mixed $expect The expected value
     * @param Closure(Expect, Traverse): bool $validation
     * @return TestUnit
     * @throws ErrorException
     */
    public function validate(mixed $expect, Closure $validation): TestUnit
    {
        $this->testUnit = $this->expectAndValidate($expect, function (mixed $value, Expect $inst) use ($validation) {
            return $validation($inst, new Traverse($value));
        }, $this->error);
        return $this->testUnit;
    }

    /**
     * Executes a test case at runtime by validating the expected value.
     *
     * Accepts either a validation array (method => arguments) or a Closure
     * containing multiple inline assertions. If any validation fails, the test
     * is marked as invalid and added to the list of failed tests.
     *
     * @param mixed $expect The value to test.
     * @param array|Closure $validation A list of validation methods with arguments,
     *                                   or a closure defining the test logic.
     * @param string|TestUnit|null $message Optional custom message for test reporting.
     * @param string|null $description
     * @param array|null $trace
     * @return TestUnit
     * @throws ErrorException If validation fails during runtime execution.
     */
    protected function expectAndValidate(
        mixed $expect,
        array|Closure $validation,
        string|TestUnit|null $message = null,
        ?string $description = null,
        ?array $trace = null
    ): TestUnit {
        $listArr = [];
        $this->value = $expect;
        $test = ($message instanceof TestUnit) ? new $message : new TestUnit($message);
        $test->setTestValue($this->value);
        if ($validation instanceof Closure) {
            $validPool = new Expect($this->value);

            try {
                $listArr = $this->buildClosureTest($validation, $validPool, $description);
            } catch (Throwable $e) {
                $test->setValid(false);
                $test->setThrowable(Helpers::getExceptionItem($e));
            }

            foreach ($listArr as $list) {

                if (is_bool($list)) {
                    $item = new TestItem();
                    $item = $item->setIsValid($list)->setValidation("Validation");
                    $test->setTestItem($item);
                } else {
                    foreach ($list as $method => $valid) {
                        $item = new TestItem();
                        /** @var array|bool $valid */
                        $item = $item->setIsValid(false)->setValidation((string)$method);
                        if (is_array($valid)) {
                            $item = $item->setValidationArgs($valid);
                        } else {
                            $item = $item->setHasArgs(false);
                        }
                        $test->setTestItem($item);
                    }
                }
            }
            // In some rare cases the validation value might change along the rode
            // tell the test to use the new value
            $initValue = $validPool->getInitValue();
            $initValue = ($initValue !== null) ? $initValue : $this->getValue();
            $test->setTestValue($initValue);

        } else {
            foreach ($validation as $method => $args) {
                if (!($args instanceof Closure) && !is_array($args)) {
                    $args = [$args];
                }
                $item = new TestItem();
                $item = $item->setIsValid($this->buildArrayTest($method, $args))
                    ->setValidation($method)
                    ->setValidationArgs((is_array($args) ? $args : []));
                $test->setTestItem($item);
            }
        }
        if (!$test->isValid()) {
            if ($trace === null || $trace === []) {
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            }

            $test->setCodeLine($trace);
            $this->count++;
        }
        $this->test[] = $test;
        $this->error = null;
        if($this->assert == true) {
            $test->assert(null);
        }
        return $test;
    }

    /**
     * Will assert a php error
     *
     * @param Throwable $exception
     * @param string|null $title
     * @param array|null $trace
     * @return $this
     * @throws ErrorException
     */
    public function createTraceError(Throwable $exception, ?string $title = null, ?array $trace = null): self
    {
        $newInst = clone $this;
        $message = Helpers::getExceptionMessage($exception, $exceptionItem);
        $title = ($title !== null) ? $title : "PHP " . $exceptionItem->getSeverityTitle();
        $newInst->expectAndValidate(
            true,
            fn () => false,
            $title,
            $message,
            ($trace !== null) ? $trace : $exception->getTrace()[0]
        );

        $newInst->throwable = $exceptionItem;
        return $newInst;
    }

    /**
     * Adds a deferred validation to be executed after all immediate tests.
     *
     * Use this to queue up validations that depend on external factors or should
     * run after the main test suite. These will be executed in the order they were added.
     *
     * @param Closure $validation A closure containing the deferred test logic.
     * @return void
     */
    public function deferValidation(Closure $validation): void
    {
        // This will add a cursor to the possible line and file where the error occurred
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4)[3];
        $this->deferredValidation[] = [
            "trace" => $trace,
            "call" => $validation
        ];
    }

    /**
     * DEPRECTAED: Add test
     *
     * @param mixed $expect The expected value
     * @param array|Closure $validation The validation logic
     * @param string|null $message An optional descriptive message for the test
     * @return $this
     * @throws ErrorException
     */
    public function add(mixed $expect, array|Closure $validation, ?string $message = null): self
    {
        $this->expectAndValidate($expect, $validation, $message);
        return $this;
    }

    /**
     * Defer can be used to clean up group and will be excepted at the end of life
     *
     * @param Closure $defer
     * @return $this
     */
    public function defer(Closure $defer): self
    {
        $this->deferredValidation[] = [
            "trace" => null,
            "call" => $defer
        ];
        return $this;
    }

    /**
     * Initialize a test wrapper
     *
     * NOTICE: When mocking a class with required constructor arguments, those arguments must be
     * specified in the mock initialization method, or it will fail. This is because the mock
     * creates and simulates an actual instance of the original class with its real constructor.
     *
     * @param string $class
     * @param array $args
     * @return ExecutionWrapper
     */
    public function wrap(string $class, array $args = []): ExecutionWrapper
    {
        return new class ($class, $args) extends ExecutionWrapper {
            public function __construct(string $class, array $args = [])
            {
                parent::__construct($class, $args);
            }
        };
    }

    /**
     * @param class-string<T> $class
     * @param array $args
     * @return self<T>
     */
    public function withMock(string $class, array $args = []): self
    {
        $inst = clone $this;
        $inst->mocker = new MockBuilder($class, $args);
        return $inst;
    }

    /**
     * @param Closure|null $validate
     * @return T
     * @throws ErrorException
     * @throws Exception
     */
    public function buildMock(?Closure $validate = null): mixed
    {
        if (!($this->mocker instanceof MockBuilder)) {
            throw new BadMethodCallException("The mocker is not set yet!");
        }
        if ($validate instanceof Closure) {
            $pool = $this->prepareValidation($this->mocker, $validate);
        }
        /** @psalm-suppress MixedReturnStatement */
        $class =  $this->mocker->execute();
        if ($this->mocker->hasFinal() && isset($pool)) {
            $finalMethods = $pool->getSelected($this->mocker->getFinalMethods());
            if ($finalMethods !== []) {
                $this->warning = "Warning: Final methods cannot be mocked or have their behavior modified: " .  implode(", ", $finalMethods);
            }
        }
        return $class;
    }

    /**
     * Creates and returns an instance of a dynamically generated mock class.
     *
     * The mock class is based on the provided class name and optional constructor arguments.
     * A validation closure can also be provided to define mock expectations. These
     * validations are deferred and will be executed later via runDeferredValidations().
     *
     * @param class-string<T> $class
     * @param (Closure(MethodRegistry): void)|null $callback
     * @param array $args
     * @return T
     * @throws Exception
     */
    public function mock(string $class, ?Closure $validate = null, array $args = []): mixed
    {
        $this->mocker = new MockBuilder($class, $args);
        return $this->buildMock($validate);
    }

    public function getMocker(): MockBuilder
    {
        if (!($this->mocker instanceof MockBuilder)) {
            throw new BadMethodCallException("The mocker is not set yet!");
        }
        return $this->mocker;
    }

    /**
     * Prepares validation for a mock object by binding validation rules and deferring their execution
     *
     * This method takes a mocker instance and a validation closure, binds the validation
     * to the method pool, and schedules the validation to run later via deferValidation.
     * This allows for mock expectations to be defined and validated after the test execution.
     *
     * @param MockBuilder $mocker The mocker instance containing the mock object
     * @param Closure $validate The closure containing validation rules
     * @return MethodRegistry
     * @throws ErrorException
     */
    private function prepareValidation(MockBuilder $mocker, Closure $validate): MethodRegistry
    {
        $pool = new MethodRegistry($mocker);
        $fn = $validate->bindTo($pool);
        if ($fn === null) {
            throw new ErrorException("A callable Closure could not be bound to the method pool!");
        }
        $fn($pool);
        $this->deferValidation(fn () => $this->runValidation($mocker, $pool));
        return $pool;
    }

    /**
     * Executes validation for a mocked class by comparing actual method calls against expectations
     *
     * This method retrieves all method call data for a mocked class and validates each call
     * against the expectations defined in the method pool. The validation results are collected
     * and returned as an array of errors indexed by method name.
     *
     * @param MockBuilder $mocker The mocker instance containing the mocked class
     * @param MethodRegistry $pool The pool containing method expectations
     * @return array An array of validation errors indexed by method name
     * @throws ErrorException
     * @throws Exception
     */
    private function runValidation(MockBuilder $mocker, MethodRegistry $pool): array
    {
        $error = [];
        $data = MockController::getData($mocker->getMockedClassName());
        if (!is_array($data)) {
            throw new ErrorException("Could not get data from mocker!");
        }
        foreach ($data as $row) {
            if (is_object($row) && isset($row->name) && is_string($row->name) && $pool->has($row->name)) {
                $error[$row->name] = $this->validateRow($row, $pool);
            }
        }
        return $error;
    }

    /**
     * Validates a specific method row against the method pool expectations
     *
     * This method compares the actual method call data with the expected validation
     * rules defined in the method pool. It handles both simple value comparisons
     * and complex array validations.
     *
     * @param object $row The method calls data to validate
     * @param MethodRegistry $pool The pool containing validation expectations
     * @return array Array of validation results containing property comparisons
     * @throws ErrorException
     */
    private function validateRow(object $row, MethodRegistry $pool): array
    {
        $item = $pool->get((string)($row->name ?? ""));
        if (!$item) {
            return [];
        }

        $errors = [];
        foreach (get_object_vars($item) as $property => $value) {
            if ($value === null) {
                continue;
            }

            if (!property_exists($row, $property)) {
                throw new ErrorException(
                    "The mock method meta data property name '$property' is undefined in mock object. " .
                    "To resolve this either use MockController::buildMethodData() to add the property dynamically " .
                    "or define a default value through Mocker::addMockMetadata()"
                );
            }
            $currentValue = $row->{$property};

            if (!in_array($property, self::EXCLUDE_VALIDATE)) {
                if (is_array($value)) {
                    $validPool = $this->validateArrayValue($value, $currentValue);
                    $valid = $validPool->isValid();
                    if (is_array($currentValue)) {
                        $this->compareFromValidCollection($validPool, $value, $currentValue);
                    }
                } else {
                    /** @psalm-suppress MixedArgument */
                    $valid = Validator::value($currentValue)->equal($value);
                }

                $item = new TestItem();
                $item = $item->setIsValid($valid)
                    ->setValidation($property)
                    ->setValue($value)
                    ->setCompareToValue($currentValue);
                $errors[] = $item;
            }
        }

        return $errors;
    }

    /**
     * Validates an array value against a validation chain configuration.
     *
     * This method processes an array of validation rules and applies them to the current value.
     * It handles both direct method calls and nested validation configurations.
     *
     * @param array $value The validation configuration array
     * @param mixed $currentValue The value to validate
     * @return Expect The validation chain instance with applied validations
     */
    private function validateArrayValue(array $value, mixed $currentValue): Expect
    {
        $validPool = new Expect($currentValue);
        foreach ($value as $method => $args) {
            if (is_int($method)) {
                foreach ($args as $methodB => $argsB) {
                    if (is_array($argsB) && count($argsB) >= 2) {
                        $validPool
                            ->mapErrorToKey((string)$argsB[0])
                            ->mapErrorValidationName((string)$argsB[1])
                            ->{$methodB}(...$argsB);
                    }
                }
            } else {
                $validPool->{$method}(...$args);
            }
        }

        return $validPool;
    }

    /**
     * Create a comparison from a validation collection
     *
     * @param Expect $validPool
     * @param array $value
     * @param array $currentValue
     * @return void
     */
    protected function compareFromValidCollection(Expect $validPool, array &$value, array &$currentValue): void
    {
        $new = [];
        $error = $validPool->getError();
        $value = $this->mapValueToCollectionError($error, $value);
        foreach ($value as $eqIndex => $_validator) {
            $new[] = Traverse::value($currentValue)->eq($eqIndex)->get();
        }
        $currentValue = $new;
    }

    /**
     * Will map collection value to error
     *
     * @param array $error
     * @param array $value
     * @return array
     */
    protected function mapValueToCollectionError(array $error, array $value): array
    {
        foreach ($value as $item) {
            foreach ($item as $value) {
                if (isset($value[0]) && isset($value[2]) && isset($error[(string)$value[0]])) {
                    $error[(string)$value[0]] = $value[2];
                }
            }
        }
        return $error;
    }

    /**
     * Executes all deferred validations registered earlier using deferValidation().
     *
     * This method runs each queued validation closure, collects their results,
     * and converts them into individual TestUnit instances. If a validation fails,
     * it increases the internal failure count and stores the test details for later reporting.
     *
     * @return array A list of TestUnit results from the deferred validations.
     * @throws ErrorException If any validation logic throws an error during execution.
     */
    public function runDeferredValidations(): array
    {
        foreach ($this->deferredValidation as $row) {

            if (!isset($row['call']) || !is_callable($row['call'])) {
                throw new ErrorException("The validation call is not callable!");
            }

            /** @var callable $row['call'] */
            $error = $row['call']();
            if($row['trace'] === null) {
                continue;
            }
            $hasValidated = [];
            /** @var string $method */
            foreach ($error as $method => $arr) {
                $test = new TestUnit("Mock method \"$method\" failed");
                if (isset($row['trace']) && is_array($row['trace'])) {
                    $test->setCodeLine($row['trace']);
                }
                foreach ($arr as $data) {
                    // We do not want to validate the return here automatically
                    /** @var TestItem $data */
                    if (!in_array($data->getValidation(), self::EXCLUDE_VALIDATE)) {
                        $test->setTestItem($data);
                        if (!isset($hasValidated[$method]) && !$data->isValid()) {
                            $hasValidated[$method] = true;
                            $this->count++;
                        }
                    }
                }

                $this->test[] = $test;
            }
        }

        return $this->test;
    }


    /**
     * Get failed test counts
     *
     * @return int
     */
    public function getTotal(): int
    {
        return count($this->test);
    }

    /**
     * Get failed test counts
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->getTotal() -  $this->getFailedCount();
    }

    /**
     * Get failed test counts
     *
     * @return int
     */
    public function getFailedCount(): int
    {
        return $this->count;
    }

    /**
     * Check if it has failed tests
     *
     * @return bool
     */
    public function hasFailed(): bool
    {
        return $this->getFailedCount() > 0;
    }

    /**
     * Get original value
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Get the test configuration
     *
     * @return TestConfig
     */
    public function getConfig(): TestConfig
    {
        return $this->config;
    }

    /**
     * Get user added message
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->config->message;
    }

    /**
     * Get a test array object
     *
     * @return array
     */
    public function getTest(): array
    {
        return $this->test;
    }

    /**
     * This will build the closure test
     *
     * @param Closure $validation
     * @param Expect $validPool
     * @param string|null $message
     * @return array
     */
    protected function buildClosureTest(Closure $validation, Expect $validPool, ?string $message = null): array
    {
        //$bool = false;
        $validation = $validation->bindTo($validPool);
        $error = [];
        if ($validation !== null) {
            try {
                $bool = $validation($this->value, $validPool);
            } catch (AssertionError $e) {
                $bool = false;
                $message = $e->getMessage();
            }

            $error = $validPool->getError();
            if ($bool === false && $message !== null) {
                $error[] = [
                    $message => true
                ];
            } elseif (is_bool($bool) && !$bool) {
                $error['customError'] = false;
            }
        }

        if ($this->getMessage() === null) {
            throw new RuntimeException("You need to specify a \"message\" in first parameter of ->group(string|TestConfig \$message, ...).");
        }

        return $error;
    }

    /**
     * This will build the array test
     *
     * @param string $method
     * @param array|Closure $args
     * @return bool
     * @throws ErrorException
     */
    protected function buildArrayTest(string $method, array|Closure $args): bool
    {
        if ($args instanceof Closure) {
            $args = $args->bindTo($this->valid($this->value));
            if ($args === null) {
                throw new ErrorException("The argument is not returning a callable Closure!");
            }
            $bool = $args($this->value);
            if (!is_bool($bool)) {
                throw new RuntimeException("A callable validation must return a boolean!");
            }
        } else {
            if (!method_exists(Validator::class, $method)) {
                throw new BadMethodCallException("The validation $method does not exist!");
            }

            try {
                $bool = call_user_func_array([$this->valid($this->value), $method], $args);
            } catch (Throwable $e) {
                throw new RuntimeException($e->getMessage(), (int)$e->getCode(), $e);
            }
        }

        return (bool)$bool;
    }

    /**
     * Init MaplePHP validation
     *
     * @param mixed $value
     * @return Validator
     * @throws ErrorException
     */
    protected function valid(mixed $value): Validator
    {
        return new Validator($value);
    }

    /**
     * This is a helper function that will list all inherited proxy methods
     *
     * @param string $class
     * @param string|null $prefixMethods
     * @param bool $isolateClass
     * @return void
     * @throws ReflectionException
     */
    public function listAllProxyMethods(string $class, ?string $prefixMethods = null, bool $isolateClass = false): void
    {
        /** @var class-string $class */
        $reflection = new ReflectionClass($class);
        $traitMethods = $isolateClass ? $this->getAllTraitMethods($reflection) : [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor()) {
                continue;
            }

            if (in_array($method->getName(), $traitMethods, true)) {
                continue;
            }

            if ($isolateClass && $method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            $params = array_map(function ($param) {
                $type = $param->hasType() ? (string)$param->getType() . ' ' : '';
                $value = $param->isDefaultValueAvailable() ? ' = ' . (string)Str::value($param->getDefaultValue())->exportReadableValue()->get() : "";
                return $type . '$' . $param->getName() . $value;
            }, $method->getParameters());

            $name = $method->getName();
            if (!$method->isStatic() && !str_starts_with($name, '__')) {
                if ($prefixMethods !== null) {
                    $name = $prefixMethods . ucfirst($name);
                }
                echo "@method self $name(" . implode(', ', $params) . ")\n";
            }
        }
    }

    /**
     * Retrieves all public methods from the traits used by a given class.
     *
     * This method collects and returns the names of all public methods
     * defined in the traits used by the provided ReflectionClass instance.
     *
     * @param ReflectionClass $reflection The reflection instance of the class to inspect
     * @return array An array of method names defined in the traits
     */
    public function getAllTraitMethods(ReflectionClass $reflection): array
    {
        $traitMethods = [];
        foreach ($reflection->getTraits() as $trait) {
            foreach ($trait->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $traitMethods[] = $method->getName();
            }
        }
        return $traitMethods;
    }
}
