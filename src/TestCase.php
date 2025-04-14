<?php

declare(strict_types=1);

namespace MaplePHP\Unitary;

use BadMethodCallException;
use Closure;
use ErrorException;
use MaplePHP\Unitary\Mocker\Mocker;
use MaplePHP\Unitary\Mocker\MockerController;
use MaplePHP\Validate\Inp;
use MaplePHP\Validate\ValidatePool;
use RuntimeException;
use Throwable;

class TestCase
{
    private mixed $value;
    private ?string $message;
    private array $test = [];
    private int $count = 0;
    private ?Closure $bind = null;
    private ?string $errorMessage = null;

    private array $deferredValidation = [];


    /**
     * Initialize a new TestCase instance with an optional message.
     *
     * @param string|null $message A message to associate with the test case.
     */
    public function __construct(?string $message = null)
    {
        $this->message = $message;
    }

    /**
     * Bind the test case to the Closure
     * 
     * @param Closure $bind
     * @return void
     */
    public function bind(Closure $bind): void
    {
        $this->bind = $bind->bindTo($this);
    }

    /**
     * Will dispatch the case tests and return them as an array
     * 
     * @return array
     */
    public function dispatchTest(): array
    {
        $test = $this->bind;
        if (!is_null($test)) {
            $test($this);
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
     * @param Closure(ValidatePool, mixed): bool $validation The validation logic
     * @return $this
     * @throws ErrorException
     */
    public function validate(mixed $expect, Closure $validation): self
    {
        $this->expectAndValidate($expect, function(mixed $value, ValidatePool $inst) use($validation) {
            return $validation($inst, $value);
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
        ?string $message = null
    ): self {
        $this->value = $expect;
        $test = new TestUnit($message);
        $test->setTestValue($this->value);
        if($validation instanceof Closure) {
            $list = $this->buildClosureTest($validation);
            foreach($list as $method => $valid) {
                $test->setUnit(!$list, $method, []);
            }
        } else {
            foreach($validation as $method => $args) {
                if(!($args instanceof Closure) && !is_array($args)) {
                    $args = [$args];
                }
                $test->setUnit($this->buildArrayTest($method, $args), $method, (is_array($args) ? $args : []));
            }
        }
        if(!$test->isValid()) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
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
    public function deferValidation(Closure $validation)
    {
        // This will add a cursor to the possible line and file where error occurred
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
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
    public function add(mixed $expect, array|Closure $validation, ?string $message = null) {
        return $this->expectAndValidate($expect, $validation, $message);
    }

    /**
     * Init a test wrapper
     *
     * @param string $class
     * @param array $args
     * @return TestWrapper
     */
    public function wrap(string $class, array $args = []): TestWrapper
    {
        return new class($class, $args) extends TestWrapper {
            function __construct(string $class, array $args = [])
            {
                parent::__construct($class, $args);
            }
        };
    }

    /**
     * Creates and returns an instance of a dynamically generated mock class.
     *
     * The mock class is based on the provided class name and optional constructor arguments.
     * A validation closure can also be provided to define mock expectations. These
     * validations are deferred and will be executed later via runDeferredValidations().
     *
     * @template T of object
     * @param class-string<T> $class
     * @param Closure|null $validate
     * @param array $args
     * @return T
     * @throws \ReflectionException
     */
    public function mock(string $class, ?Closure $validate = null, array $args = []): mixed
    {
        $mocker = new Mocker($class, $args);
        if(is_callable($validate)) {
            $pool = $mocker->getMethodPool();
            $fn = $validate->bindTo($pool);
            $fn($pool);

            $this->deferValidation(function() use($mocker, $pool) {
                $error = [];
                $data = MockerController::getData($mocker->getMockedClassName());

                foreach($data as $row) {
                    $item = $pool->get($row->name);
                    if($item) {
                        foreach (get_object_vars($item) as $property => $value) {
                            if(!is_null($value)) {
                                $currentValue = $row->{$property};
                                if(is_array($value)) {
                                    $validPool = new ValidatePool($currentValue);
                                    foreach($value as $method => $args) {
                                        $validPool->{$method}(...$args);
                                    }
                                    $valid = $validPool->isValid();
                                    $currentValue = $validPool->getValue();

                                } else {
                                    $valid = Inp::value($currentValue)->equal($value);
                                }

                                $error[$row->name][] = [
                                    "property" => $property,
                                    "currentValue" => $currentValue,
                                    "expectedValue" => $value,
                                    "valid" => $valid
                                ];
                            }
                        }
                    }
                }
                return $error;
            });
        }
        return $mocker->execute();
    }

    /**
     * Executes all deferred validations that were registered earlier using deferValidation().
     *
     * This method runs each queued validation closure, collects their results,
     * and converts them into individual TestUnit instances. If a validation fails,
     * it increases the internal failure count and stores the test details for later reporting.
     *
     * @return TestUnit[] A list of TestUnit results from the deferred validations.
     * @throws ErrorException If any validation logic throws an error during execution.
     */
    public function runDeferredValidations()
    {
        foreach($this->deferredValidation as $row) {
            $error = $row['call']();
            foreach($error as $method => $arr) {
                $test = new TestUnit("Mock method \"{$method}\" failed");
                if(is_array($row['trace'] ?? "")) {
                    $test->setCodeLine($row['trace']);
                }
                foreach($arr as $data) {
                    $test->setUnit($data['valid'], $data['property'], [], [
                        $data['expectedValue'], $data['currentValue']
                    ]);
                    if (!$data['valid']) {
                        $this->count++;
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
     * Get user added message
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Get test array object
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
     * @return array
     */
    protected function buildClosureTest(Closure $validation): array
    {
        //$bool = false;
        $validPool = new ValidatePool($this->value);
        $validation = $validation->bindTo($validPool);

        $error = [];
        if(!is_null($validation)) {
            $bool = $validation($this->value, $validPool);
            $error = $validPool->getError();
            if(is_bool($bool) && !$bool) {
                $error['customError'] = $bool;
            }
        }

        if(is_null($this->message)) {
            throw new RuntimeException("When testing with closure the third argument message is required");
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
        if($args instanceof Closure) {
            $args = $args->bindTo($this->valid($this->value));
            if(is_null($args)) {
                throw new ErrorException("The argument is not returning a callable Closure!");
            }
            $bool = $args($this->value);
            if(!is_bool($bool)) {
                throw new RuntimeException("A callable validation must return a boolean!");
            }
        } else {
            if(!method_exists(Inp::class, $method)) {
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
     * @return Inp
     * @throws ErrorException
     */
    protected function valid(mixed $value): Inp
    {
        return new Inp($value);
    }

    /**
     * This is a helper function that will list all inherited proxy methods
     *
     * @param string $class
     * @param string|null $prefixMethods
     * @return void
     * @throws \ReflectionException
     */
    public function listAllProxyMethods(string $class, ?string $prefixMethods = null): void
    {
        $reflection = new \ReflectionClass($class);
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor()) continue;
            $params = array_map(function($param) {
                $type = $param->hasType() ? $param->getType() . ' ' : '';
                return $type . '$' . $param->getName();
            }, $method->getParameters());

            $name = $method->getName();

            if(!$method->isStatic() && !str_starts_with($name, '__')) {
                if(!is_null($prefixMethods)) {
                    $name = $prefixMethods . ucfirst($name);
                }
                echo "@method self $name(" . implode(', ', $params) . ")\n";
            }
        }
    }
}
