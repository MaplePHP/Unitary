<?php

declare(strict_types=1);

namespace MaplePHP\Unitary;

use MaplePHP\Validate\ValidatePool;
use MaplePHP\Validate\Inp;
use BadMethodCallException;
use ErrorException;
use RuntimeException;
use Closure;
use Throwable;

class TestCase
{
    private mixed $value;
    private ?string $message;
    private array $test = [];
    private int $count = 0;
    private ?Closure $bind = null;
    private ?string $errorMessage = null;


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
        $this->addTestUnit($expect, function(mixed $value, ValidatePool $inst) use($validation) {
            return $validation($inst, $value);
        }, $this->errorMessage);

        return $this;
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
        return $this->addTestUnit($expect, $validation, $message);
    }

    /**
     * Create a test
     * 
     * @param mixed $expect
     * @param array|Closure $validation
     * @param string|null $message
     * @return TestCase
     * @throws ErrorException
     */
    protected function addTestUnit(mixed $expect, array|Closure $validation, ?string $message = null): self
    {
        $this->value = $expect;
        $test = new TestUnit($this->value, $message);
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
     * Init a test wrapper
     *
     * @param string $className
     * @return TestWrapper
     */
    public function wrapper(string $className): TestWrapper
    {
        return new class($className) extends TestWrapper {
        };
    }

    public function mock(string $className, null|array|Closure $validate = null): object
    {
        $mocker = new TestMocker($className);
        if(is_array($validate)) {
            $mocker->validate($validate);
        }
        if(is_callable($validate)) {
            $fn = $validate->bindTo($mocker);
            $fn($mocker);
        }
        return $mocker->execute();
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
    public function buildClosureTest(Closure $validation): array
    {
        $bool = false;
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
    public function buildArrayTest(string $method, array|Closure $args): bool
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
