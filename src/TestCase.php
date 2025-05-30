<?php

declare(strict_types=1);

namespace MaplePHP\Unitary;

use AssertionError;
use BadMethodCallException;
use Closure;
use ErrorException;
use Exception;
use MaplePHP\Blunder\BlunderErrorException;
use MaplePHP\DTO\Format\Str;
use MaplePHP\DTO\Traverse;
use MaplePHP\Unitary\Mocker\MethodRegistry;
use MaplePHP\Unitary\Mocker\MockBuilder;
use MaplePHP\Unitary\Mocker\MockController;
use MaplePHP\Unitary\TestUtils\ExecutionWrapper;
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
    private ?string $errorMessage = null;
    private array $deferredValidation = [];

    private ?MockBuilder $mocker = null;
    private bool $hasAssertError = false;

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
     * Sets the assertion error flag to true
     *
     * @return void
     */
    function setHasAssertError(): void
    {
        $this->hasAssertError = true;
    }

    /**
     * Gets the current state of the assertion error flag
     *
     * @return bool
     */
    function getHasAssertError(): bool
    {
        return $this->hasAssertError;
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
        if ($test !== null) {
            try {
                $newInst = $test($this);
            } catch (AssertionError $e) {
                $newInst = clone $this;
                $newInst->setHasAssertError();
                $msg = "Assertion failed";
                $newInst->expectAndValidate(
                    true, fn() => false, $msg, $e->getMessage(), $e->getTrace()[0]
                );
            } catch (Throwable $e) {
                if(str_contains($e->getFile(), "eval()")) {
                    throw new BlunderErrorException($e->getMessage(), (int)$e->getCode());
                }
                throw $e;
            }
            if ($newInst instanceof self) {
                $row = $newInst;
            }
        }
        return $this->test;
    }

    /**
     * Add custom error message if validation fails
     *
     * @param string $message
     * @return $this
     */
    public function error(string $message): self
    {
        $this->errorMessage = $message;
        return $this;
    }

    /**
     * Add a test unit validation using the provided expectation and validation logic
     *
     * @param mixed $expect The expected value
     * @param Closure(Expect, Traverse): bool $validation
     * @return $this
     * @throws ErrorException
     */
    public function validate(mixed $expect, Closure $validation): self
    {
        $this->expectAndValidate($expect, function (mixed $value, Expect $inst) use ($validation) {
            return $validation($inst, new Traverse($value));
        }, $this->errorMessage);

        return $this;
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
     * @param string|null $message Optional custom message for test reporting.
     * @return $this
     * @throws ErrorException If validation fails during runtime execution.
     */
    protected function expectAndValidate(
        mixed $expect,
        array|Closure $validation,
        ?string $message = null,
        ?string $description = null,
        ?array $trace = null
    ): self {
        $this->value = $expect;
        $test = new TestUnit($message);
        $test->setTestValue($this->value);
        if ($validation instanceof Closure) {
            $validPool = new Expect($this->value);
            $listArr = $this->buildClosureTest($validation, $validPool, $description);

            foreach ($listArr as $list) {
                if(is_bool($list)) {
                    $test->setUnit($list, "Validation");
                } else {
                    foreach ($list as $method => $valid) {
                        /** @var array<mixed>|bool $valid */
                        $test->setUnit(false, (string)$method, $valid);
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
                $test->setUnit($this->buildArrayTest($method, $args), $method, (is_array($args) ? $args : []));
            }
        }
        if (!$test->isValid()) {
            if($trace === null || $trace === []) {
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            }

            $test->setCodeLine($trace);
            $this->count++;
        }
        $this->test[] = $test;
        $this->errorMessage = null;
        return $this;
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
     * Same as "addTestUnit" but is public and will make sure the validation can be
     * properly registered and traceable
     *
     * @param mixed $expect The expected value
     * @param array|Closure $validation The validation logic
     * @param string|null $message An optional descriptive message for the test
     * @return $this
     * @throws ErrorException
     */
    public function add(mixed $expect, array|Closure $validation, ?string $message = null): TestCase
    {
        return $this->expectAndValidate($expect, $validation, $message);
    }

    /**
     * Initialize a test wrapper
     *
     * NOTICE: When mocking a class with required constructor arguments, those arguments must be
     * specified in the mock initialization method or it will fail. This is because the mock
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
        if(!($this->mocker instanceof MockBuilder)) {
            throw new BadMethodCallException("The mocker is not set yet!");
        }
        if (is_callable($validate)) {
            $this->prepareValidation($this->mocker, $validate);
        }
        try {
            /** @psalm-suppress MixedReturnStatement */
            return $this->mocker->execute();
        } catch (Throwable $e) {
            throw new BlunderErrorException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Creates and returns an instance of a dynamically generated mock class.
     *
     * The mock class is based on the provided class name and optional constructor arguments.
     * A validation closure can also be provided to define mock expectations. These
     * validations are deferred and will be executed later via runDeferredValidations().
     *
     * @param class-string<T> $class
     * @param Closure|null $validate
     * @param array $args
     * @return T
     * @throws Exception
     */
    public function mock(string $class, ?Closure $validate = null, array $args = [])
    {
        $this->mocker = new MockBuilder($class, $args);
        return $this->buildMock($validate);
    }

    public function getMocker(): MockBuilder
    {
        if(!($this->mocker instanceof MockBuilder)) {
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
     * @return void
     * @throws ErrorException
     */
    private function prepareValidation(MockBuilder $mocker, Closure $validate): void
    {
        $pool = new MethodRegistry($mocker);
        $fn = $validate->bindTo($pool);
        if ($fn === null) {
            throw new ErrorException("A callable Closure could not be bound to the method pool!");
        }
        $fn($pool);

        $this->deferValidation(fn () => $this->runValidation($mocker, $pool));
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

            if(!property_exists($row, $property)) {
                throw new ErrorException(
                    "The mock method meta data property name '$property' is undefined in mock object. " .
                    "To resolve this either use MockController::buildMethodData() to add the property dynamically " .
                    "or define a default value through Mocker::addMockMetadata()"
                );
            }
            $currentValue = $row->{$property};

            if(!in_array($property, self::EXCLUDE_VALIDATE)) {
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
                $errors[] = [
                    "property" => $property,
                    "currentValue" => $currentValue,
                    "expectedValue" => $value,
                    "valid" => $valid
                ];
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
            $hasValidated = [];
            /** @var string $method */
            foreach ($error as $method => $arr) {
                $test = new TestUnit("Mock method \"$method\" failed");
                if (isset($row['trace']) && is_array($row['trace'])) {
                    $test->setCodeLine($row['trace']);
                }
                foreach ($arr as $data) {
                    // We do not want to validate the return here automatically
                    /** @var array{property: string} $data */
                    if(!in_array($data['property'], self::EXCLUDE_VALIDATE)) {
                        /** @var array{valid: bool|null, expectedValue: mixed, currentValue: mixed} $data  */
                        $test->setUnit($data['valid'], $data['property'], [], [
                            $data['expectedValue'], $data['currentValue']
                        ]);
                        if (!isset($hasValidated[$method]) && $data['valid'] === null || $data['valid'] === false) {
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
            if($bool === false && $message !== null) {
                $error[] = [
                    $message => true
                ];
            } else if (is_bool($bool) && !$bool) {
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
